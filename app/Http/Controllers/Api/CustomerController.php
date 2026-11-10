<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Services\EthiopianCalendarService;
use App\Services\OrderLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user && $user->isCleaner()) {
            return response()->json(['message' => 'Unauthorized. Cleaners cannot access customer records.'], 403);
        }

        $query = Customer::query()->withCount('orders')->with(['feedbacks' => fn($q) => $q->latest()->limit(1)]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('alt_phone', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('subcity', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('customer_type')) {
            $query->where('customer_type', $type);
        }

        // Filter tabs: normal_active (reviewed), comment_waiting, in_service, new
        $stage = $request->input('lifecycle_stage') ?: $request->input('stage');
        if ($stage && $stage !== 'all') {
            if ($stage === 'normal_active' || $stage === 'reviewed') {
                $query->where(function ($q) {
                    $q->where('lifecycle_stage', 'normal_active')
                      ->orWhereHas('feedbacks');
                });
            } else {
                $query->where('lifecycle_stage', $stage);
            }
        }

        $customers = $query->latest()->paginate($request->input('per_page', 20));

        // Format customer fields for UI display
        $customers->getCollection()->transform(function ($cust) {
            $latestFb = $cust->feedbacks->first();
            $cust->latest_rating = $latestFb ? (int)$latestFb->rating : null;
            $cust->latest_comment = $latestFb ? $latestFb->comment : null;
            $cust->latest_feedback_date = $latestFb ? $latestFb->created_at->toIso8601String() : null;
            $cust->total_orders = $cust->total_orders ?: $cust->orders_count;
            if ((float)$cust->total_spent <= 0) {
                $cust->total_spent = (float)\App\Models\Payment::where('customer_id', $cust->id)->sum('amount');
            }
            return $cust;
        });

        $stageCounts = [
            'all' => Customer::count(),
            'normal_active' => Customer::where('lifecycle_stage', 'normal_active')->orWhereHas('feedbacks')->count(),
            'comment_waiting' => Customer::where('lifecycle_stage', 'comment_waiting')->count(),
            'in_service' => Customer::where('lifecycle_stage', 'in_service')->count(),
        ];

        return response()->json([
            'customers' => $customers,
            'stage_counts' => $stageCounts,
            // Maintain backwards compatibility if frontend expects raw paginator
            'data' => $customers->items(),
            'current_page' => $customers->currentPage(),
            'last_page' => $customers->lastPage(),
            'total' => $customers->total(),
        ]);
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        $user = $request->user();
        if ($user && $user->isCleaner()) {
            return response()->json(['message' => 'Unauthorized. Cleaners cannot access customer records.'], 403);
        }

        $customer->load([
            'orders.items.service',
            'orders.payments',
            'feedbacks',
            'complaints',
            'followups',
        ]);

        // Build summary stats
        $totalOrders = $customer->orders->count();
        $completedOrders = $customer->orders->where('order_status', 'completed')->count();
        $cancelledOrders = $customer->orders->where('order_status', 'cancelled')->count();
        $totalSpending = $customer->orders->sum('total');
        $lastOrder = $customer->orders->first();

        $lastServiceEth = null;
        if ($lastOrder) {
            $lastServiceEth = EthiopianCalendarService::toEthiopian($lastOrder->appointment_date);
        }

        $lastFeedback = $customer->feedbacks->first();
        $complaintCount = $customer->complaints->count();

        // Build timeline
        $timeline = [];

        foreach ($customer->orders as $order) {
            $ethDate = EthiopianCalendarService::toEthiopian($order->created_at);
            $timeline[] = [
                'type' => 'order',
                'title' => "Order #{$order->order_number}",
                'subtitle' => "Status: {$order->order_status} | Total: " . number_format($order->total, 2) . " ETB",
                'date' => $order->created_at->toIso8601String(),
                'eth_date' => $ethDate['formatted_am'],
                'data' => $order,
            ];

            foreach ($order->payments as $payment) {
                $ethPayDate = EthiopianCalendarService::toEthiopian($payment->payment_date);
                $timeline[] = [
                    'type' => 'payment',
                    'title' => "Payment #{$payment->payment_number}",
                    'subtitle' => number_format($payment->amount, 2) . " ETB via " . strtoupper($payment->payment_method),
                    'date' => $payment->created_at->toIso8601String(),
                    'eth_date' => $ethPayDate['formatted_am'],
                    'data' => $payment,
                ];
            }
        }

        foreach ($customer->feedbacks as $fb) {
            $ethFbDate = EthiopianCalendarService::toEthiopian($fb->created_at);
            $timeline[] = [
                'type' => 'feedback',
                'title' => "Feedback: {$fb->rating} Stars",
                'subtitle' => $fb->comment ?? 'No comment provided',
                'date' => $fb->created_at->toIso8601String(),
                'eth_date' => $ethFbDate['formatted_am'],
                'data' => $fb,
            ];
        }

        foreach ($customer->complaints as $cmp) {
            $ethCmpDate = EthiopianCalendarService::toEthiopian($cmp->created_at);
            $timeline[] = [
                'type' => 'complaint',
                'title' => "Complaint #{$cmp->complaint_number}",
                'subtitle' => "Category: {$cmp->category} | Status: {$cmp->status}",
                'date' => $cmp->created_at->toIso8601String(),
                'eth_date' => $ethCmpDate['formatted_am'],
                'data' => $cmp,
            ];
        }

        // Sort timeline descending
        usort($timeline, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return response()->json([
            'customer' => $customer,
            'summary' => [
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrders,
                'cancelled_orders' => $cancelledOrders,
                'total_spending' => (float) $totalSpending,
                'last_service_eth' => $lastServiceEth ? $lastServiceEth['formatted_en'] : null,
                'last_rating' => $lastFeedback ? $lastFeedback->rating : null,
                'complaints_count' => $complaintCount,
            ],
            'timeline' => $timeline,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'alt_phone' => 'nullable|string|max:32',
            'address' => 'nullable|string',
            'subcity' => 'nullable|string|max:64',
            'woreda' => 'nullable|string|max:32',
            'house_no' => 'nullable|string|max:32',
            'landmark' => 'nullable|string',
            'customer_type' => 'nullable|string|in:individual,corporate',
            'lifecycle_stage' => 'nullable|string|in:new,in_service,comment_waiting,normal_active',
            'status' => 'nullable|string|in:active,inactive',
            'total_orders' => 'nullable|integer|min:0',
            'total_spent' => 'nullable|numeric|min:0',
            'preferred_contact_method' => 'nullable|string',
            'telegram_user_id' => 'nullable|string',
            'marketing_consent' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['customer_code'] = Customer::generateNextCode();
        if (!empty($validated['marketing_consent'])) {
            $validated['consent_timestamp'] = now();
        }

        $customer = Customer::create($validated);
        OrderLifecycleService::syncCustomerToTelegramUsers($customer);

        AuditLog::logAction(
            $request->user()?->id,
            'created',
            Customer::class,
            $customer->id,
            null,
            $customer->toArray()
        );

        return response()->json([
            'message' => 'Customer registered successfully',
            'customer' => $customer,
        ], 201);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:32',
                Rule::unique('customers', 'phone')->ignore($customer->id),
            ],
            'alt_phone' => 'nullable|string|max:32',
            'address' => 'nullable|string',
            'subcity' => 'nullable|string|max:64',
            'woreda' => 'nullable|string|max:32',
            'house_no' => 'nullable|string|max:32',
            'landmark' => 'nullable|string',
            'customer_type' => 'nullable|string|in:individual,corporate',
            'lifecycle_stage' => 'nullable|string|in:new,in_service,comment_waiting,normal_active',
            'status' => 'nullable|string|in:active,inactive',
            'total_orders' => 'nullable|integer|min:0',
            'total_spent' => 'nullable|numeric|min:0',
            'preferred_contact_method' => 'nullable|string',
            'telegram_user_id' => 'nullable|string',
            'marketing_consent' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $old = $customer->toArray();
        if (isset($validated['marketing_consent']) && $validated['marketing_consent'] && !$customer->marketing_consent) {
            $validated['consent_timestamp'] = now();
        }

        $customer->update($validated);
        OrderLifecycleService::syncCustomerToTelegramUsers($customer);

        AuditLog::logAction(
            $request->user()?->id,
            'updated',
            Customer::class,
            $customer->id,
            $old,
            $customer->toArray()
        );

        return response()->json([
            'message' => 'Customer updated successfully',
            'customer' => $customer,
        ]);
    }
}
