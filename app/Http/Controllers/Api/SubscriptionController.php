<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $query = Subscription::with('customer:id,customer_code,full_name,phone,address,subcity');

        if ($status && in_array($status, ['active', 'paused', 'cancelled'])) {
            $query->where('status', $status);
        }

        $subscriptions = $query->latest()->paginate(25);

        $stats = [
            'total_active' => Subscription::where('status', 'active')->count(),
            'weekly_active' => Subscription::where('status', 'active')->where('plan_type', 'weekly')->count(),
            'biweekly_active' => Subscription::where('status', 'active')->where('plan_type', 'biweekly')->count(),
            'monthly_active' => Subscription::where('status', 'active')->where('plan_type', 'monthly')->count(),
        ];

        return response()->json([
            'stats' => $stats,
            'subscriptions' => $subscriptions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'plan_type' => 'required|in:weekly,biweekly,monthly',
            'service_summary' => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'preferred_day' => 'nullable|string|max:32',
            'preferred_time_slot' => 'nullable|string|max:64',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $discountPercents = [
            'weekly' => 15.00,
            'biweekly' => 10.00,
            'monthly' => 5.00,
        ];

        $discPercent = $discountPercents[$validated['plan_type']] ?? 10.00;
        $base = (float)$validated['base_price'];
        $discountAmount = ($base * $discPercent) / 100.00;
        $discountedPrice = max(0, $base - $discountAmount);

        $startDate = Carbon::parse($validated['start_date']);
        $nextDate = match ($validated['plan_type']) {
            'weekly' => $startDate->copy()->addWeek(),
            'biweekly' => $startDate->copy()->addWeeks(2),
            'monthly' => $startDate->copy()->addMonth(),
        };

        $subscription = Subscription::create([
            'customer_id' => $validated['customer_id'],
            'plan_type' => $validated['plan_type'],
            'discount_percent' => $discPercent,
            'service_summary' => $validated['service_summary'],
            'base_price' => $base,
            'discounted_price' => $discountedPrice,
            'preferred_day' => $validated['preferred_day'] ?? $startDate->format('l'),
            'preferred_time_slot' => $validated['preferred_time_slot'] ?? 'Morning (8:00 AM - 12:00 PM)',
            'start_date' => $startDate,
            'next_service_date' => $nextDate,
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::logAction(
            $request->user()?->id,
            'subscription_created',
            Subscription::class,
            $subscription->id,
            null,
            $subscription->toArray()
        );

        return response()->json([
            'message' => 'Subscription plan activated successfully with discounted rate.',
            'subscription' => $subscription->load('customer'),
        ], 201);
    }

    public function updateStatus(Request $request, Subscription $subscription): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,paused,cancelled',
            'notes' => 'nullable|string',
        ]);

        $old = $subscription->status;
        $subscription->status = $validated['status'];
        if (!empty($validated['notes'])) {
            $subscription->notes = ($subscription->notes ? $subscription->notes . "\n" : '') . $validated['notes'];
        }
        $subscription->save();

        AuditLog::logAction(
            $request->user()?->id,
            'subscription_status_changed',
            Subscription::class,
            $subscription->id,
            ['status' => $old],
            ['status' => $subscription->status]
        );

        return response()->json([
            'message' => "Subscription status updated to {$subscription->status}",
            'subscription' => $subscription,
        ]);
    }
}
