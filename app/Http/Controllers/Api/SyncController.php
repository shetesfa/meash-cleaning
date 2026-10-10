<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SalesVisit;
use App\Models\SyncConflict;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    public function batchSync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.client_uuid' => 'required|string',
            'items.*.entity_type' => 'required|string|in:order,sales_visit,expense,job_action',
            'items.*.action' => 'required|string|in:create,update,status_change,job_action',
            'items.*.payload' => 'required|array',
            'items.*.client_version' => 'nullable|integer',
            'items.*.client_timestamp' => 'nullable|string',
        ]);

        $synced = [];
        $conflicts = [];
        $errors = [];

        foreach ($validated['items'] as $item) {
            $uuid = $item['client_uuid'];
            $entityType = $item['entity_type'];
            $action = $item['action'];
            $payload = $item['payload'];
            $clientVer = $item['client_version'] ?? 1;

            // Idempotency check: did we already process this client_uuid?
            $existingAudit = AuditLog::where('action', 'like', "%{$uuid}%")
                ->orWhere('new_values->client_uuid', $uuid)
                ->first();

            if ($existingAudit) {
                $synced[] = [
                    'client_uuid' => $uuid,
                    'status' => 'already_processed',
                    'entity_id' => $existingAudit->auditable_id,
                ];
                continue;
            }

            try {
                if ($entityType === 'order' && $action === 'create') {
                    // Create Order
                    DB::transaction(function () use ($payload, $uuid, $request, &$synced) {
                        $orderNumber = Order::generateNextNumber();
                        $assignedTeam = !empty($payload['assigned_team_id']) ? $payload['assigned_team_id'] : null;
                        $orderStatus = $assignedTeam ? 'assigned' : ($payload['order_status'] ?? 'new');

                        $order = Order::create([
                            'order_number' => $orderNumber,
                            'customer_id' => $payload['customer_id'],
                            'created_by_user_id' => $request->user()?->id,
                            'assigned_team_id' => $assignedTeam,
                            'source' => $payload['source'] ?? 'phone',
                            'order_status' => $orderStatus,
                            'payment_status' => 'unpaid',
                            'appointment_date' => $payload['appointment_date'],
                            'appointment_time_slot' => $payload['appointment_time_slot'] ?? 'morning',
                            'address' => $payload['address'],
                            'subcity' => $payload['subcity'] ?? null,
                            'notes' => ($payload['notes'] ?? '') . " [Synced from offline, UUID: {$uuid}]",
                            'version' => 1,
                        ]);

                        if (!empty($payload['items'])) {
                            foreach ($payload['items'] as $it) {
                                OrderItem::create([
                                    'order_id' => $order->id,
                                    'service_id' => $it['service_id'] ?? null,
                                    'item_name' => $it['item_name'],
                                    'quantity' => $it['quantity'],
                                    'unit_price' => $it['unit_price'],
                                    'subtotal' => (float)$it['quantity'] * (float)$it['unit_price'],
                                ]);
                            }
                        }

                        AuditLog::logAction(
                            $request->user()?->id,
                            "synced_create_{$uuid}",
                            Order::class,
                            $order->id,
                            null,
                            array_merge($order->toArray(), ['client_uuid' => $uuid])
                        );

                        $synced[] = [
                            'client_uuid' => $uuid,
                            'status' => 'synced',
                            'entity_id' => $order->id,
                            'order_number' => $order->order_number,
                        ];
                    });
                } elseif (($entityType === 'order' || $entityType === 'job_action') && ($action === 'status_change' || $action === 'job_action' || $action === 'update')) {
                    $order = Order::find($payload['order_id'] ?? $payload['id'] ?? null);
                    if (!$order) {
                        $errors[] = [
                            'client_uuid' => $uuid,
                            'error' => 'Order not found on server',
                        ];
                        continue;
                    }

                    // Conflict Detection: check if server order version or status changed divergently
                    $incomingStatus = $payload['order_status'] ?? $payload['action'] ?? null;
                    if ($incomingStatus === 'complete') $incomingStatus = 'completed';
                    if ($incomingStatus === 'start') $incomingStatus = 'cleaning';

                    if ($order->version > $clientVer && $order->order_status !== $incomingStatus) {
                        // Conflict! Store in sync_conflicts
                        $conflict = SyncConflict::create([
                            'entity_type' => 'order',
                            'entity_id' => $order->id,
                            'client_uuid' => $uuid,
                            'client_state' => $payload,
                            'server_state' => $order->toArray(),
                        ]);

                        $conflicts[] = [
                            'client_uuid' => $uuid,
                            'conflict_id' => $conflict->id,
                            'entity_id' => $order->id,
                            'client_state' => $payload,
                            'server_state' => [
                                'order_status' => $order->order_status,
                                'version' => $order->version,
                            ],
                            'message' => 'Conflict detected: local change diverged from updated server state.',
                        ];
                        continue;
                    }

                    // Apply update safely
                    if ($incomingStatus) {
                        $order->order_status = $incomingStatus;
                        if ($incomingStatus === 'completed') {
                            $order->completed_at = now();
                        } elseif ($incomingStatus === 'on_the_way') {
                            \App\Services\SmsService::sendTeamDispatched($order);
                        }
                    }

                    // Update team live GPS if present
                    if (!empty($payload['latitude']) && !empty($payload['longitude']) && $order->assigned_team_id) {
                        \App\Models\CleaningTeam::where('id', $order->assigned_team_id)->update([
                            'current_latitude' => $payload['latitude'],
                            'current_longitude' => $payload['longitude'],
                            'location_updated_at' => now(),
                        ]);
                    }

                    if (!empty($payload['completion_notes'])) {
                        $order->completion_notes = $payload['completion_notes'];
                    }

                    $order->version += 1;
                    $order->save();

                    AuditLog::logAction(
                        $request->user()?->id,
                        "synced_update_{$uuid}",
                        Order::class,
                        $order->id,
                        null,
                        ['client_uuid' => $uuid, 'status' => $order->order_status]
                    );

                    $synced[] = [
                        'client_uuid' => $uuid,
                        'status' => 'synced',
                        'entity_id' => $order->id,
                    ];
                } elseif ($entityType === 'sales_visit') {
                    $visit = SalesVisit::create([
                        'visit_code' => SalesVisit::generateNextCode(),
                        'organization_id' => $payload['organization_id'],
                        'contact_person' => $payload['contact_person'],
                        'contact_position' => $payload['contact_position'] ?? null,
                        'phone' => $payload['phone'],
                        'address' => $payload['address'],
                        'services_introduced' => $payload['services_introduced'] ?? null,
                        'interest_level' => $payload['interest_level'] ?? 'medium',
                        'salesperson_user_id' => $request->user()->id,
                        'visit_date' => $payload['visit_date'] ?? Carbon::today(),
                        'notes' => ($payload['notes'] ?? '') . " [Synced offline]",
                        'stage' => $payload['stage'] ?? 'visited',
                    ]);

                    AuditLog::logAction(
                        $request->user()?->id,
                        "synced_sales_{$uuid}",
                        SalesVisit::class,
                        $visit->id,
                        null,
                        ['client_uuid' => $uuid]
                    );

                    $synced[] = [
                        'client_uuid' => $uuid,
                        'status' => 'synced',
                        'entity_id' => $visit->id,
                    ];
                } elseif ($entityType === 'expense') {
                    $expense = Expense::create([
                        'expense_number' => Expense::generateNextNumber(),
                        'category' => $payload['category'],
                        'amount' => $payload['amount'],
                        'reference_number' => $payload['reference_number'] ?? null,
                        'description' => ($payload['description'] ?? '') . " [Synced offline]",
                        'date' => $payload['date'] ?? Carbon::today(),
                        'entered_by_user_id' => $request->user()->id,
                    ]);

                    AuditLog::logAction(
                        $request->user()?->id,
                        "synced_expense_{$uuid}",
                        Expense::class,
                        $expense->id,
                        null,
                        ['client_uuid' => $uuid]
                    );

                    $synced[] = [
                        'client_uuid' => $uuid,
                        'status' => 'synced',
                        'entity_id' => $expense->id,
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'client_uuid' => $uuid,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'synced_count' => count($synced),
            'conflict_count' => count($conflicts),
            'error_count' => count($errors),
            'synced' => $synced,
            'conflicts' => $conflicts,
            'errors' => $errors,
        ]);
    }

    public function listConflicts(): JsonResponse
    {
        $conflicts = SyncConflict::whereNull('resolved_at')
            ->latest()
            ->paginate(20);

        return response()->json($conflicts);
    }

    public function resolveConflict(Request $request, SyncConflict $conflict): JsonResponse
    {
        $validated = $request->validate([
            'resolution' => 'required|string|in:server_wins,client_wins,manual',
            'manual_status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($conflict->entity_type === 'order') {
            $order = Order::find($conflict->entity_id);
            if ($order) {
                if ($validated['resolution'] === 'client_wins') {
                    $clientState = $conflict->client_state;
                    $status = $clientState['order_status'] ?? $clientState['action'] ?? null;
                    if ($status === 'complete') $status = 'completed';
                    if ($status) {
                        $order->order_status = $status;
                        $order->version += 1;
                        $order->save();
                    }
                } elseif ($validated['resolution'] === 'manual' && !empty($validated['manual_status'])) {
                    $order->order_status = $validated['manual_status'];
                    $order->version += 1;
                    $order->save();
                }
            }
        }

        $conflict->update([
            'resolution' => $validated['resolution'],
            'resolved_by_user_id' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Conflict resolved successfully',
            'conflict' => $conflict,
        ]);
    }
}
