<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\WebsiteSetting;
use App\Services\EthiopianCalendarService;
use App\Services\SecurityTokenService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicWebsiteController extends Controller
{
    /**
     * Get public website settings (phones, slogans, about text, bank accounts, subcities, time slots, pricing rules)
     */
    public function settings(): JsonResponse
    {
        $settings = WebsiteSetting::getPublicKeyValue();
        $subcities = WebsiteSetting::getJson('subcities', []);
        $timeSlots = WebsiteSetting::getJson('time_slots', []);
        $pricingRules = WebsiteSetting::getJson('pricing_rules', []);
        $paymentAccounts = WebsiteSetting::getJson('payment_accounts', []);

        return response()->json([
            'success' => true,
            'settings' => $settings,
            'subcities' => $subcities,
            'time_slots' => $timeSlots,
            'pricing_rules' => $pricingRules,
            'payment_accounts' => $paymentAccounts,
        ]);
    }

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
            'payment_method' => 'nullable|string|in:cash,telebirr,cbe,cbe_birr,bank_transfer',
            'notes' => 'nullable|string',
            'marketing_consent' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $phone = SecurityTokenService::normalizePhone($validated['customer_phone']);
            // Find or create customer
            $customer = Customer::where('phone', $phone)->first();
            if (!$customer) {
                $customer = Customer::create([
                    'customer_code' => Customer::generateNextCode(),
                    'full_name' => $validated['customer_name'],
                    'phone' => $phone,
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
            $pricingRules = WebsiteSetting::getJson('pricing_rules', [
                'weekly_discount' => 15,
                'biweekly_discount' => 10,
                'monthly_discount' => 5,
            ]);
            $discRates = [
                'weekly' => (float)($pricingRules['weekly_discount'] ?? 15.0),
                'biweekly' => (float)($pricingRules['biweekly_discount'] ?? 10.0),
                'monthly' => (float)($pricingRules['monthly_discount'] ?? 5.0),
                'one_time' => 0.0,
            ];
            $discPercent = $discRates[$plan] ?? 0.0;

            $payMethod = ($validated['payment_method'] ?? 'cash') === 'cbe_birr' ? 'cbe' : ($validated['payment_method'] ?? 'cash');
            $notes = $validated['notes'] ?? '';
            if (!empty($validated['payment_method'])) {
                $payLabels = ['cash' => 'ጥሬ ገንዘብ (Cash)', 'telebirr' => 'ቴሌብር (Telebirr)', 'cbe' => 'የኢትዮጵያ ንግድ ባንክ (CBE)', 'bank_transfer' => 'የባንክ ዝውውር (Bank Transfer)'];
                $notes = ($notes ? $notes . ' | ' : '') . 'የተመረጠ የክፍያ መንገድ: ' . ($payLabels[$payMethod] ?? $payMethod);
            }

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
                'notes' => $notes ?: null,
                'version' => 1,
            ]);

            $subtotalCalc = 0;
            $itemsSummary = [];
            foreach ($validated['items'] as $it) {
                // Server-side catalogue lookup - never trust client unit_price
                $service = Service::whereKey($it['service_id'])->where('is_active', true)->first();
                if (!$service) {
                    throw ValidationException::withMessages([
                        'items' => ['One or more selected services are unavailable. Please refresh and try again.'],
                    ]);
                }

                $unitPrice = (float)$service->base_price;
                $itemName = $service->name_am ?: $service->name_en;
                $sub = round((float)$it['quantity'] * $unitPrice, 2);
                $subtotalCalc += $sub;
                $itemsSummary[] = "{$itemName} (x{$it['quantity']})";

                OrderItem::create([
                    'order_id' => $order->id,
                    'service_id' => $service->id,
                    'item_name' => $itemName,
                    'quantity' => $it['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $sub,
                ]);
            }

            // Update order financial figures strictly from server recalculation
            $order->subtotal = $subtotalCalc;
            $order->total = $subtotalCalc;

            // Apply subscription recurring discount if selected
            if ($discPercent > 0) {
                $subDiscount = round(($subtotalCalc * $discPercent) / 100.0, 2);
                $order->subscription_discount = $subDiscount;
                $order->discount = $subDiscount;
                $order->total = max(0, $subtotalCalc - $subDiscount);

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
                    'billing_cycle' => $plan,
                    'preferred_day' => $startDate->format('l'),
                    'preferred_time_slot' => $order->appointment_time_slot,
                    'start_date' => $startDate,
                    'next_service_date' => $nextDate,
                    'status' => 'active',
                    'notes' => "Auto-created from website booking #{$orderNumber}",
                ]);
            }
            $order->save();

            // Create corresponding Appointment record for Dispatch Calendar & Dashboard Metrics
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
                'cleaning_team_id' => null,
                'appointment_date' => $order->appointment_date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => 'scheduled',
                'notes' => !empty($notesParts) ? implode(' | ', $notesParts) : 'Booked via Website',
            ]);

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

            $trackingToken = SecurityTokenService::generateTrackingToken($order);
            $actionToken = SecurityTokenService::generateActionToken($order, 'reschedule');

            return response()->json([
                'success' => true,
                'message' => 'BOOKING RECEIVED. Our receptionist will call to confirm your appointment shortly.',
                'booking_id' => $order->order_number,
                'order_number' => $order->order_number,
                'tracking_token' => $trackingToken,
                'action_token' => $actionToken,
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
        try {
            $token = trim($request->input('token', $request->input('tracking_token', '')));
            $search = trim($request->input('tracking_id', $request->input('query', '')));

            $verifiedPayload = SecurityTokenService::verifyTrackingToken($token);
            if (!$verifiedPayload && !empty($search)) {
                $verifiedPayload = SecurityTokenService::verifyTrackingToken($search);
            }

            $order = null;
            $hasValidToken = false;

            if ($verifiedPayload) {
                $order = Order::with(['customer:id,full_name,subcity', 'items.service:id,name_en,name_am', 'assignedTeam'])
                    ->find($verifiedPayload['order_id']);
                $hasValidToken = true;
            } elseif (!empty($search)) {
                // Public lookup without token: find by order_number only (not open by arbitrary phone)
                $order = Order::with(['customer:id,full_name,subcity', 'items.service:id,name_en,name_am', 'assignedTeam'])
                    ->where('order_number', $search)
                    ->latest()
                    ->first();
            }

            if (!$order) {
                return response()->json([
                    'found' => false,
                    'message' => 'ትክክለኛ ትዕዛዝ ወይም የማረጋገጫ ማስረጃ አልተገኘም። እባክዎን የትዕዛዝ ቁጥርዎን ወይም የተሰጠዎትን ሊንክ ያረጋግጡ።',
                ], 404);
            }

            $eth = !empty($order->appointment_date)
                ? EthiopianCalendarService::toEthiopian($order->appointment_date)
                : ['formatted_am' => 'ቀን አልተወሰነም'];

            $isCompletedOrCancelled = in_array($order->order_status, ['completed', 'cancelled']);

            $apptDateStr = !empty($order->appointment_date)
                ? (is_object($order->appointment_date) ? $order->appointment_date->toDateString() : substr((string)$order->appointment_date, 0, 10))
                : now()->toDateString();

            // Mask customer name for privacy (e.g. "Abebe K***")
            $rawName = $order->customer?->full_name ?? 'ውድ ደንበኛችን';
            $nameParts = explode(' ', trim($rawName));
            $maskedName = $nameParts[0] . (count($nameParts) > 1 ? ' ' . mb_substr($nameParts[1], 0, 1) . '***' : '');

            // Non-sensitive sanitized items
            $safeItems = $order->items->map(function ($item) {
                return [
                    'item_name' => $item->item_name,
                    'quantity' => (float)$item->quantity,
                ];
            });

            // If request has a verified token, provide authorized status details
            // Never expose live GPS, exact house numbers, or financial details publicly
            $orderData = [
                'order_number' => $order->order_number,
                'customer_name' => $hasValidToken ? $rawName : $maskedName,
                'subcity' => $order->subcity,
                'status' => $order->order_status,
                'appointment_date' => $apptDateStr,
                'eth_appointment_date' => $eth['formatted_am'] ?? $apptDateStr,
                'appointment_time_slot' => $order->appointment_time_slot,
                'subscription_plan' => $order->subscription_plan ?? 'one_time',
                'team_assigned' => $order->assignedTeam ? 'የተመደበ የሜሽ ቡድን' : 'Pending Assignment',
                'items' => $safeItems,
                'has_secure_token' => $hasValidToken,
                'is_tracking_expired' => $isCompletedOrCancelled,
                'privacy_notice' => 'ለደንበኞች እና ለሰራተኞች ደህንነት ሲባል ትክክለኛ አድራሻ፣ የክፍያ ዝርዝር እና የቀጥታ GPS በይፋ አይጋለጡም።',
            ];

            if ($hasValidToken) {
                $orderData['reschedule_token'] = SecurityTokenService::generateActionToken($order, 'reschedule');
            }

            return response()->json([
                'success' => true,
                'found' => true,
                'order' => $orderData,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Track order error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'ትዕዛዙን በመፈለግ ላይ ስህተት ተፈጥሯል። እባክዎን በስልክ ይደውሉልን።',
            ], 500);
        }
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

    /**
     * Submit public customer review & star rating for an order
     */
    public function submitReview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => 'nullable|string|max:64',
            'order_id' => 'nullable|integer',
            'phone' => 'nullable|string|max:32',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $orderNumber = trim($validated['order_number'] ?? '');
        $orderId = $validated['order_id'] ?? null;
        $phone = !empty($validated['phone']) ? SecurityTokenService::normalizePhone($validated['phone']) : null;

        $orderQuery = Order::with('customer');
        if (!empty($orderId)) {
            $orderQuery->where('id', $orderId);
        } elseif (!empty($orderNumber)) {
            $orderQuery->where('order_number', $orderNumber);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'የትዕዛዝ ቁጥር (Order Number) ያስፈልጋል::',
            ], 422);
        }

        $order = $orderQuery->first();
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'የትዕዛዝ መረጃው አልተገኘም:: እባክዎ ትክክለኛ የትዕዛዝ ቁጥር ያስገቡ::',
            ], 404);
        }

        if ($phone && $order->customer) {
            $cleanCustPhone = SecurityTokenService::normalizePhone($order->customer->phone ?? '');
            if ($cleanCustPhone !== $phone) {
                return response()->json([
                    'success' => false,
                    'message' => 'ያስገቡት ስልክ ቁጥር ከትዕዛዙ መረጃ ጋር አይዛመድም::',
                ], 403);
            }
        }

        $rating = (int)$validated['rating'];
        $comment = trim($validated['comment'] ?? '');

        $feedback = Feedback::updateOrCreate(
            ['order_id' => $order->id],
            [
                'customer_id' => $order->customer_id,
                'rating' => $rating,
                'comment' => $comment ?: null,
                'source' => $request->input('source', 'website'),
                'is_approved' => ($rating >= 4),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'አስተያየትዎ እና የኮከብ ደረጃዎ በተሳካ ሁኔታ ተመዝግቧል! እናመሰግናለን::',
            'feedback' => $feedback,
        ]);
    }

    /**
     * Customer Self-Reschedule / Postpone Request from Website or Bot
     * Strictly requires a valid expiring signed action token.
     */
    public function rescheduleOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => 'required|string',
            'token' => 'nullable|string',
            'action_token' => 'nullable|string',
            'new_appointment_date' => 'required|date|after_or_equal:today',
            'new_time_slot' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);

        $token = $request->input('action_token', $request->input('token', ''));

        // Verify cryptographic token
        $orderId = SecurityTokenService::verifyActionToken($token, 'reschedule');
        if (!$orderId) {
            $trackingPayload = SecurityTokenService::verifyTrackingToken($token);
            if ($trackingPayload && ($trackingPayload['order_number'] ?? '') === trim($validated['order_number'])) {
                $orderId = (int)$trackingPayload['order_id'];
            }
        }

        if (!$orderId) {
            return response()->json([
                'success' => false,
                'message' => 'ቀጠሮ ለማዛወር የተፈረመና ጊዜው ያላለፈ የማረጋገጫ ማስረጃ (Valid action token) ያስፈልጋል። እባክዎ በትዕዛዝ ማረጋገጫዎ ላይ ያለውን ሊንክ ይጠቀሙ ወይም በስልክ ይደውሉልን።',
            ], 403);
        }

        $order = Order::with('customer')->where('id', $orderId)->first();
        if (!$order || $order->order_number !== trim($validated['order_number'])) {
            return response()->json([
                'success' => false,
                'message' => 'የትዕዛዝ ቁጥሩ እና የማረጋገጫ ማስረጃው አይዛመዱም።',
            ], 403);
        }

        if (in_array($order->order_status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'የተጠናቀቀ ወይም የተሰረዘ ትዕዛዝ ቀጠሮ ማዛወር አይቻልም።',
            ], 422);
        }

        $oldDate = $order->appointment_date ? (is_string($order->appointment_date) ? $order->appointment_date : $order->appointment_date->format('Y-m-d')) : 'ያልተገለጸ';
        $newDate = $validated['new_appointment_date'];
        $newSlot = $validated['new_time_slot'] ?? $order->appointment_time_slot ?? 'morning';
        $reason = $validated['reason'] ?? 'በደንበኛው ጥያቄ መሰረት';

        $order->appointment_date = $newDate;
        $order->appointment_time_slot = $newSlot;
        $order->order_status = 'rescheduled';
        $note = "ቀጠሮ ተላልፏል (Customer Reschedule): ከ {$oldDate} ወደ {$newDate} ({$newSlot}) | ምክንያት: {$reason}";
        $order->notes = ($order->notes ? $order->notes . "\n" : '') . $note;
        $order->save();

        \App\Models\Appointment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'customer_id' => $order->customer_id,
                'cleaning_team_id' => $order->assigned_team_id,
                'appointment_date' => $newDate,
                'status' => 'rescheduled',
                'notes' => $note,
            ]
        );

        \App\Models\Notification::create([
            'title' => "ደንበኛ ቀጠሮ አስተላልፏል (#{$order->order_number})",
            'body' => "ደንበኛ {$order->customer?->full_name} ቀጠሮውን ወደ {$newDate} አስተላልፏል::",
            'type' => 'order_rescheduled',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'new_date' => $newDate,
            ]
        ]);

        $eth = EthiopianCalendarService::toEthiopian($newDate);

        return response()->json([
            'success' => true,
            'message' => "ቀጠሮዎ በተሳካ ሁኔታ ወደ {$newDate} ({$eth['formatted_am']}) ተላልፏል:: የፅዳት ቡድናችን በተያዘው ቀን በሰዓቱ ይደርሳል!",
            'new_appointment_date' => $newDate,
            'eth_appointment_date' => $eth['formatted_am'],
        ]);
    }

    public function quickMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'message' => 'required|string|max:1000',
        ]);

        $customer = Customer::firstOrCreate(
            ['phone' => $validated['phone']],
            [
                'customer_code' => Customer::generateNextCode(),
                'full_name' => $validated['name'],
                'customer_type' => 'individual',
                'marketing_consent' => true,
                'consent_timestamp' => now(),
            ]
        );

        Notification::create([
            'title' => 'አዲስ ፈጣን መልእክት ከድረ-ገጽ',
            'body' => "ደንበኛ፡ {$validated['name']} ({$validated['phone']})\nመልእክት፡ {$validated['message']}",
            'type' => 'customer_inquiry',
            'data' => [
                'customer_id' => $customer->id,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'message' => $validated['message'],
            ],
        ]);

        $botToken = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN');
        $adminChatId = config('services.telegram.admin_chat_id') ?: env('TELEGRAM_ADMIN_CHAT_ID');
        if (!empty($botToken) && !empty($adminChatId)) {
            try {
                $text = "<b>አዲስ ፈጣን መልእክት ከድረ-ገጽ ደርሷል!</b>\n\n" .
                    "<b>ስም:</b> " . htmlspecialchars($validated['name']) . "\n" .
                    "<b>ስልክ:</b> <code>" . htmlspecialchars($validated['phone']) . "</code>\n" .
                    "<b>የደንበኛው ጥያቄ:</b>\n" . htmlspecialchars($validated['message']) . "\n\n" .
                    "እባክዎ ደውለው ያነጋግሯቸው!";
                \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $adminChatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Quick message telegram alert failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'መልእክትዎ በተሳካ ሁኔታ ደርሶናል! የሪሴፕሽን ሰራተኞቻችን በጥቂት ደቂቃዎች ውስጥ በስልክ ይደውሉልዎታል::',
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
