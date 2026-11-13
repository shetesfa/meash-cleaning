<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\TelegramUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationMarketingController extends Controller
{
    public function notifications(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $readBroadcastIds = \App\Models\NotificationRead::where('user_id', $userId)
            ->pluck('notification_id')
            ->toArray();

        $notifications = Notification::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhereNull('user_id'); // broadcast
        })
        ->latest()
        ->paginate(25);

        // Authoritative per-user read state: transform broadcast notifications for current user
        $notifications->getCollection()->transform(function ($n) use ($readBroadcastIds) {
            if ($n->user_id === null) {
                $n->is_read = in_array($n->id, $readBroadcastIds, true);
            }
            return $n;
        });

        $unreadCount = Notification::where(function ($q) use ($userId, $readBroadcastIds) {
            $q->where(function ($sub) use ($userId) {
                $sub->where('user_id', $userId)->where('is_read', false);
            })->orWhere(function ($sub) use ($readBroadcastIds) {
                $sub->whereNull('user_id');
                if (!empty($readBroadcastIds)) {
                    $sub->whereNotIn('id', $readBroadcastIds);
                }
            });
        })->count();

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $currentUser = $request->user();

        // 1. Personal Notification: Strict IDOR prevention
        if ($notification->user_id !== null) {
            if ($notification->user_id !== $currentUser->id) {
                return response()->json([
                    'message' => 'Unauthorized: You cannot mark another user\'s notification as read.',
                ], 403);
            }

            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            return response()->json(['message' => 'Notification marked as read']);
        }

        // 2. Global Broadcast Notification: Per-user read state without global mutation
        \App\Models\NotificationRead::firstOrCreate([
            'notification_id' => $notification->id,
            'user_id' => $currentUser->id,
        ], [
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'Broadcast notification marked as read for current user']);
    }

    public function campaigns(): JsonResponse
    {
        $campaigns = Campaign::with('creator:id,name')
            ->latest()
            ->paginate(20);

        return response()->json($campaigns);
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'channel' => 'required|string|in:telegram,sms,in_app',
            'audience_filter' => 'nullable|string',
            'message_text' => 'required|string',
        ]);

        $filter = $validated['audience_filter'] ?: 'all_customers';

        // Calculate targets
        $targetsQuery = Customer::query();
        if ($filter === 'corporate') {
            $targetsQuery->where('customer_type', 'corporate');
        } elseif ($filter === 'vip') {
            $targetsQuery->where('tags', 'like', '%vip%');
        }

        $totalTargets = $targetsQuery->count();
        if ($validated['channel'] === 'telegram') {
            $tgCount = TelegramUser::count();
            if ($tgCount > 0) {
                $totalTargets = max($totalTargets, $tgCount);
            }
        }
        if ($totalTargets === 0) {
            $totalTargets = 1;
        }

        $campaign = Campaign::create([
            'title' => $validated['title'],
            'channel' => $validated['channel'],
            'audience_filter' => $filter,
            'message_text' => $validated['message_text'],
            'status' => 'draft',
            'total_targets' => $totalTargets,
            'sent_count' => 0,
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Campaign created as draft',
            'campaign' => $campaign,
        ], 201);
    }

    public function sendCampaign(Request $request, Campaign $campaign): JsonResponse
    {
        // Only Owner or Reception can dispatch marketing campaigns
        if (!$request->user()->isOwner() && $request->user()->role !== 'reception') {
            return response()->json(['message' => 'Unauthorized. Only Owner or Reception can dispatch campaigns.'], 403);
        }

        $campaign->status = 'sending';
        $campaign->save();

        $sent = 0;
        $failed = 0;
        $botToken = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN');

        if ($campaign->channel === 'telegram') {
            // Dispatch via Telegram Bot — count ONLY a confirmed Telegram ok.
            $telegramUsers = TelegramUser::all();
            foreach ($telegramUsers as $tgUser) {
                if (!empty($tgUser->telegram_id)) {
                    try {
                        // Batch 11a: per-recipient {name} substitution (same
                        // pattern as the SMS-Bridge path). Previously the
                        // raw template went out with literal "{name}".
                        $recipientName = trim((string)($tgUser->name ?? '')) ?: 'ውድ ደንበኛችን';
                        $personalizedText = " " . str_replace(['{name}', '{ስም}', '{first_name}'], $recipientName, $campaign->title) . "\n\n" . str_replace(['{name}', '{ስም}', '{first_name}'], $recipientName, $campaign->message_text);
                        $response = \Illuminate\Support\Facades\Http::timeout(5)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                            'chat_id' => $tgUser->telegram_id,
                            'text' => $personalizedText,
                        ]);
                        if ($response->successful() && ($response->json('ok') ?? false)) {
                            $sent++;
                        } else {
                            $failed++;
                        }
                    } catch (\Throwable $e) {
                        $failed++;
                        \Illuminate\Support\Facades\Log::warning("Campaign Telegram dispatch error: " . $e->getMessage());
                    }
                }
            }
            // No substitution: 0 sent means 0 sent.
        } else {
            // Dispatch via SMS
            $customers = Customer::all();
            foreach ($customers as $cust) {
                if (!empty($cust->phone)) {
                    try {
                        // Batch 11a: per-recipient {name} substitution here too.
                        $recipientName = trim((string)($cust->full_name ?? '')) ?: 'ውድ ደንበኛችን';
                        $personalizedText = str_replace(['{name}', '{ስም}', '{first_name}'], $recipientName, $campaign->message_text);
                        $smsResult = \App\Services\SmsService::sendDirectSms($cust->phone, $personalizedText, [
                            'sent_by_user_id' => $request->user()->id,
                            'customer_id' => $cust->id,
                        ]);
                        // Count ONLY a real live send. Until SmsService reports
                        // honestly, is_live is false for logged SMS and
                        // nothing is counted as sent.
                        if (!empty($smsResult['is_live'])) {
                            $sent++;
                        } else {
                            $failed++;
                        }
                    } catch (\Throwable $e) {
                        $failed++;
                        \Illuminate\Support\Facades\Log::warning("Campaign SMS dispatch error: " . $e->getMessage());
                    }
                }
            }
            // No substitution: 0 sent means 0 sent.
        }

        $campaign->update([
            'status' => 'completed',
            'sent_count' => $sent,
            'failed_count' => $failed,
            'sent_at' => now(),
        ]);

        AuditLog::logAction(
            $request->user()->id,
            'campaign_dispatched',
            Campaign::class,
            $campaign->id,
            null,
            ['total_sent' => $sent]
        );

        $dispatchMessage = $campaign->channel === 'sms' && $sent === 0
            ? "Campaign logged for {$failed} recipients (SMS gateway not connected — nothing was delivered)."
            : "Campaign successfully dispatched to {$sent} recipients.";

        return response()->json([
            'message' => $dispatchMessage,
            'campaign' => $campaign,
        ]);
    }

    public function sendDirectSms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string|max:500',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $res = \App\Services\SmsService::sendDirectSms($validated['phone'], $validated['message'], [
            'sent_by_user_id' => $request->user()->id,
            'customer_id' => $validated['customer_id'] ?? null,
        ]);

        AuditLog::logAction(
            $request->user()->id,
            'direct_sms_sent',
            Customer::class,
            $validated['customer_id'] ?? null,
            null,
            ['phone' => $validated['phone'], 'message' => $validated['message']]
        );

        $status = $res['status'] ?? 'unknown';
        $isLive = !empty($res['is_live']);

        if ($status === 'logged_locally') {
            // Honest success: the request succeeded (it was logged), but
            // nothing was delivered — there is no SMS gateway. Return
            // success:true so the UI does not treat a log as an error, with
            // a message that says exactly what happened.
            $message = 'ተመዝግቧል (logged) — SMS gateway የለም፤ መልእክቱ አልተላከም። በስልክ SMS Bridge ወይም በ Messages App ይላኩት።';
            return response()->json(['success' => true, 'status' => $status, 'message' => $message, 'result' => $res]);
        } elseif ($status === 'delivered') {
            $message = 'SMS sent successfully to live mobile carrier.';
        } elseif ($status === 'gateway_failed') {
            $message = 'Failed to deliver SMS: Gateway rejected request.';
        } elseif ($status === 'network_error') {
            $message = 'Failed to deliver SMS: Network error or timeout.';
        } else {
            $message = 'SMS dispatch completed with status: ' . $status;
        }

        return response()->json([
            'success' => $isLive,
            'status' => $status,
            'message' => $message,
            'result' => $res,
        ]);
    }

    public function smsStatus(): JsonResponse
    {
        return response()->json(\App\Services\SmsService::getGatewayStatus());
    }

}
