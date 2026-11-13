<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
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

        // Net spend needs refunds per customer. Refunds are Expense rows
        // (category 'refund') tagged "#<order_number>" in the
        // description — the same link OrderController::
        // refundTotalsByOrderNumber() and FinanceController::
        // recordRefund use. One query pair for the whole page.
        $pageCustomerIds = $customers->getCollection()->pluck('id')->all();
        $ordersOnPage = Order::whereIn('customer_id', $pageCustomerIds)
            ->get(['customer_id', 'order_number']);
        $refundTotals = $this->refundsByOrderNumber($ordersOnPage->pluck('order_number')->all());
        $refundsByCustomer = [];
        foreach ($ordersOnPage as $ord) {
            $refundsByCustomer[$ord->customer_id] = round(
                ($refundsByCustomer[$ord->customer_id] ?? 0) + ($refundTotals[$ord->order_number] ?? 0),
                2
            );
        }

        // Format customer fields for UI display
        $customers->getCollection()->transform(function ($cust) use ($refundsByCustomer) {
            $latestFb = $cust->feedbacks->first();
            $cust->latest_rating = $latestFb ? (int)$latestFb->rating : null;
            $cust->latest_comment = $latestFb ? $latestFb->comment : null;
            $cust->latest_feedback_date = $latestFb ? $latestFb->created_at->toIso8601String() : null;
            $cust->total_orders = $cust->total_orders ?: $cust->orders_count;
            // Spend shown in the list is NET: gross payments minus
            // refunds. Gross is the fresh payments sum; the stored
            // total_spent is only a fallback for customers with no
            // payment rows (legacy data).
            $grossPaid = (float) Payment::where('customer_id', $cust->id)->sum('amount');
            if ($grossPaid <= 0) {
                $grossPaid = (float) $cust->total_spent;
            }
            $cust->total_refunded = $refundsByCustomer[$cust->id] ?? 0.0;
            $cust->total_spent = max(0, round($grossPaid - $cust->total_refunded, 2));
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
        // Net spend: what the customer actually paid minus refunds.
        // (The old value summed order totals, so a 2,250 ETB order with
        // a 500 ETB refund still showed 2,250 instead of 1,750.)
        $grossPaid = 0.0;
        $orderNumbers = [];
        foreach ($customer->orders as $order) {
            $orderNumbers[] = $order->order_number;
            $grossPaid += (float) $order->payments->sum('amount');
        }
        if ($grossPaid <= 0) {
            // No payment rows: fall back to the stored total, then to
            // paid order totals (same fallback the stored value uses).
            $grossPaid = (float) $customer->total_spent;
            if ($grossPaid <= 0) {
                $grossPaid = (float) $customer->orders->where('payment_status', 'paid')->sum('total');
            }
        }
        $totalRefunded = array_sum($this->refundsByOrderNumber($orderNumbers));
        $totalSpending = max(0, round($grossPaid - $totalRefunded, 2));
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
                'total_paid_gross' => round($grossPaid, 2),
                'total_refunded' => round($totalRefunded, 2),
                'last_service_eth' => $lastServiceEth ? $lastServiceEth['formatted_en'] : null,
                'last_rating' => $lastFeedback ? $lastFeedback->rating : null,
                'complaints_count' => $complaintCount,
            ],
            'timeline' => $timeline,
        ]);
    }

    /**
     * Refund totals keyed by order number, in ONE query.
     * Refunds are Expense rows (category 'refund') whose description
     * carries "#<order_number>" — the same link FinanceController::
     * recordRefund uses and OrderController::refundTotalsByOrderNumber()
     * reads. Duplicated here because that helper is private to
     * OrderController.
     *
     * @param string[] $orderNumbers
     * @return array<string, float>
     */
    private function refundsByOrderNumber(array $orderNumbers): array
    {
        $orderNumbers = array_values(array_filter(array_unique($orderNumbers)));
        if (empty($orderNumbers)) {
            return [];
        }
        $totals = [];
        $refunds = Expense::where('category', 'refund')->get(['description', 'amount']);
        foreach ($refunds as $refund) {
            foreach ($orderNumbers as $number) {
                if ($number !== '' && str_contains((string) $refund->description, '#' . $number)) {
                    $totals[$number] = round(($totals[$number] ?? 0) + (float) $refund->amount, 2);
                    break;
                }
            }
        }
        return $totals;
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

        // Normalise the phone so the same number in different formats
        // (+251911..., 0911..., 911...) maps to one stored form, and
        // reject a second customer with the same number instead of
        // silently creating a duplicate.
        $validated['phone'] = Customer::normalizePhone($validated['phone']) ?? $validated['phone'];
        if (!empty($validated['alt_phone'])) {
            $validated['alt_phone'] = Customer::normalizePhone($validated['alt_phone']) ?? $validated['alt_phone'];
        }
        $existing = Customer::where('phone', $validated['phone'])->first();
        if ($existing) {
            return response()->json([
                'message' => 'በዚህ ስልክ ቁጥር የተመዘገበ ደንበኛ አለ (A customer with this phone number already exists).',
                'customer' => $existing,
            ], 422);
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
