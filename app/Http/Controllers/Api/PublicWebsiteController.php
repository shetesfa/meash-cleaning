<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\Subscription;
use App\Services\EthiopianCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicWebsiteController extends Controller
{
    public function services(): JsonResponse
    {
        $services = Service::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json($services);
    }

    public function book(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:32',
            'address' => 'required|string',
            'subcity' => 'required|string|max:64',
            'woreda' => 'nullable|string|max:32',
            'house_no' => 'nullable|string|max:32',
            'landmark' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time_slot' => 'required|string|max:64',
            'subscription_plan' => 'nullable|string|in:one_time,weekly,biweekly,monthly',
            'notes' => 'nullable|string',
            'marketing_consent' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'nullable|exists:services,id',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            // Find or create customer
            $customer = Customer::where('phone', $validated['customer_phone'])->first();
            if (!$customer) {
                $customer = Customer::create([
                    'customer_code' => Customer::generateNextCode(),
                    'full_name' => $validated['customer_name'],
                    'phone' => $validated['customer_phone'],
                    'address' => $validated['address'],
                    'subcity' => $validated['subcity'],
                    'woreda' => $validated['woreda'] ?? null,
                    'house_no' => $validated['house_no'] ?? null,
                    'landmark' => $validated['landmark'] ?? null,
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'customer_type' => 'individual',
                    'marketing_consent' => $validated['marketing_consent'] ?? false,
                    'consent_timestamp' => !empty($validated['marketing_consent']) ? now() : null,
                ]);
            } else {
                if (!empty($validated['latitude']) && !empty($validated['longitude'])) {
                    $customer->update([
                        'latitude' => $validated['latitude'],
                        'longitude' => $validated['longitude'],
                    ]);
                }
            }

            $plan = $validated['subscription_plan'] ?? 'one_time';
            $discRates = ['weekly' => 15.0, 'biweekly' => 10.0, 'monthly' => 5.0, 'one_time' => 0.0];
            $discPercent = $discRates[$plan] ?? 0.0;

            $orderNumber = Order::generateNextNumber();
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'source' => 'website',
                'subscription_plan' => $plan,
                'order_status' => 'new',
                'payment_status' => 'unpaid',
                'subtotal' => 0,
                'discount' => 0,
                'subscription_discount' => 0,
                'tax' => 0,
                'total' => 0,
                'appointment_date' => $validated['appointment_date'],
                'appointment_time_slot' => $validated['appointment_time_slot'],
                'address' => $validated['address'],
                'subcity' => $validated['subcity'],
                'woreda' => $validated['woreda'] ?? null,
                'house_no' => $validated['house_no'] ?? null,
                'landmark' => $validated['landmark'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'version' => 1,
            ]);

            $subtotalCalc = 0;
            $itemsSummary = [];
            foreach ($validated['items'] as $it) {
                $sub = (float)$it['quantity'] * (float)$it['unit_price'];
                $subtotalCalc += $sub;
                $itemsSummary[] = "{$it['item_name']} (x{$it['quantity']})";
                OrderItem::create([
                    'order_id' => $order->id,
                    'service_id' => $it['service_id'] ?? null,
                    'item_name' => $it['item_name'],
                    'quantity' => $it['quantity'],
                    'unit_price' => $it['unit_price'],
                    'subtotal' => $sub,
                ]);
            }

            // Apply subscription recurring discount if selected
            if ($discPercent > 0) {
                $subDiscount = ($subtotalCalc * $discPercent) / 100.0;
                $order->update([
                    'subscription_discount' => $subDiscount,
                    'discount' => $order->discount + $subDiscount,
                    'total' => max(0, $subtotalCalc - ($order->discount + $subDiscount)),
                ]);

                // Create recurring subscription profile
                $startDate = Carbon::parse($order->appointment_date);
                $nextDate = match ($plan) {
                    'weekly' => $startDate->copy()->addWeek(),
                    'biweekly' => $startDate->copy()->addWeeks(2),
                    'monthly' => $startDate->copy()->addMonth(),
                    default => null,
                };

                Subscription::create([
                    'customer_id' => $customer->id,
                    'plan_type' => $plan,
                    'discount_percent' => $discPercent,
                    'service_summary' => implode(', ', $itemsSummary),
                    'base_price' => $subtotalCalc,
                    'discounted_price' => max(0, $subtotalCalc - $subDiscount),
                    'preferred_day' => $startDate->format('l'),
                    'preferred_time_slot' => $order->appointment_time_slot,
                    'start_date' => $startDate,
                    'next_service_date' => $nextDate,
                    'status' => 'active',
                    'notes' => "Auto-created from website booking #{$orderNumber}",
                ]);
            }

            // Create notification for Reception & Owner
            Notification::create([
                'title' => 'New Website Booking Received' . ($plan !== 'one_time' ? " ({$plan} subscription)" : ''),
                'body' => "Order #{$orderNumber} placed by {$customer->full_name} for {$order->appointment_date}.",
                'type' => 'new_booking',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $orderNumber,
                    'phone' => $customer->phone,
                    'total' => $order->total,
                    'subscription_plan' => $plan,
                ],
            ]);

            $eth = EthiopianCalendarService::toEthiopian($order->appointment_date);

            return response()->json([
                'success' => true,
                'message' => 'BOOKING RECEIVED. Our receptionist will call to confirm your appointment shortly.',
                'booking_id' => $order->order_number,
                'order_number' => $order->order_number,
                'eth_date' => $eth['formatted_am'],
                'eth_date_en' => $eth['formatted_en'],
                'subscription_plan' => $plan,
                'subscription_discount' => $order->subscription_discount,
                'order' => $order->fresh(['items', 'customer']),
            ], 201);
        });
    }

    public function trackOrder(Request $request): JsonResponse
    {
        $search = trim($request->input('tracking_id', $request->input('query', '')));

        if (empty($search)) {
            return response()->json(['success' => false, 'message' => 'Please provide an Order ID or Phone number.'], 422);
        }

        $order = Order::with(['customer:id,full_name,subcity,latitude,longitude', 'items.service:id,name_en,name_am', 'assignedTeam'])
            ->where('order_number', $search)
            ->orWhereHas('customer', function ($q) use ($search) {
                $q->where('phone', $search);
            })
            ->latest()
            ->first();

        if (!$order) {
            return response()->json([
                'found' => false,
                'message' => 'No booking found with this ID or phone number.',
            ], 404);
        }

        $eth = EthiopianCalendarService::toEthiopian($order->appointment_date);

        $teamLocation = null;
        if ($order->assignedTeam) {
            $teamLocation = [
                'team_name' => $order->assignedTeam->team_name,
                'phone' => $order->assignedTeam->phone,
                'vehicle_plate' => $order->assignedTeam->vehicle_plate,
                'latitude' => $order->assignedTeam->current_latitude ? (float)$order->assignedTeam->current_latitude : null,
                'longitude' => $order->assignedTeam->current_longitude ? (float)$order->assignedTeam->current_longitude : null,
                'status' => $order->assignedTeam->status,
                'updated_at' => $order->assignedTeam->location_updated_at?->diffForHumans() ?? 'በቅርቡ',
            ];
        }

        $custLat = $order->latitude !== null ? (float)$order->latitude : null;
        $custLng = $order->longitude !== null ? (float)$order->longitude : null;

        $customerLocation = ($custLat !== null && $custLng !== null) ? [
            'latitude' => $custLat,
            'longitude' => $custLng,
        ] : null;

        return response()->json([
            'success' => true,
            'found' => true,
            'order' => [
                'order_number' => $order->order_number,
                'customer_name' => $order->customer->full_name,
                'address' => $order->address,
                'subcity' => $order->subcity,
                'status' => $order->order_status,
                'payment_status' => $order->payment_status,
                'appointment_date' => $order->appointment_date->toDateString(),
                'eth_appointment_date' => $eth['formatted_am'],
                'appointment_time_slot' => $order->appointment_time_slot,
                'subscription_plan' => $order->subscription_plan ?? 'one_time',
                'subscription_discount' => (float)($order->subscription_discount ?? 0),
                'total' => (float) $order->total,
                'team_assigned' => $order->assignedTeam ? $order->assignedTeam->team_name : 'Pending Assignment',
                'team_location' => $teamLocation,
                'customer_location' => $customerLocation,
                'items' => $order->items,
            ],
        ]);
    }

    public function publicReviews(): JsonResponse
    {
        $feedbacks = Feedback::with('customer:id,full_name')
            ->where('is_approved', true)
            ->latest()
            ->limit(10)
            ->get();

        return response()->json($feedbacks);
    }
}
