<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Followup;
use App\Models\Order;
use App\Services\EthiopianCalendarService;
use App\Services\OrderLifecycleService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerCareController extends Controller
{
    public function followups(Request $request): JsonResponse
    {
        // Follow-up generation is EXPLICIT only (POST /followups/generate):
        // a GET list must never create rows.
        if ($request->boolean('generate')) {
            $this->generateMissingFollowups();
        }

        $query = Followup::with([
            'order:id,order_number,total,appointment_date,payment_status,order_status',
            'order.items.service:id,name_en,name_am',
            'customer:id,customer_code,full_name,phone,subcity,lifecycle_stage',
            'handledBy:id,name',
        ]);

        if ($request->input('filter') === 'due_today') {
            // Include due today or created today without hiding any pending review
            $query->where(function ($q) {
                $q->whereDate('due_date', '<=', Carbon::today())
                  ->orWhereDate('created_at', Carbon::today());
            })->where('status', 'pending');
        } elseif ($request->has('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        } elseif (!$request->has('status') && !$request->has('filter')) {
            // Default to all pending follow-up queue so today's created orders are displayed immediately!
            $query->where('status', 'pending');
        }

        $followups = $query->latest('id')->paginate($request->input('per_page', 25));

        $followups->getCollection()->transform(function ($item) {
            $eth = EthiopianCalendarService::toEthiopian($item->due_date ?: $item->created_at);
            $item->eth_due_date = $eth['formatted_am'];
            $item->order_number = $item->order?->order_number;
            $item->customer_name = $item->customer?->full_name;
            $item->customer_phone = $item->customer?->phone;
            $item->order_total = (float)($item->order?->total ?? 0);
            return $item;
        });

        $commentWaitingCount = Followup::where('status', 'pending')->count();
        $completedFollowupCount = Followup::where('status', 'completed')->count();

        return response()->json([
            'followups' => $followups,
            'comment_waiting_count' => $commentWaitingCount,
            'completed_count' => $completedFollowupCount,
            'data' => $followups->items(),
            'current_page' => $followups->currentPage(),
            'last_page' => $followups->lastPage(),
            'total' => $followups->total(),
        ]);
    }

    /**
     * Explicitly generate pending follow-ups for completed/paid orders
     * that don't have one yet. Called from POST /followups/generate
     * (button in the Care tab) — never from the GET list.
     */
    public function generateFollowups(Request $request): JsonResponse
    {
        $created = $this->generateMissingFollowups();
        return response()->json([
            'success' => true,
            'created' => $created,
            'message' => $created > 0
                ? "{$created} አዲስ የክትትል (follow-up) መዝገቦች ተፈጥረዋል።"
                : 'አዲስ የሚፈጠር የክትትል መዝገብ አልተገኘም — ሁሉም የተጠናቀቁ ትዕዛዞች የክትትል መዝገብ አላቸው።',
        ]);
    }

    protected function generateMissingFollowups(): int
    {
        $completedOrdersWithoutFollowup = \App\Models\Order::where(function ($q) {
                $q->where('order_status', 'completed')
                  ->orWhere('payment_status', 'paid');
            })
            ->whereDoesntHave('followups')
            ->get();

        foreach ($completedOrdersWithoutFollowup as $cOrder) {
            Followup::create([
                'order_id' => $cOrder->id,
                'customer_id' => $cOrder->customer_id,
                'type' => 'review',
                'due_date' => Carbon::today(),
                'status' => 'pending',
                'notes' => 'Post-service follow-up call',
            ]);
        }

        return $completedOrdersWithoutFollowup->count();
    }

    public function updateFollowup(Request $request, Followup $followup): JsonResponse
    {
        $status = $request->input('status', 'completed');
        $rating = $request->input('rating') ?? $request->input('satisfaction_score') ?? 5;
        $notes = $request->input('notes') ?? $request->input('feedback_notes');
        
        $outcome = $request->input('outcome');
        if (!$outcome) {
            $outcome = ($rating >= 4) ? 'satisfied' : (($rating <= 2) ? 'complaint' : 'satisfied');
        }

        $followup->loadMissing(['customer', 'order']);
        $customer = $followup->customer ?: \App\Models\Customer::findOrFail($followup->customer_id);
        $order = $followup->order;

        // Record feedback and promote customer out of Comment Waiting queue
        $feedback = OrderLifecycleService::recordCustomerReviewAndPromote(
            $customer,
            $order,
            (int) $rating,
            $notes ?: 'Customer feedback recorded via post-service follow-up call.',
            'phone',
            $request->user()?->id
        );

        if ($outcome === 'complaint' || $rating <= 2) {
            Complaint::create([
                'complaint_number' => Complaint::generateNextNumber(),
                'order_id' => $followup->order_id,
                'customer_id' => $followup->customer_id,
                'category' => 'followup_escalation',
                'description' => $notes ?: 'Customer raised concern during follow-up call.',
                'priority' => 'high',
                'status' => 'new',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'የክትትል ጥሪው እና የደንበኛው አስተያየት በተሳካ ሁኔታ ተመዝግቧል!',
            'followup' => $followup->fresh(['customer', 'order']),
            'feedback' => $feedback,
        ]);
    }

    /**
     * Alias for completing a followup record
     */
    public function completeFollowup(Request $request, Followup $followup): JsonResponse
    {
        return $this->updateFollowup($request, $followup);
    }

    public function feedbacks(Request $request): JsonResponse
    {
        $query = Feedback::with([
            'order:id,order_number',
            'customer:id,customer_code,full_name,phone',
        ]);

        $feedbacks = $query->latest()->paginate(20);

        $totalFeedbacks = Feedback::count();
        $avgRating = round((float) Feedback::avg('rating') ?: 5.0, 1);
        $starCounts = Feedback::select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating');

        $ratingDistribution = [];
        for ($s = 5; $s >= 1; $s--) {
            $c = (int)($starCounts[$s] ?? 0);
            $ratingDistribution[$s] = [
                'stars' => $s,
                'count' => $c,
                'percentage' => $totalFeedbacks > 0 ? round(($c / $totalFeedbacks) * 100) : 0,
            ];
        }

        return response()->json([
            'avg_rating' => $avgRating,
            'total_feedbacks' => $totalFeedbacks,
            'rating_distribution' => $ratingDistribution,
            'feedbacks' => $feedbacks,
            'data' => $feedbacks->items(),
            'current_page' => $feedbacks->currentPage(),
            'last_page' => $feedbacks->lastPage(),
        ]);
    }

    public function storeFeedback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'customer_id' => 'required|exists:customers,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'source' => 'nullable|string|in:web,telegram,phone',
        ]);

        $customer = \App\Models\Customer::findOrFail($validated['customer_id']);
        $order = !empty($validated['order_id']) ? \App\Models\Order::find($validated['order_id']) : null;

        $feedback = OrderLifecycleService::recordCustomerReviewAndPromote(
            $customer,
            $order,
            (int)$validated['rating'],
            $validated['comment'] ?? null,
            $validated['source'] ?? 'phone',
            $request->user()?->id
        );

        return response()->json([
            'message' => 'Feedback saved successfully',
            'feedback' => $feedback->load('customer'),
        ], 201);
    }
    public function approveFeedback(Request $request, Feedback $feedback): JsonResponse
    {
        $validated = $request->validate([
            'is_approved' => 'required|boolean',
        ]);

        $feedback->update(['is_approved' => $validated['is_approved']]);

        return response()->json([
            'success' => true,
            'message' => $feedback->is_approved ? 'አስተያየቱ በይፋዊ ድረ-ገጹ ላይ እንዲታይ ተፈቅዷል' : 'አስተያየቱ ከድረ-ገጹ እንዲደበቅ ተደርጓል',
            'feedback' => $feedback,
        ]);
    }

    public function complaints(Request $request): JsonResponse
    {
        $query = Complaint::with([
            'customer:id,customer_code,full_name,phone,address',
            'order:id,order_number,total,appointment_date',
            'assignedTo:id,name',
        ]);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        $complaints = $query->latest()->paginate(20);
        return response()->json($complaints);
    }

    public function storeComplaint(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'order_id' => 'nullable|exists:orders,id',
            'category' => 'required|string|max:64',
            'description' => 'required|string',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'assigned_to_user_id' => 'nullable|exists:users,id',
        ]);

        // Derive the customer from the order when the caller only knows
        // the order (e.g. the cleaner problem-report button).
        if (empty($validated['customer_id']) && !empty($validated['order_id'])) {
            $validated['customer_id'] = Order::find($validated['order_id'])?->customer_id;
        }
        if (empty($validated['customer_id'])) {
            return response()->json([
                'message' => 'customer_id is required (or an order_id whose order has a customer).',
            ], 422);
        }

        $validated['complaint_number'] = Complaint::generateNextNumber();
        $validated['priority'] = $validated['priority'] ?? 'medium';
        $validated['status'] = 'new';

        $complaint = Complaint::create($validated);

        AuditLog::logAction(
            $request->user()?->id,
            'complaint_filed',
            Complaint::class,
            $complaint->id,
            null,
            $complaint->toArray()
        );

        return response()->json([
            'message' => 'Complaint logged successfully',
            'complaint' => $complaint->load('customer', 'order'),
        ], 201);
    }

    public function resolveComplaint(Request $request, Complaint $complaint): JsonResponse
    {
        if (!$request->has('resolution_notes') && $request->has('resolution_action')) {
            $request->merge(['resolution_notes' => $request->input('resolution_action')]);
        }

        $validated = $request->validate([
            'resolution_notes' => 'required|string',
            'status' => 'nullable|string|in:in_progress,resolved,closed',
        ]);

        $old = $complaint->toArray();

        $status = $validated['status'] ?? 'resolved';
        $userId = $request->user()?->id ?? auth()->id();
        $complaint->update([
            'resolution_notes' => $validated['resolution_notes'],
            'status' => $status,
            'resolved_at' => in_array($status, ['resolved', 'closed']) ? now() : null,
            'assigned_to_user_id' => $userId,
        ]);

        AuditLog::logAction(
            $userId,
            'complaint_resolved',
            Complaint::class,
            $complaint->id,
            $old,
            $complaint->toArray()
        );

        return response()->json([
            'message' => 'Complaint updated successfully',
            'complaint' => $complaint->fresh(['customer', 'assignedTo']),
        ]);
    }

    /**
     * 1-to-1 SMS messaging to a customer
     */
    public function sendSms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'phone' => 'nullable|string',
            'message' => 'required|string|max:1000',
        ]);

        $phone = $validated['phone'] ?? null;
        $customer = null;
        if (!empty($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
            if ($customer && empty($phone)) {
                $phone = $customer->phone;
            }
        }

        if (empty($phone)) {
            return response()->json([
                'success' => false,
                'message' => 'እባክዎ ትክክለኛ የደንበኛ ስልክ ቁጥር ያስገቡ!',
            ], 422);
        }

        $cleanPhone = SmsService::normalizeEthiopianPhone($phone);
        $result = SmsService::sendDirectSms($cleanPhone, $validated['message']);

        AuditLog::logAction(
            $request->user()?->id,
            'customer_care_sms_sent',
            Customer::class,
            $customer?->id,
            null,
            [
                'phone' => $cleanPhone,
                'message' => $validated['message'],
                'customer_name' => $customer?->full_name,
                'result' => $result,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'መልእክቱ በሲስተም መዝገብ ተቀምጧል — ለደንበኛው አልተላከም (no SMS gateway)። በስልክዎ Messages App ይላኩት።',
            'phone' => $cleanPhone,
            'details' => $result,
        ]);
    }

    /**
     * 1-to-1 Telegram messaging with safe phone-only fallback to SMS without throwing errors
     */
    public function sendTelegram(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'telegram_chat_id' => 'nullable',
            'phone' => 'nullable|string',
            'message' => 'required|string|max:4000',
        ]);

        $chatId = $validated['telegram_chat_id'] ?? null;
        $phone = $validated['phone'] ?? null;
        $customer = null;

        if (!empty($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
            if ($customer) {
                $chatId = $chatId ?: $customer->telegram_user_id;
                $phone = $phone ?: $customer->phone;
            }
        }

        // Validate telegram_chat_id
        $hasValidTelegram = !empty($chatId) && is_numeric($chatId) && (int)$chatId > 10000;

        $botToken = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN');

        if ($hasValidTelegram && !empty($botToken)) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(8)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => (int)$chatId,
                    'text' => $validated['message'],
                    'parse_mode' => 'HTML',
                ]);

                if ($response->successful()) {
                    AuditLog::logAction(
                        $request->user()?->id,
                        'customer_care_telegram_sent',
                        Customer::class,
                        $customer?->id,
                        null,
                        [
                            'chat_id' => $chatId,
                            'message' => $validated['message'],
                            'customer_name' => $customer?->full_name,
                        ]
                    );

                    return response()->json([
                        'success' => true,
                        'channel' => 'telegram',
                        'message' => 'የቴሌግራም መልእክቱ ለደንበኛው በተሳካ ሁኔታ ተልኳል!',
                        'chat_id' => $chatId,
                    ]);
                }
            } catch (\Throwable $th) {
                \Illuminate\Support\Facades\Log::warning("1-to-1 Telegram error, falling back to SMS: " . $th->getMessage());
            }
        }

        // Fallback or route phone-only contacts to SMS safely without throwing errors
        if (!empty($phone)) {
            $cleanPhone = SmsService::normalizeEthiopianPhone($phone);
            $smsResult = SmsService::sendDirectSms($cleanPhone, $validated['message']);

            AuditLog::logAction(
                $request->user()?->id,
                'customer_care_telegram_routed_to_sms',
                Customer::class,
                $customer?->id,
                null,
                [
                    'phone' => $cleanPhone,
                    'message' => $validated['message'],
                    'customer_name' => $customer?->full_name,
                    'sms_result' => $smsResult,
                ]
            );

            return response()->json([
                'success' => true,
                'channel' => 'sms_fallback_logged',
                'message' => 'ደንበኛው የቴሌግራም መለያ የለውም፤ መልእክቱ በ SMS አልተላከም — በሲስተም መዝገብ ብቻ ተቀምጧል። በስልክዎ ይላኩት።',
                'phone' => $cleanPhone,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'ደንበኛው የቴሌግራም መለያም ሆነ ስልክ ቁጥር አልተገኘለትም!',
        ], 422);
    }
}
