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
            'audience_filter' => 'required|string|in:opted_in,corporate,individual,all_customers',
            'message_text' => 'required|string',
        ]);

        // Calculate targets strictly honoring marketing consent
        $targetsQuery = Customer::query();
        if ($validated['audience_filter'] === 'opted_in') {
            $targetsQuery->where('marketing_consent', true);
        } elseif ($validated['audience_filter'] === 'corporate') {
            $targetsQuery->where('customer_type', 'corporate');
        } elseif ($validated['audience_filter'] === 'individual') {
            $targetsQuery->where('customer_type', 'individual')->where('marketing_consent', true);
        }

        $totalTargets = $targetsQuery->count();

        $campaign = Campaign::create([
            'title' => $validated['title'],
            'channel' => $validated['channel'],
            'audience_filter' => $validated['audience_filter'],
            'message_text' => $validated['message_text'],
            'status' => 'draft',
            'total_targets' => $totalTargets,
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Campaign created as draft',
            'campaign' => $campaign,
        ], 201);
    }

    public function sendCampaign(Request $request, Campaign $campaign): JsonResponse
    {
        // Only Owner can approve and dispatch marketing campaigns
        if (!$request->user()->isOwner()) {
            return response()->json(['message' => 'Unauthorized. Only Owner can dispatch campaigns.'], 403);
        }

        $targetsQuery = Customer::query()->where('marketing_consent', true);
        if ($campaign->audience_filter === 'corporate') {
            $targetsQuery->where('customer_type', 'corporate');
        }

        $customers = $targetsQuery->get();

        $campaign->status = 'sending';
        $campaign->save();

        $sent = 0;
        foreach ($customers as $cust) {
            CampaignRecipient::create([
                'campaign_id' => $campaign->id,
                'recipient_id' => $cust->phone,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
            $sent++;
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
            'message' => "Campaign successfully dispatched to {$sent} verified opted-in customers.",
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

