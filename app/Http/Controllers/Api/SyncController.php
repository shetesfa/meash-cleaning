<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SalesVisit;
use App\Models\SyncConflict;
use App\Models\SyncIdempotencyRecord;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $inProgress = [];

        $user = $request->user();

        foreach ($validated['items'] as $item) {
            $uuid = $item['client_uuid'];
            $entityType = $item['entity_type'];
            $action = $item['action'];
            $payload = $item['payload'];
            $clientVer = $item['client_version'] ?? 1;

            // 1. Durable Database-Backed Idempotency Record Check
            $idempRecord = SyncIdempotencyRecord::where('client_uuid', $uuid)->first();

            if ($idempRecord && $idempRecord->status === 'completed') {
                $synced[] = [
                    'client_uuid' => $uuid,
                    'status' => 'already_processed',
                    'entity_id' => $idempRecord->entity_id,
                    'order_number' => $idempRecord->response_payload['order_number'] ?? null,
                ];
                continue;
            }

            if ($idempRecord && $idempRecord->status === 'in_progress') {
                // Another concurrent request is actively processing this client_uuid.
                // NEVER return 'synced' or 'already_processed' so the client preserves its queue!
                $inProgress[] = [
                    'client_uuid' => $uuid,
                    'status' => 'in_progress',
                    'message' => 'Sync operation currently in progress by another request. Retaining local queue item.',
                ];
                continue;
            }

            // Also inspect durable audit log before running again
            $existingAudit = AuditLog::where('action', 'like', "%{$uuid}%")
                ->orWhere('new_values->client_uuid', $uuid)
                ->first();

            if ($existingAudit) {
                if ($idempRecord) {
                    $idempRecord->update(['status' => 'completed', 'entity_id' => $existingAudit->auditable_id]);
                } else {
                    SyncIdempotencyRecord::create([
                        'client_uuid' => $uuid,
                        'user_id' => $user?->id,
                        'entity_type' => $entityType,
                        'action' => $action,
                        'status' => 'completed',
                        'entity_id' => $existingAudit->auditable_id,
                    ]);
                }

                $synced[] = [
                    'client_uuid' => $uuid,
                    'status' => 'already_processed',
                    'entity_id' => $existingAudit->auditable_id,
                ];
                continue;
            }

            // 2. Atomic Lock Acquisition (DB unique claim + Cache lock defense-in-depth)
            $lockToken = Str::random(32);
            $lockKey = "sync_lock_{$uuid}";
            $cacheLock = Cache::lock($lockKey, 30);

            if (! $cacheLock->get()) {
                $inProgress[] = [
                    'client_uuid' => $uuid,
                    'status' => 'in_progress',
                    'message' => 'Concurrent duplicate sync request safely held in progress.',
                ];
                continue;
            }

            try {
                if ($idempRecord && $idempRecord->status === 'failed') {
                    $idempRecord->update([
                        'status' => 'in_progress',
                        'user_id' => $user?->id,
                        'lock_token' => $lockToken,
                    ]);
                } else {
                    $idempRecord = SyncIdempotencyRecord::create([
                        'client_uuid' => $uuid,
                        'user_id' => $user?->id,
                        'entity_type' => $entityType,
                        'action' => $action,
                        'status' => 'in_progress',
                        'lock_token' => $lockToken,
                    ]);
                }
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                $cacheLock->release();
                $inProgress[] = [
                    'client_uuid' => $uuid,
                    'status' => 'in_progress',
                    'message' => 'Concurrent duplicate sync request safely held.',
                ];
                continue;
            }

            // 3. Processing with Guaranteed Lock Release & State Resolution in finally block
            $isSuccess = false;
            $resultEntityId = null;
            $resultPayload = null;

            try {
                if ($entityType === 'order' && $action === 'create') {
                    // Strict explicit role check: Owner, Admin, or Reception only.
                    $isOwner = $user && ($user->role === 'owner' || $user->role === 'admin');
                    $isReception = $user && $user->role === 'reception';

                    if (! $isOwner && ! $isReception) {
                        throw new \Exception('Unauthorized: Only Reception and Owner are permitted to create orders.');
                    }

                    // Create Order in transaction
                    DB::transaction(function () use ($payload, $uuid, $request, &$synced, &$isSuccess, &$resultEntityId, &$resultPayload) {
                        $orderNumber = Order::generateNextNumber();
                        $assignedTeam = !empty($payload['assigned_team_id']) ? $payload['assigned_team_id'] : (!empty($payload['team_id']) ? $payload['team_id'] : null);

                        $incomingStatus = $payload['order_status'] ?? 'new';
                        $allowedStatuses = ['new', 'confirmed', 'assigned', 'on_the_way', 'cleaning', 'completed', 'rescheduled'];
                        $orderStatus = in_array($incomingStatus, $allowedStatuses, true) ? ($assignedTeam ? 'assigned' : $incomingStatus) : ($assignedTeam ? 'assigned' : 'new');

                        $customerId = $payload['customer_id'] ?? null;
                        if (!$customerId) {
                            $rawPhone = $payload['customer_phone'] ?? $payload['phone'] ?? null;
                            $normPhone = $rawPhone ? \App\Services\SecurityTokenService::normalizePhone($rawPhone) : '0900000000';
                            $cust = Customer::firstOrCreate(
                                ['phone' => $normPhone],
                                [
                                    'customer_code' => Customer::generateNextCode(),
                                    'full_name' => $payload['customer_name'] ?? $payload['full_name'] ?? 'ደንበኛ',
                                    'address' => $payload['address'] ?? 'አዲስ አበባ',
                                    'subcity' => $payload['subcity'] ?? null,
                                ]
                            );
                            $customerId = $cust->id;
                        }

                        $order = Order::create([
                            'order_number' => $orderNumber,
                            'customer_id' => $customerId,
                            'created_by_user_id' => $request->user()?->id,
                            'assigned_team_id' => $assignedTeam,
                            'source' => $payload['source'] ?? 'phone',
                            'order_status' => $orderStatus,
                            'payment_status' => 'unpaid', // Authoritative: never trust client payment status
                            'subtotal' => 0,
                            'discount' => 0,              // Authoritative: never trust client discount
                            'tax' => 0,
                            'total' => 0,
                            'appointment_date' => $payload['appointment_date'] ?? null,
                            'appointment_time_slot' => $payload['appointment_time_slot'] ?? 'morning',
                            'address' => $payload['address'] ?? 'አዲስ አበባ',
                            'subcity' => $payload['subcity'] ?? null,
                            'notes' => ($payload['notes'] ?? '') . " [Synced from offline, UUID: {$uuid}]",
                            'version' => 1,
                        ]);

                        if ($order->appointment_date) {
                            Appointment::create([
                                'order_id' => $order->id,
                                'customer_id' => $customerId,
                                'cleaning_team_id' => $assignedTeam,
                                'appointment_date' => $order->appointment_date,
                                'status' => 'scheduled',
                                'notes' => "Slot: {$order->appointment_time_slot}",
                            ]);
                        }

                        $subtotalCalc = 0;
                        if (!empty($payload['items'])) {
                            foreach ($payload['items'] as $it) {
                                // Authoritative catalogue price lookup - never fall back to forged client price
                                $service = null;
                                if (!empty($it['service_id'])) {
                                    $service = \App\Models\Service::where('id', $it['service_id'])->where('is_active', true)->first();
                                }
                                if (!$service && !empty($it['service_code'])) {
                                    $service = \App\Models\Service::where('code', $it['service_code'])->where('is_active', true)->first();
                                }
                                if (!$service && !empty($it['item_name'])) {
                                    $cleanName = trim($it['item_name']);
                                    $service = \App\Models\Service::where('is_active', true)
                                        ->where(function ($q) use ($cleanName) {
                                            $q->where('name_en', $cleanName)
                                              ->orWhere('name_am', $cleanName)
                                              ->orWhere('code', strtolower($cleanName))
                                              ->orWhere('name_en', 'like', "%{$cleanName}%")
                                              ->orWhere('name_am', 'like', "%{$cleanName}%");
                                        })->first();
                                    if (!$service) {
                                        $firstWord = explode(' ', $cleanName)[0] ?? '';
                                        if (strlen($firstWord) >= 3) {
                                            $service = \App\Models\Service::where('is_active', true)
                                                ->where(function ($q) use ($firstWord) {
                                                    $q->where('code', 'like', "%{$firstWord}%")
                                                      ->orWhere('name_en', 'like', "%{$firstWord}%")
                                                      ->orWhere('name_am', 'like', "%{$firstWord}%");
                                                })->first();
                                        }
                                    }
                                }

                                if (!$service) {
                                    throw new \Exception("Selected service is invalid or unavailable in catalog.");
                                }

                                $unitPrice = (float) $service->base_price;
                                $qty = max(0.1, (float)($it['quantity'] ?? 1));
                                $sub = round($qty * $unitPrice, 2);
                                $subtotalCalc += $sub;

                                OrderItem::create([
                                    'order_id' => $order->id,
                                    'service_id' => $service->id,
                                    'item_name' => $service->name_am ?: $service->name_en,
                                    'quantity' => $qty,
                                    'unit_price' => $unitPrice,
                                    'subtotal' => $sub,
                                ]);
                            }
                        }

                        $order->subtotal = $subtotalCalc;
                        $order->total = $subtotalCalc;
                        $order->save();

                        AuditLog::logAction(
                            $request->user()?->id,
                            "synced_create_{$uuid}",
                            Order::class,
                            $order->id,
                            null,
                            array_merge($order->toArray(), ['client_uuid' => $uuid])
                        );

                        $isSuccess = true;
                        $resultEntityId = $order->id;
                        $resultPayload = ['order_number' => $order->order_number];

                        $synced[] = [
                            'client_uuid' => $uuid,
                            'status' => 'synced',
                            'entity_id' => $order->id,
                            'order_number' => $order->order_number,
                        ];
                    });
                } elseif (($entityType === 'order' || $entityType === 'job_action') && ($action === 'status_change' || $action === 'job_action' || $action === 'update')) {
                    // Explicit role check: Sales and Finance roles cannot modify orders
                    if ($user && ($user->role === 'sales' || $user->role === 'finance')) {
                        throw new \Exception('Unauthorized: Sales and Finance roles are not permitted to modify orders.');
                    }

                    $order = Order::find($payload['order_id'] ?? $payload['id'] ?? null);
                    if (!$order) {
                        throw new \Exception('Order not found on server');
                    }

                    // Enforce cleaner team isolation
                    if ($user && $user->role === 'cleaner') {
                        $cleanerTeamIds = DB::table('team_members')->where('user_id', $user->id)->pluck('cleaning_team_id')
                            ->merge(DB::table('cleaning_teams')->where('team_leader_id', $user->id)->pluck('id'))
                            ->unique();

                        if (!$order->assigned_team_id || !$cleanerTeamIds->contains($order->assigned_team_id)) {
                            throw new \Exception('Unauthorized. Cleaners cannot modify orders belonging to another team.');
                        }
                    }

                    // Strict status validation - reject arbitrary or unrecognized statuses
                    $incomingStatus = $payload['order_status'] ?? $payload['action'] ?? null;
                    if ($incomingStatus === 'complete') $incomingStatus = 'completed';
                    if ($incomingStatus === 'start') $incomingStatus = 'cleaning';

                    $allowedStatuses = ['new', 'confirmed', 'assigned', 'on_the_way', 'cleaning', 'completed', 'cancelled', 'rescheduled', 'issue'];
                    if ($incomingStatus && !in_array($incomingStatus, $allowedStatuses, true)) {
                        throw new \Exception("Invalid or unrecognized order status '{$incomingStatus}'.");
                    }

                    if ($user && $user->role === 'cleaner' && $incomingStatus) {
                        $cleanerPermittedStatuses = ['assigned', 'on_the_way', 'cleaning', 'completed', 'issue'];
                        if (!in_array($incomingStatus, $cleanerPermittedStatuses, true)) {
                            throw new \Exception("Cleaners are not permitted to set order status to '{$incomingStatus}'.");
                        }
                    }

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

                        $isSuccess = true;
                        $resultEntityId = $order->id;
                        $resultPayload = ['conflict' => true, 'conflict_id' => $conflict->id];
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

                    $isSuccess = true;
                    $resultEntityId = $order->id;

                    $synced[] = [
                        'client_uuid' => $uuid,
                        'status' => 'synced',
                        'entity_id' => $order->id,
                    ];
                } elseif ($entityType === 'sales_visit') {
                    // Explicit role check: Sales, Owner, or Admin only
                    $isSales = $user && $user->role === 'sales';
                    $isOwner = $user && ($user->role === 'owner' || $user->role === 'admin');

                    if (! $isSales && ! $isOwner) {
                        throw new \Exception('Unauthorized: Only Sales or Owner accounts can record sales visits.');
                    }

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

                    $isSuccess = true;
                    $resultEntityId = $visit->id;

                    $synced[] = [
                        'client_uuid' => $uuid,
                        'status' => 'synced',
                        'entity_id' => $visit->id,
                    ];
                } elseif ($entityType === 'expense') {
                    // Explicit role check: Finance, Owner, or Admin only
                    $isFinance = $user && $user->role === 'finance';
                    $isOwner = $user && ($user->role === 'owner' || $user->role === 'admin');

                    if (! $isFinance && ! $isOwner) {
                        throw new \Exception('Unauthorized: Only Finance or Owner accounts can record business expenses.');
                    }

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

                    $isSuccess = true;
                    $resultEntityId = $expense->id;

                    $synced[] = [
                        'client_uuid' => $uuid,
                        'status' => 'synced',
                        'entity_id' => $expense->id,
                    ];
                } else {
                    throw new \Exception("Unauthorized or unrecognized entity/action: {$entityType}/{$action}");
                }
            } catch (\Throwable $e) {
                $errors[] = [
                    'client_uuid' => $uuid,
                    'error' => $e->getMessage(),
                ];
            } finally {
                if ($idempRecord) {
                    if ($isSuccess) {
                        $idempRecord->update([
                            'status' => 'completed',
                            'entity_id' => $resultEntityId,
                            'response_payload' => $resultPayload,
                            'lock_token' => null,
                        ]);
                    } else {
                        // Mark failed so subsequent retries are allowed
                        $idempRecord->update([
                            'status' => 'failed',
                            'lock_token' => null,
                        ]);
                    }
                }
                $cacheLock->release();
            }
        }

        return response()->json([
            'synced_count' => count($synced),
            'conflict_count' => count($conflicts),
            'error_count' => count($errors),
            'in_progress_count' => count($inProgress),
            'synced' => $synced,
            'conflicts' => $conflicts,
            'errors' => $errors,
            'in_progress' => $inProgress,
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
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['message' => 'Conflict resolved successfully', 'conflict' => $conflict]);
    }
}
