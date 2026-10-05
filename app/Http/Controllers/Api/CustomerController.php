<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Services\EthiopianCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query()->withCount('orders');

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

        $customers = $query->latest()->paginate($request->input('per_page', 20));

        return response()->json($customers);
    }

    public function show(Customer $customer): JsonResponse
    {
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
            'phone' => 'sometimes|required|string|max:32',
            'alt_phone' => 'nullable|string|max:32',
            'address' => 'nullable|string',
            'subcity' => 'nullable|string|max:64',
            'woreda' => 'nullable|string|max:32',
            'house_no' => 'nullable|string|max:32',
            'landmark' => 'nullable|string',
            'customer_type' => 'nullable|string|in:individual,corporate',
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
