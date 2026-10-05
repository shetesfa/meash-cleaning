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
use App\Services\EthiopianCalendarService;
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

        // Cleaners only see their assigned team's jobs
        $user = $request->user();
        if ($user && $user->isCleaner()) {
            $teamIds = DB::table('team_members')
                ->where('user_id', $user->id)
                ->pluck('cleaning_team_id');
            $query->whereIn('assigned_team_id', $teamIds);
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
        if ($status = $request->input('status')) {
            $query->where('order_status', $status);
        }

        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($teamId = $request->input('assigned_team_id')) {
            $query->where('assigned_team_id', $teamId);
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

        $orders = $query->latest('appointment_date')->paginate($request->input('per_page', 20));

        // Append Ethiopian calendar formatted strings
        $orders->getCollection()->transform(function ($order) {
            $eth = EthiopianCalendarService::toEthiopian($order->appointment_date);
            $order->eth_appointment_date = $eth['formatted_am'];
            $order->eth_appointment_en = $eth['formatted_en'];
            return $order;
        });

        return response()->json($orders);
    }

    public function show(Order $order): JsonResponse
    {
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
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            // Or create customer inline:
            'customer_name' => 'required_without:customer_id|string|max:255',
            'customer_phone' => 'required_without:customer_id|string|max:32',
            'customer_address' => 'nullable|string',
            'customer_subcity' => 'nullable|string|max:64',
            
            // Order details
            'source' => 'nullable|string|in:website,telegram_bot,telegram_inline,telegram_miniapp,phone,walkin,corporate_contract',
            'appointment_date' => 'required|date',
            'appointment_time_slot' => 'nullable|string|max:64',
            'address' => 'required|string',
            'subcity' => 'nullable|string|max:64',
            'woreda' => 'nullable|string|max:32',
            'house_no' => 'nullable|string|max:32',
            'landmark' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'assigned_team_id' => 'nullable|exists:cleaning_teams,id',
            
            // MULTI-ITEM SUPPORT (Array of items)
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'nullable|exists:services,id',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            // 1. Resolve or create customer
            if (!empty($validated['customer_id'])) {
                $customer = Customer::findOrFail($validated['customer_id']);
            } else {
                // Check if existing phone exists to avoid duplicates
                $customer = Customer::where('phone', $validated['customer_phone'])->first();
                if (!$customer) {
                    $customer = Customer::create([
                        'customer_code' => Customer::generateNextCode(),
                        'full_name' => $validated['customer_name'],
                        'phone' => $validated['customer_phone'],
                        'address' => $validated['customer_address'] ?? $validated['address'],
                        'subcity' => $validated['customer_subcity'] ?? $validated['subcity'] ?? null,
                    ]);
                }
            }

            // 2. Create Order
            $orderNumber = Order::generateNextNumber();
            $status = !empty($validated['assigned_team_id']) ? 'assigned' : 'new';

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'created_by_user_id' => $request->user()?->id,
                'assigned_team_id' => $validated['assigned_team_id'] ?? null,
                'source' => $validated['source'] ?? 'phone',
                'order_status' => $status,
                'payment_status' => 'unpaid',
                'subtotal' => 0,
                'discount' => $validated['discount'] ?? 0,
                'tax' => 0,
                'total' => 0,
                'appointment_date' => $validated['appointment_date'],
                'appointment_time_slot' => $validated['appointment_time_slot'] ?? 'morning',
                'address' => $validated['address'],
                'subcity' => $validated['subcity'] ?? $customer->subcity,
                'woreda' => $validated['woreda'] ?? null,
                'house_no' => $validated['house_no'] ?? null,
                'landmark' => $validated['landmark'] ?? null,
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

            // 3. Create Order Items (Calculates subtotals & order totals automatically)
            foreach ($validated['items'] as $itemData) {
                $subtotal = (float)$itemData['quantity'] * (float)$itemData['unit_price'];
                OrderItem::create([
                    'order_id' => $order->id,
                    'service_id' => $itemData['service_id'] ?? null,
                    'item_name' => $itemData['item_name'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $subtotal,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            // 4. Create Appointment
            Appointment::create([
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'cleaning_team_id' => $order->assigned_team_id,
                'appointment_date' => $order->appointment_date,
                'status' => 'scheduled',
                'notes' => "Slot: {$order->appointment_time_slot}",
            ]);

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

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order->fresh(['customer', 'items', 'assignedTeam']),
            ], 201);
        });
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
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
                SmsService::sendJobCompleted($order);
            } catch (\Throwable $th) {
                Log::warning('Job completed SMS failed: ' . $th->getMessage());
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
            'assigned_team_id' => 'required|exists:cleaning_teams,id',
            'appointment_date' => 'nullable|date',
            'appointment_time_slot' => 'nullable|string',
        ]);

        $old = $order->toArray();

        $order->assigned_team_id = $validated['assigned_team_id'];
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
        Appointment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'customer_id' => $order->customer_id,
                'cleaning_team_id' => $order->assigned_team_id,
                'appointment_date' => $order->appointment_date,
                'status' => 'scheduled',
            ]
        );

        AuditLog::logAction(
            $request->user()?->id,
            'team_assigned',
            Order::class,
            $order->id,
            $old,
            $order->toArray()
        );

        return response()->json([
            'message' => 'Team assigned successfully',
            'order' => $order->fresh(['customer', 'assignedTeam', 'items']),
        ]);
    }

    public function recordPayment(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:cash,telebirr,cbe_birr,bank_transfer,check',
            'reference_number' => 'nullable|string|max:64',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $payment = Payment::create([
            'payment_number' => Payment::generateNextNumber(),
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'reference_number' => $validated['reference_number'] ?? null,
            'payment_date' => $validated['payment_date'] ?? Carbon::today(),
            'recorded_by_user_id' => $request->user()?->id,
            'notes' => $validated['notes'] ?? null,
        ]);

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
            'order' => $order->fresh(['payments']),
        ], 201);
    }

    /**
     * Postpones / Reschedules an order with SMS & Telegram notifications to the customer
     */
    public function postpone(Request $request, Order $order): JsonResponse
    {
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
}
