<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Followup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Service;
use App\Services\EthiopianCalendarService;
use App\Services\SecurityTokenService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()->with([
            'customer:id,customer_code,full_name,phone,subcity,address',
            'assignedTeam:id,team_name,phone',
            'items:id,order_id,service_id,item_name,quantity,unit_price,subtotal',
            'items.service:id,name_en,name_am,code',
        ]);

        $filterTeamId = $request->input('assigned_team_id') ?: $request->input('team_id');

        // Cleaners: Strict access limit to ONLY their own team's jobs
        $user = $request->user();
        if ($user && $user->isCleaner()) {
            $teamIds = DB::table('team_members')->where('user_id', $user->id)->pluck('cleaning_team_id')
                ->merge(DB::table('cleaning_teams')->where('team_leader_id', $user->id)->pluck('id'))
                ->unique();
            if ($teamIds->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('assigned_team_id', $teamIds);
            }
        } elseif ($filterTeamId) {
            $query->where('assigned_team_id', $filterTeamId);
        }

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Filters
        $status = $request->input('status') ?: $request->input('order_status');
        if ($status) {
            $query->where('order_status', $status);
        }

        if ($source = $request->input('source')) {
            if ($source === 'walk_in' || $source === 'walkin') {
                $query->whereIn('source', ['walk_in', 'walkin']);
            } else {
                $query->where('source', $source);
            }
        }

        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($dateFilter = $request->input('date')) {
            if ($dateFilter === 'today') {
                $query->whereDate('appointment_date', Carbon::today());
            } elseif ($dateFilter === 'tomorrow') {
                $query->whereDate('appointment_date', Carbon::tomorrow());
            } elseif ($dateFilter === 'this_week') {
                $query->whereBetween('appointment_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            } else {
                $query->whereDate('appointment_date', $dateFilter);
            }
        }

        $perPage = $request->boolean('all') ? 200 : min(max((int)$request->input('per_page', 20), 1), 200);
        $orders = $query->latest('appointment_date')->paginate($perPage);

        // Append Ethiopian calendar formatted strings and alias fields
        $orders->getCollection()->transform(function ($order) {
            $eth = EthiopianCalendarService::toEthiopian($order->appointment_date);
            $order->eth_appointment_date = $eth['formatted_am'];
            $order->eth_appointment_en = $eth['formatted_en'];
            $order->customer_name = $order->customer?->full_name ?? $order->customer?->name ?? 'ደንበኛ';
            $order->customer_phone = $order->customer?->phone ?? '';
            $order->status = $order->order_status;
            $order->team_id = $order->assigned_team_id;
            return $order;
        });

        return response()->json($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user && $user->isCleaner()) {
            $teamIds = DB::table('team_members')->where('user_id', $user->id)->pluck('cleaning_team_id')
                ->merge(DB::table('cleaning_teams')->where('team_leader_id', $user->id)->pluck('id'))
                ->unique();
            if (!$order->assigned_team_id || !$teamIds->contains($order->assigned_team_id)) {
                return response()->json(['message' => 'Unauthorized. You can only view jobs assigned to your team.'], 403);
            }
        }

        $order->load([
            'customer',
            'creator:id,name,role',
            'assignedTeam.leader:id,name,phone',
            'assignedTeam.members.user:id,name,phone',
            'items.service',
            'appointments.team',
            'payments.recordedBy:id,name',
            'followups.handledBy:id,name',
            'feedbacks',
            'complaints.assignedTo:id,name',
        ]);

        $ethAppt = EthiopianCalendarService::toEthiopian($order->appointment_date);
        $ethCreated = EthiopianCalendarService::toEthiopian($order->created_at);

        $orderData = $order->toArray();
        $orderData['eth_appointment_date'] = $ethAppt['formatted_am'];
        $orderData['eth_appointment_en'] = $ethAppt['formatted_en'];
        $orderData['eth_created_date'] = $ethCreated['formatted_am'];

        return response()->json($orderData);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user && !$user->isOwner() && !$user->isReception()) {
            return response()->json([
                'message' => 'Unauthorized. Only owner or reception may create orders.',
            ], 403);
        }

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            // Inline customer attributes (aliases supported)
            'customer_name' => 'nullable|string|max:255',
            'full_name' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:32',
            'phone' => 'nullable|string|max:32',
            'customer_address' => 'nullable|string',
            'customer_subcity' => 'nullable|string|max:64',
            'customer_woreda' => 'nullable|string|max:32',
            'customer_house_no' => 'nullable|string|max:32',
            'customer_landmark' => 'nullable|string',
            
            // Order details
            'source' => 'nullable|string|in:website,telegram_bot,telegram_inline,telegram_miniapp,phone,walkin,walk_in,internal,corporate_contract',
            'order_status' => 'nullable|string|in:new,pending_confirmation,confirmed,assigned,draft',
            'is_draft' => 'nullable|boolean',
            'appointment_date' => 'nullable|date',
            'appointment_time_slot' => 'nullable|string|max:64',
            'address' => 'nullable|string',
            'subcity' => 'nullable|string|max:64',
            'woreda' => 'nullable|string|max:32',
            'house_no' => 'nullable|string|max:32',
            'landmark' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'assigned_team_id' => 'nullable|exists:cleaning_teams,id',
            'team_id' => 'nullable|exists:cleaning_teams,id',
            'allow_price_override' => 'nullable|boolean',
            'price_override' => 'nullable|boolean',
            
            // MULTI-ITEM SUPPORT (Array of items)
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.item_name' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.price_override' => 'nullable|boolean',
            'items.*.notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $user = $request->user();

            // 1. Resolve or create customer reliably
            $rawPhone = $validated['customer_phone'] ?? $validated['phone'] ?? $request->input('phone') ?? $request->input('customer_phone');
            $customerName = $validated['customer_name'] ?? $validated['full_name'] ?? $validated['name'] ?? $request->input('customer_name') ?? $request->input('full_name') ?? 'ደንበኛ';
            $address = $validated['address'] ?? $validated['customer_address'] ?? $request->input('address') ?? $request->input('customer_address') ?? 'አዲስ አበባ';
            $subcity = $validated['subcity'] ?? $validated['customer_subcity'] ?? $request->input('subcity') ?? $request->input('customer_subcity') ?? null;
            $woreda = $validated['woreda'] ?? $validated['customer_woreda'] ?? $request->input('woreda') ?? null;
            $houseNo = $validated['house_no'] ?? $validated['customer_house_no'] ?? $request->input('house_no') ?? null;
            $landmark = $validated['landmark'] ?? $validated['customer_landmark'] ?? $request->input('landmark') ?? null;

            if (!empty($validated['customer_id'])) {
                $customer = Customer::findOrFail($validated['customer_id']);
            } else {
                $normalizedPhone = $rawPhone ? SecurityTokenService::normalizePhone($rawPhone) : '0900000000';
                $customer = Customer::firstOrCreate(
                    ['phone' => $normalizedPhone],
                    [
                        'customer_code' => Customer::generateNextCode(),
                        'full_name' => $customerName,
                        'address' => $address,
                        'subcity' => $subcity,
                        'woreda' => $woreda,
                        'house_no' => $houseNo,
                        'landmark' => $landmark,
                    ]
                );

                if (($customer->full_name === 'ደንበኛ' || empty($customer->full_name)) && $customerName !== 'ደንበኛ') {
                    $customer->update(['full_name' => $customerName]);
                }
            }

            // 2. Resolve team assignment
            $teamId = $validated['assigned_team_id'] ?? $validated['team_id'] ?? $request->input('assigned_team_id') ?? $request->input('team_id');
            $orderNumber = Order::generateNextNumber();
            $status = !empty($teamId) ? 'assigned' : 'new';
            if ($request->boolean('is_draft') || in_array($request->input('order_status'), ['draft', 'pending_confirmation'])) {
                $status = 'pending_confirmation';
            }
            $orderSource = in_array($validated['source'] ?? '', ['walkin', 'walk_in']) ? 'walk_in' : ($validated['source'] ?? 'phone');

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'created_by_user_id' => $request->user()?->id,
                'assigned_team_id' => $teamId ?: null,
                'source' => $orderSource,
                'order_status' => $status,
                'payment_status' => 'unpaid',
                'subtotal' => 0,
                'discount' => $validated['discount'] ?? 0,
                'tax' => 0,
                'total' => 0,
                'appointment_date' => $validated['appointment_date'] ?? Carbon::today(),
                'appointment_time_slot' => $validated['appointment_time_slot'] ?? 'morning',
                'address' => $address,
                'subcity' => $subcity ?? $customer->subcity,
                'woreda' => $woreda,
                'house_no' => $houseNo,
                'landmark' => $landmark,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'version' => 1,
            ]);

            if (!empty($validated['latitude']) && !empty($validated['longitude'])) {
                $customer->update([
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                ]);
            }

            // 3. Create Order Items (Authoritative service lookup with owner override check)
            $isOwner = $user && method_exists($user, 'isOwner') && $user->isOwner();
            $globalOverride = $request->boolean('allow_price_override') || $request->boolean('price_override');

            foreach ($validated['items'] as $itemData) {
                $service = Service::whereKey($itemData['service_id'])->where('is_active', true)->firstOrFail();
                $authoritativeUnitPrice = (float)$service->base_price;
                $clientUnitPrice = isset($itemData['unit_price']) ? (float)$itemData['unit_price'] : $authoritativeUnitPrice;

                $itemOverride = !empty($itemData['price_override']);
                $hasOverrideFlag = $globalOverride || $itemOverride;

                $finalUnitPrice = $authoritativeUnitPrice;
                $isPriceOverridden = false;

                if ($isOwner && $hasOverrideFlag && abs($clientUnitPrice - $authoritativeUnitPrice) > 0.001) {
                    $finalUnitPrice = $clientUnitPrice;
                    $isPriceOverridden = true;
                }

                $subtotal = round((float)$itemData['quantity'] * $finalUnitPrice, 2);
                $itemName = !empty($itemData['item_name']) ? $itemData['item_name'] : ($service->name_am ?: $service->name_en);

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'service_id' => $service->id,
                    'item_name' => $itemName,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $finalUnitPrice,
                    'subtotal' => $subtotal,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                if ($isPriceOverridden) {
                    AuditLog::logAction(
                        $user?->id,
                        'price_override',
                        OrderItem::class,
                        $orderItem->id,
                        ['base_price' => $authoritativeUnitPrice],
                        [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'service_id' => $service->id,
                            'service_name' => $service->name_en,
                            'base_price' => $authoritativeUnitPrice,
                            'overridden_unit_price' => $finalUnitPrice,
                            'quantity' => $itemData['quantity'],
                            'reason' => 'Authorized owner price override applied during order creation',
                        ]
                    );
                }
            }

            // 4. Create Appointment if appointment_date is provided and one does not already exist
            if (!empty($order->appointment_date)) {
                $existingAppointment = Appointment::where('order_id', $order->id)->first();
                if (!$existingAppointment) {
                    [$startTime, $endTime] = $this->parseTimeSlot($order->appointment_time_slot);

                    $notesParts = [];
                    if (!empty($order->appointment_time_slot)) {
                        $notesParts[] = "Slot: {$order->appointment_time_slot}";
                    }
                    $location = trim(($order->subcity ? "{$order->subcity}, " : '') . ($order->address ?? ''));
                    if ($location) {
                        $notesParts[] = "Location: {$location}";
                    }
                    if (!empty($order->notes)) {
                        $notesParts[] = "Notes: {$order->notes}";
                    }

                    Appointment::create([
                        'order_id' => $order->id,
                        'customer_id' => $customer->id,
                        'cleaning_team_id' => $order->assigned_team_id,
                        'appointment_date' => $order->appointment_date,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => 'scheduled',
                        'notes' => !empty($notesParts) ? implode(' | ', $notesParts) : "Slot: {$order->appointment_time_slot}",
                    ]);
                }
            }

            AuditLog::logAction(
                $request->user()?->id,
                'created',
                Order::class,
                $order->id,
                null,
                $order->load('items')->toArray()
            );

            // Send instant booking confirmation SMS to customer mobile
            try {
                SmsService::sendBookingConfirmation($order);
            } catch (\Throwable $th) {
                Log::warning('Booking confirmation SMS failed: ' . $th->getMessage());
            }

            // If a cleaning team was assigned right away, notify the team
            if ($order->assigned_team_id) {
                try {
                    SmsService::sendTeamDispatched($order);
                } catch (\Throwable $th) {
                    Log::warning('Team dispatched notification failed: ' . $th->getMessage());
                }
            }

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order->fresh(['customer', 'items', 'assignedTeam']),
            ], 201);
        });
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user && !$user->isOwner() && !$user->isReception()) {
            return response()->json([
                'message' => 'Unauthorized. Only owner or reception may update orders.',
            ], 403);
        }

        $validated = $request->validate([
            'assigned_team_id' => 'nullable|exists:cleaning_teams,id',
            'team_id' => 'nullable|exists:cleaning_teams,id',
            'order_status' => 'nullable|string',
            'status' => 'nullable|string',
            'appointment_date' => 'nullable|date',
            'appointment_time_slot' => 'nullable|string',
            'notes' => 'nullable|string',
            'address' => 'nullable|string',
            'subcity' => 'nullable|string',
        ]);

        $teamId = $validated['assigned_team_id'] ?? $validated['team_id'] ?? $request->input('assigned_team_id') ?? $request->input('team_id');
        $status = $validated['order_status'] ?? $validated['status'] ?? $request->input('order_status') ?? $request->input('status');

        $old = $order->toArray();

        if ($teamId !== null) {
            $order->assigned_team_id = $teamId ?: null;
            if ($teamId && in_array($order->order_status, ['new', 'pending_confirmation', 'confirmed'])) {
                $order->order_status = 'assigned';
            }
        }

        if ($status !== null) {
            $order->order_status = $status;
        }

        if (!empty($validated['appointment_date'])) {
            $order->appointment_date = $validated['appointment_date'];
        }

        if (!empty($validated['appointment_time_slot'])) {
            $order->appointment_time_slot = $validated['appointment_time_slot'];
        }

        if (isset($validated['notes'])) {
            $order->notes = $validated['notes'];
        }

        if (isset($validated['address'])) {
            $order->address = $validated['address'];
        }

        if (isset($validated['subcity'])) {
            $order->subcity = $validated['subcity'];
        }

        $order->version += 1;
        $order->save();

        if ($order->assigned_team_id) {
            Appointment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'customer_id' => $order->customer_id,
                    'cleaning_team_id' => $order->assigned_team_id,
                    'appointment_date' => $order->appointment_date,
                    'status' => 'scheduled',
                ]
            );

            try {
                SmsService::sendTeamDispatched($order);
            } catch (\Throwable $th) {
                Log::warning('Team dispatched notification failed: ' . $th->getMessage());
            }
        }

        if ($order->order_status === 'completed' && ($old['order_status'] ?? '') !== 'completed') {
            if (!$order->completed_at) {
                $order->completed_at = now();
                $order->save();
            }
            try {
                $sentViaTelegram = TelegramController::sendOrderReviewRequest($order);
                if (!$sentViaTelegram) {
                    SmsService::sendJobCompleted($order);
                }
            } catch (\Throwable $th) {
                Log::warning('Review request failed on order update: ' . $th->getMessage());
            }
        }

        AuditLog::logAction(
            $request->user()?->id,
            'updated',
            Order::class,
            $order->id,
            $old,
            $order->toArray()
        );

        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $order->fresh(['customer', 'assignedTeam', 'items']),
        ]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Enforce cleaner team isolation and permissible statuses
        if ($user && $user->isCleaner()) {
            $cleanerTeamIds = DB::table('team_members')->where('user_id', $user->id)->pluck('cleaning_team_id')
                ->merge(DB::table('cleaning_teams')->where('team_leader_id', $user->id)->pluck('id'))
                ->unique();

            if (!$order->assigned_team_id || !$cleanerTeamIds->contains($order->assigned_team_id)) {
                return response()->json([
                    'message' => 'Unauthorized. You can only update jobs assigned to your team.',
                ], 403);
            }

            $allowedCleanerStatuses = ['on_the_way', 'cleaning', 'completed', 'problem_reported'];
            if (!in_array($request->input('order_status'), $allowedCleanerStatuses)) {
                return response()->json([
                    'message' => 'Unauthorized. Cleaners cannot set status to ' . $request->input('order_status'),
                ], 403);
            }
        }

        $validated = $request->validate([
            'order_status' => 'required|string|in:new,pending_confirmation,confirmed,assigned,on_the_way,cleaning,completed,paid,follow_up,cancelled,rescheduled,no_show,problem_reported',
            'notes' => 'nullable|string',
            'cancellation_reason' => 'nullable|string',
            'completion_notes' => 'nullable|string',
            'client_version' => 'nullable|integer',
        ]);

        $old = $order->toArray();

        // Optimistic concurrency check if provided
        if (isset($validated['client_version']) && $validated['client_version'] < $order->version) {
            // Version mismatch - potential conflict
            return response()->json([
                'conflict' => true,
                'message' => 'Conflict detected. The order was modified by another session.',
                'server_order' => $order->load('items', 'customer'),
            ], 409);
        }

        $order->order_status = $validated['order_status'];
        if (!empty($validated['notes'])) {
            $order->notes = ($order->notes ? $order->notes . "\n" : '') . $validated['notes'];
        }
        if (!empty($validated['cancellation_reason'])) {
            $order->cancellation_reason = $validated['cancellation_reason'];
        }
        if (!empty($validated['completion_notes'])) {
            $order->completion_notes = $validated['completion_notes'];
        }

        if ($validated['order_status'] === 'completed' && !$order->completed_at) {
            $order->completed_at = now();

            // Auto schedule next-day follow-up
            Followup::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'customer_id' => $order->customer_id,
                    'due_date' => Carbon::tomorrow(),
                    'status' => 'pending',
                    'notes' => 'Next-day customer satisfaction inquiry',
                ]
            );
        }

        $order->version += 1;
        $order->save();

        AuditLog::logAction(
            $request->user()?->id,
            'status_changed',
            Order::class,
            $order->id,
            $old,
            $order->toArray()
        );

        // Send customer SMS on key milestones
        if ($validated['order_status'] === 'on_the_way') {
            try {
                SmsService::sendTeamDispatched($order);
            } catch (\Throwable $th) {
                Log::warning('Team dispatched SMS failed: ' . $th->getMessage());
            }
        } elseif ($validated['order_status'] === 'completed') {
            try {
                $sentViaTelegram = TelegramController::sendOrderReviewRequest($order);
                if (!$sentViaTelegram) {
                    SmsService::sendJobCompleted($order);
                }
            } catch (\Throwable $th) {
                Log::warning('Job completed notification failed: ' . $th->getMessage());
            }
        }

        return response()->json([
            'message' => "Order status updated to {$order->order_status}",
            'order' => $order->fresh(['customer', 'assignedTeam', 'items']),
        ]);
    }

    public function assignTeam(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'assigned_team_id' => 'nullable',
            'team_id' => 'nullable',
            'worker_id' => 'nullable|exists:users,id',
            'appointment_date' => 'nullable|date',
            'appointment_time_slot' => 'nullable|string',
        ]);

        $teamId = $validated['assigned_team_id'] ?? $validated['team_id'] ?? null;
        $workerId = $validated['worker_id'] ?? null;

        // If a specific worker was selected directly
        if ($workerId && !$teamId) {
            $member = \App\Models\TeamMember::where('user_id', $workerId)->first();
            $leaderTeam = \App\Models\CleaningTeam::where('team_leader_id', $workerId)->first();
            $teamId = $member?->cleaning_team_id ?? $leaderTeam?->id;

            if (!$teamId) {
                $worker = \App\Models\User::find($workerId);
                $team = \App\Models\CleaningTeam::create([
                    'team_name' => $worker ? $worker->name : "ሰራተኛ #{$workerId}",
                    'team_leader_id' => $workerId,
                    'phone' => $worker?->phone,
                    'status' => 'active',
                ]);
                \App\Models\TeamMember::firstOrCreate([
                    'cleaning_team_id' => $team->id,
                    'user_id' => $workerId,
                ], [
                    'role_in_team' => 'cleaner',
                ]);
                $teamId = $team->id;
            }
        }

        $old = $order->toArray();

        $order->assigned_team_id = $teamId;
        if (!empty($validated['appointment_date'])) {
            $order->appointment_date = $validated['appointment_date'];
        }
        if (!empty($validated['appointment_time_slot'])) {
            $order->appointment_time_slot = $validated['appointment_time_slot'];
        }

        if (in_array($order->order_status, ['new', 'pending_confirmation', 'confirmed'])) {
            $order->order_status = 'assigned';
        }

        $order->version += 1;
        $order->save();

        // Update or create appointment
        if ($order->assigned_team_id) {
            Appointment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'customer_id' => $order->customer_id,
                    'cleaning_team_id' => $order->assigned_team_id,
                    'appointment_date' => $order->appointment_date,
                    'status' => 'scheduled',
                ]
            );
        }

        AuditLog::logAction(
            $request->user()?->id,
            'team_assigned',
            Order::class,
            $order->id,
            $old,
            $order->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'ሰራተኛው/ቡድኑ ለትዕዛዙ በተሳካ ሁኔታ ተመድቧል::',
            'order' => $order->fresh(['customer', 'assignedTeam', 'items']),
        ]);
    }

    public function recordPayment(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:cash,telebirr,cbe,cbe_birr,bank_transfer,check',
            'reference_number' => 'nullable|string|max:64',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $order, $validated) {
            // Serialise payment creation for this order, preventing two devices
            // from both accepting the same remaining balance at once.
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $alreadyPaid = (float)$lockedOrder->payments()->sum('amount');
            $remaining = max(0, (float)$lockedOrder->total - $alreadyPaid);
            if ((float)$validated['amount'] > $remaining + 0.00001) {
                return response()->json([
                    'message' => 'Payment exceeds the outstanding balance.',
                    'outstanding_balance' => round($remaining, 2),
                ], 422);
            }

            $method = ($validated['payment_method'] === 'cbe_birr') ? 'cbe' : $validated['payment_method'];

            $payment = Payment::create([
                'payment_number' => Payment::generateNextNumber(),
                'order_id' => $lockedOrder->id,
                'customer_id' => $lockedOrder->customer_id,
                'amount' => $validated['amount'],
                'payment_method' => $method,
                'reference_number' => $validated['reference_number'] ?? null,
                'payment_date' => $validated['payment_date'] ?? Carbon::today(),
                'recorded_by_user_id' => $request->user()?->id,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Automatically synchronize order payment status
            $newTotalPaid = $alreadyPaid + (float)$validated['amount'];
            if ($newTotalPaid >= (float)$lockedOrder->total - 0.01) {
                $lockedOrder->payment_status = 'paid';
            } elseif ($newTotalPaid > 0) {
                $lockedOrder->payment_status = 'partially_paid';
            }
            $lockedOrder->save();

            AuditLog::logAction(
                $request->user()?->id,
                'payment_recorded',
                Payment::class,
                $payment->id,
                null,
                $payment->toArray()
            );

            return response()->json([
                'message' => 'Payment recorded successfully',
                'payment' => $payment,
                'order' => $lockedOrder->fresh(['payments']),
            ], 201);
        });
    }

    /**
     * Cleaner team submits collected payment for Owner/Reception approval.
     * Both Cash and Transfer MUST result in payment_status = 'pending_approval'.
     */
    public function submitPayment(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Enforce cleaner team isolation if caller is cleaner
        if ($user && $user->isCleaner()) {
            $cleanerTeamIds = DB::table('team_members')->where('user_id', $user->id)->pluck('cleaning_team_id')
                ->merge(DB::table('cleaning_teams')->where('team_leader_id', $user->id)->pluck('id'))
                ->unique();

            if (!$order->assigned_team_id || !$cleanerTeamIds->contains($order->assigned_team_id)) {
                return response()->json([
                    'message' => 'Unauthorized. You can only submit payment for jobs assigned to your team.',
                ], 403);
            }
        }

        $validated = $request->validate([
            'payment_method' => 'required|string|in:cash,telebirr,cbe,cbe_birr,bank_transfer',
            'payment_reference' => 'nullable|string|max:64',
            'reference_number' => 'nullable|string|max:64',
            'amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $method = ($validated['payment_method'] === 'cbe_birr') ? 'cbe' : $validated['payment_method'];
        $ref = $validated['payment_reference'] ?? $validated['reference_number'] ?? null;
        $amount = !empty($validated['amount']) ? (float)$validated['amount'] : (float)$order->total;

        $old = $order->toArray();

        // CRITICAL: Regardless of cash or transfer, payment_status is ALWAYS pending_approval!
        $order->payment_status = 'pending_approval';
        $order->payment_method = $method;
        $order->payment_reference = $ref;
        $order->payment_submitted_at = now();
        $order->payment_submitted_by_user_id = $user?->id;

        $methodLabel = $method === 'cash' ? 'ጥሬ ገንዘብ (Cash)' : strtoupper($method);
        $noteText = "ክፍያ ለመጽደቅ ቀርቧል: {$methodLabel}" . ($ref ? " [Ref: {$ref}]" : '') . " - " . number_format($amount, 2) . " ETB";
        if (!empty($validated['notes'])) {
            $noteText .= " | " . $validated['notes'];
        }
        $order->notes = ($order->notes ? $order->notes . "\n" : '') . $noteText;
        $order->version += 1;
        $order->save();

        AuditLog::logAction(
            $user?->id,
            'payment_submitted_for_approval',
            Order::class,
            $order->id,
            $old,
            [
                'payment_status' => 'pending_approval',
                'payment_method' => $method,
                'payment_reference' => $ref,
                'amount' => $amount,
                'submitted_by' => $user?->name,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'የክፍያ መረጃው በተሳካ ሁኔታ ለመጽደቅ ተልኳል (Payment submitted for approval).',
            'order' => $order->fresh(['customer', 'assignedTeam', 'items']),
        ]);
    }

    /**
     * Owner & Reception approve a pending payment (or record verified direct payment).
     * Sets payment_status = 'paid' and creates the official Payment record.
     */
    public function approvePayment(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user && !$user->isOwner() && !$user->isReception()) {
            return response()->json([
                'message' => 'Unauthorized. Only owner or reception may approve payments.',
            ], 403);
        }

        $validated = $request->validate([
            'payment_method' => 'nullable|string|in:cash,telebirr,cbe,cbe_birr,bank_transfer,check',
            'reference_number' => 'nullable|string|max:64',
            'payment_reference' => 'nullable|string|max:64',
            'amount' => 'nullable|numeric|min:0.01',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $order, $validated, $user) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            $alreadyPaid = (float)$lockedOrder->payments()->sum('amount');
            $remaining = max(0, (float)$lockedOrder->total - $alreadyPaid);

            $amount = !empty($validated['amount']) ? (float)$validated['amount'] : ($remaining > 0 ? $remaining : (float)$lockedOrder->total);
            $method = $validated['payment_method'] ?? $lockedOrder->payment_method ?? 'cash';
            if ($method === 'cbe_birr') {
                $method = 'cbe';
            }
            $ref = $validated['reference_number'] ?? $validated['payment_reference'] ?? $lockedOrder->payment_reference ?? null;

            // Create the official Payment record that impacts FinanceController revenue immediately
            $payment = Payment::create([
                'payment_number' => Payment::generateNextNumber(),
                'order_id' => $lockedOrder->id,
                'customer_id' => $lockedOrder->customer_id,
                'amount' => $amount,
                'payment_method' => $method,
                'reference_number' => $ref,
                'payment_date' => $validated['payment_date'] ?? Carbon::today(),
                'recorded_by_user_id' => $user?->id,
                'notes' => $validated['notes'] ?? ("በ{$user?->name} የጸደቀ ክፍያ"),
            ]);

            $old = $lockedOrder->toArray();

            $lockedOrder->payment_status = 'paid';
            $lockedOrder->payment_method = $method;
            $lockedOrder->payment_reference = $ref;
            $lockedOrder->payment_approved_at = now();
            $lockedOrder->payment_approved_by_user_id = $user?->id;
            $lockedOrder->version += 1;
            $lockedOrder->save();

            AuditLog::logAction(
                $user?->id,
                'payment_approved',
                Order::class,
                $lockedOrder->id,
                $old,
                [
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'payment_status' => 'paid',
                    'payment_method' => $method,
                    'amount' => $amount,
                    'approved_by' => $user?->name,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'ክፍያው በተሳካ ሁኔታ ጸድቋል (Payment approved and recorded).',
                'order' => $lockedOrder->fresh(['customer', 'assignedTeam', 'items', 'payments']),
                'payment' => $payment,
            ]);
        });
    }

    /**
     * Owner & Reception reject a pending payment and request recheck.
     */
    public function rejectPayment(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user && !$user->isOwner() && !$user->isReception()) {
            return response()->json([
                'message' => 'Unauthorized. Only owner or reception may reject payments.',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $old = $order->toArray();
        $reason = $validated['reason'] ?? 'ክፍያው እንደገና እንዲጣራ ተመልሷል (Recheck requested)';

        $order->payment_status = 'unpaid';
        $order->notes = ($order->notes ? $order->notes . "\n" : '') . "[ክፍያ ውድቅ የተደረገበት ምክንያት: {$reason} - በ{$user?->name}]";
        $order->version += 1;
        $order->save();

        AuditLog::logAction(
            $user?->id,
            'payment_rejected',
            Order::class,
            $order->id,
            $old,
            [
                'payment_status' => 'unpaid',
                'reason' => $reason,
                'rejected_by' => $user?->name,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'ክፍያው ውድቅ ተደርጓል፤ እንደገና እንዲጣራ ተመልሷል::',
            'order' => $order->fresh(['customer', 'assignedTeam', 'items']),
        ]);
    }

    /**
     * Postpones / Reschedules an order with SMS & Telegram notifications to the customer
     */
    public function postpone(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user && !$user->isOwner() && !$user->isReception()) {
            return response()->json([
                'message' => 'Unauthorized. Only owner or reception may postpone or reschedule orders.',
            ], 403);
        }

        $validated = $request->validate([
            'new_appointment_date' => 'required|date',
            'new_time_slot' => 'nullable|string',
            'reason' => 'required|string|max:500',
            'assigned_team_id' => 'nullable|exists:cleaning_teams,id',
        ]);

        $old = $order->toArray();
        $oldDate = $order->appointment_date ? (is_string($order->appointment_date) ? $order->appointment_date : $order->appointment_date->format('Y-m-d')) : 'ያልተገለጸ';
        $oldSlot = $order->appointment_time_slot ?? 'ጠዋት';

        $newDate = $validated['new_appointment_date'];
        $newSlot = $validated['new_time_slot'] ?? $order->appointment_time_slot ?? 'ጠዋት (ከ 2:00 - 6:00)';
        $reason = $validated['reason'];

        $order->appointment_date = $newDate;
        $order->appointment_time_slot = $newSlot;
        $order->order_status = 'rescheduled';
        if (!empty($validated['assigned_team_id'])) {
            $order->assigned_team_id = $validated['assigned_team_id'];
        }

        $userLabel = $request->user()?->name ?? 'የሜሽ ሰራተኛ';
        $postponeNote = "ቀጠሮ ተላልፏል (Postponed): ከ {$oldDate} ({$oldSlot}) ወደ {$newDate} ({$newSlot}) | ምክንያት: {$reason} | በ: {$userLabel}";
        $order->notes = ($order->notes ? $order->notes . "\n" : '') . $postponeNote;
        $order->version += 1;
        $order->save();

        // Update Appointment
        Appointment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'customer_id' => $order->customer_id,
                'cleaning_team_id' => $order->assigned_team_id,
                'appointment_date' => $newDate,
                'status' => 'rescheduled',
                'notes' => $postponeNote,
            ]
        );

        // Audit Log
        AuditLog::logAction(
            $request->user()?->id,
            'order_postponed',
            Order::class,
            $order->id,
            $old,
            $order->toArray()
        );

        // 1. Dispatch SMS Notification to Customer
        $smsResult = null;
        try {
            $smsResult = SmsService::sendOrderPostponed($order, $reason, $newDate, $newSlot);
        } catch (\Throwable $th) {
            Log::warning('Postpone SMS failed: ' . $th->getMessage());
        }

        // 2. Dispatch Telegram Notification to Customer
        $tgResult = false;
        try {
            $tgController = app(\App\Http\Controllers\Api\TelegramController::class);
            $tgResult = $tgController->sendOrderPostponedMessage($order, $reason, $newDate, $newSlot);
        } catch (\Throwable $th) {
            Log::warning('Postpone Telegram failed: ' . $th->getMessage());
        }

        return response()->json([
            'message' => "የትዕዛዝ ቁጥር {$order->order_number} ቀጠሮ በተሳካ ሁኔታ ወደ {$newDate} ተላልፏል:: ደንበኛው በ SMS እና በቴሌግራም ተገልጾለታል::",
            'order' => $order->fresh(['customer', 'assignedTeam', 'items']),
            'sms_sent' => $smsResult['ok'] ?? false,
            'telegram_sent' => $tgResult,
        ]);
    }

    /**
     * Reception or Owner triggers review request manually
     */
    public function sendReviewRequest(Request $request, Order $order): JsonResponse
    {
        $order->loadMissing('customer');
        if (!$order->customer) {
            return response()->json([
                'success' => false,
                'message' => 'ትዕዛዙ የተገናኘ ደንበኛ የለውም (Customer not found).',
            ], 422);
        }

        $sentViaTelegram = false;
        try {
            $sentViaTelegram = TelegramController::sendOrderReviewRequest($order);
        } catch (\Throwable $th) {
            Log::warning('Manual telegram review request error: ' . $th->getMessage());
        }

        if ($sentViaTelegram) {
            return response()->json([
                'success' => true,
                'channel' => 'telegram',
                'message' => 'የ 5-ኮከብ ደረጃ መስጫ ጥያቄ በቴሌግራም ለደንበኛው ተልኳል!',
            ]);
        }

        // Fallback to direct normal phone SMS
        $smsResult = SmsService::sendReviewRequest($order);
        return response()->json([
            'success' => true,
            'channel' => 'sms',
            'message' => 'የ 5-ኮከብ ደረጃ መስጫ ጥያቄ በኤስኤምኤስ (SMS) ለደንበኛው ስልክ ተልኳል!',
            'sms' => $smsResult,
        ]);
    }

    /**
     * Parse human or standard time slots into start_time and end_time (H:i:s).
     */
    private function parseTimeSlot(?string $slot): array
    {
        if (!$slot) {
            return [null, null];
        }
        $slotLower = strtolower(trim($slot));
        if ($slotLower === 'morning') {
            return ['09:00:00', '12:00:00'];
        }
        if ($slotLower === 'afternoon') {
            return ['14:00:00', '17:00:00'];
        }
        if (preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $slot, $matches)) {
            $start = date('H:i:s', strtotime($matches[1]));
            $end = date('H:i:s', strtotime($matches[2]));
            return [$start, $end];
        }
        $parsed = strtotime($slot);
        if ($parsed !== false) {
            return [date('H:i:s', $parsed), null];
        }
        return [null, null];
    }
}
