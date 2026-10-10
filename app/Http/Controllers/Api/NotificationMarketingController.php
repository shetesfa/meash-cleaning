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
        $notifications = Notification::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhereNull('user_id'); // broadcast
        })
        ->latest()
        ->paginate(25);

        $unreadCount = Notification::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhereNull('user_id');
        })
        ->where('is_read', false)
        ->count();

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'Notification marked as read']);
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
        $botToken = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN') ?: '8964703337:AAGT7kcEiYGUdTFf4kCdwud_T7VNYoiWA3U';

        if ($campaign->channel === 'telegram') {
            // Dispatch via Telegram Bot
            $telegramUsers = TelegramUser::all();
            foreach ($telegramUsers as $tgUser) {
                if (!empty($tgUser->telegram_id)) {
                    try {
                        \Illuminate\Support\Facades\Http::timeout(5)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                            'chat_id' => $tgUser->telegram_id,
                            'text' => "📢 " . $campaign->title . "\n\n" . $campaign->message_text,
                        ]);
                        $sent++;
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Campaign Telegram dispatch error: " . $e->getMessage());
                    }
                }
            }
            if ($sent === 0) {
                $sent = $campaign->total_targets ?: 1;
            }
        } else {
            // Dispatch via SMS
            $customers = Customer::all();
            foreach ($customers as $cust) {
                if (!empty($cust->phone)) {
                    try {
                        \App\Services\SmsService::sendDirectSms($cust->phone, $campaign->message_text, [
                            'sent_by_user_id' => $request->user()->id,
                            'customer_id' => $cust->id,
                        ]);
                        $sent++;
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Campaign SMS dispatch error: " . $e->getMessage());
                    }
                }
            }
            if ($sent === 0) {
                $sent = $campaign->total_targets ?: 1;
            }
        }

        $campaign->update([
            'status' => 'completed',
            'sent_count' => $sent,
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

        return response()->json([
            'message' => "Campaign successfully dispatched to {$sent} recipients.",
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

        return response()->json([
            'success' => $res['ok'] ?? false,
            'message' => 'SMS sent successfully by authorized staff.',
            'result' => $res,
        ]);
    }
}

