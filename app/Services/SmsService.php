<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Sends booking confirmation SMS to customer
     */
    public static function sendBookingConfirmation(Order $order): array
    {
        $order->loadMissing(['customer', 'items']);
        $phone = $order->customer ? $order->customer->phone : null;
        $name = $order->customer ? $order->customer->full_name : 'ደንበኛችን';

        if (empty($phone)) {
            return ['ok' => false, 'error' => 'Customer phone is missing'];
        }

        $formattedPhone = self::normalizeEthiopianPhone($phone);
        $dateStr = $order->appointment_date ? $order->appointment_date->format('Y-m-d') : date('Y-m-d');
        $slotStr = $order->appointment_time_slot ?: 'ቀን';
        $orderNo = $order->order_number;

        $msg = "ውድ {$name}፣ በሜሽ የፅዳት አገልግሎት የያዙት ቀጠሮ ቁጥር {$orderNo} በተሳካ ሁኔታ ተመዝግቧል:: የቀጠሮ ቀን: {$dateStr} ({$slotStr}):: የሪሴፕሽን ስልክ: 0911000002:: Mesh Cleaning Solution";

        return self::sendDirectSms($formattedPhone, $msg, [
            'event' => 'booking_confirmed',
            'order_id' => $order->id,
            'order_number' => $orderNo,
        ]);
    }

    /**
     * Sends SMS when a cleaning team is dispatched to customer location
     */
    public static function sendTeamDispatched(Order $order): array
    {
        $order->loadMissing(['customer', 'assignedTeam']);
        $phone = $order->customer ? $order->customer->phone : null;
        $name = $order->customer ? $order->customer->full_name : 'ደንበኛችን';
        $teamName = $order->assignedTeam ? $order->assignedTeam->name : 'የፅዳት ቡድናችን';

        if (empty($phone)) return ['ok' => false, 'error' => 'No phone number'];

        $formattedPhone = self::normalizeEthiopianPhone($phone);
        $msg = "ውድ {$name}፣ የሜሽ የፅዳት ቡድን ({$teamName}) ወደ እርስዎ አድራሻ በመጓዝ ላይ ነው:: ለጥያቄ: 0911000002:: Mesh Cleaning";

        return self::sendDirectSms($formattedPhone, $msg, [
            'event' => 'team_dispatched',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);
    }

    /**
     * Sends SMS when a cleaning job is completed
     */
    public static function sendJobCompleted(Order $order): array
    {
        $order->loadMissing(['customer']);
        $phone = $order->customer ? $order->customer->phone : null;
        $name = $order->customer ? $order->customer->full_name : 'ደንበኛችን';

        if (empty($phone)) return ['ok' => false, 'error' => 'No phone number'];

        $formattedPhone = self::normalizeEthiopianPhone($phone);
        $msg = "ውድ {$name}፣ የፅዳት አገልግሎቱ ተጠናቋል:: ስለመረጡን እናመሰግናለን! አስተያየትዎን ወይም ደረጃዎን በ 0911000002 ያጋሩን:: Mesh Cleaning";

        return self::sendDirectSms($formattedPhone, $msg, [
            'event' => 'job_completed',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);
    }

    /**
     * Sends SMS when an order or cleaning job is postponed or rescheduled
     */
    public static function sendOrderPostponed(Order $order, string $reason, string $newDate, string $newSlot): array
    {
        $order->loadMissing(['customer']);
        $phone = $order->customer ? $order->customer->phone : null;
        $name = $order->customer ? $order->customer->full_name : 'ደንበኛችን';

        if (empty($phone)) return ['ok' => false, 'error' => 'No phone number'];

        $formattedPhone = self::normalizeEthiopianPhone($phone);
        $timeSlotAm = match($newSlot) {
            'morning' => 'ጠዋት (ከ 2:00 - 6:00)',
            'afternoon' => 'ከሰዓት (ከ 7:00 - 11:00)',
            default => $newSlot,
        };

        try {
            $eth = EthiopianCalendarService::toEthiopian(\Carbon\Carbon::parse($newDate));
            $ethDateStr = $eth['formatted_am'];
        } catch (\Throwable $e) {
            $ethDateStr = $newDate;
        }

        $orderNo = $order->order_number;
        $msg = "ውድ {$name}፣ የትዕዛዝ ቁጥር {$orderNo} ቀጠሮዎ በ\"{$reason}\" ምክንያት ወደ {$newDate} ({$ethDateStr}) {$timeSlotAm} ተላልፏል:: ለተፈጠረው መስተጓጎል ይቅርታ እንጠይቃለን:: ለበለጠ መረጃ: 0911000002:: Mesh Cleaning Solution";

        return self::sendDirectSms($formattedPhone, $msg, [
            'event' => 'order_postponed',
            'order_id' => $order->id,
            'order_number' => $orderNo,
            'reason' => $reason,
            'new_date' => $newDate,
            'new_slot' => $newSlot,
        ]);
    }

    /**
     * Sends a direct test SMS to verify carrier delivery
     */
    public static function sendTestSms(string $phone, ?string $customMessage = null): array
    {
        $formattedPhone = self::normalizeEthiopianPhone($phone);
        $msg = $customMessage ?: "የሙከራ የኤስኤምኤስ (Test SMS) ከሜሽ የፅዳት አገልግሎት (Mesh Cleaning Solution):: የሲስተም ግንኙነትዎ በተሳካ ሁኔታ እየሰራ ነው! ሪሴፕሽን: 0911000002";

        return self::sendDirectSms($formattedPhone, $msg, [
            'event' => 'manual_test_sms',
            'tested_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Core SMS dispatcher with gateway support (AfroMessage / Africa's Talking / Custom HTTP) & audit logging
     */
    public static function sendDirectSms(string $phone, string $message, array $metadata = []): array
    {
        $provider = env('SMS_PROVIDER', 'afromessage');
        $afroToken = env('AFROMESSAGE_TOKEN', env('SMS_API_KEY'));
        $gatewayUrl = env('SMS_GATEWAY_URL');
        $senderId = env('SMS_SENDER_ID', 'MESH');

        $deliveryStatus = 'simulated';
        $providerResponse = null;
        $isLiveCarrier = false;

        // 1. Native AfroMessage Gateway (Ethiopia)
        if (!empty($afroToken)) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $afroToken,
                    'Content-Type' => 'application/json',
                ])->timeout(10)->post('https://api.afromessage.com/api/send', [
                    'to' => $phone,
                    'message' => $message,
                    'sender' => $senderId,
                ]);

                $deliveryStatus = $response->successful() ? 'delivered' : 'gateway_failed';
                $providerResponse = $response->json() ?: $response->body();
                $isLiveCarrier = $response->successful();
            } catch (\Exception $e) {
                $deliveryStatus = 'network_error';
                $providerResponse = $e->getMessage();
            }
        }
        // 2. Generic Gateway URL Fallback
        elseif (!empty($gatewayUrl)) {
            try {
                $response = Http::timeout(10)->post($gatewayUrl, [
                    'to' => $phone,
                    'message' => $message,
                    'sender' => $senderId,
                    'api_key' => env('SMS_API_KEY'),
                ]);
                $deliveryStatus = $response->successful() ? 'delivered' : 'gateway_failed';
                $providerResponse = $response->body();
                $isLiveCarrier = $response->successful();
            } catch (\Exception $e) {
                $deliveryStatus = 'network_error';
                $providerResponse = $e->getMessage();
            }
        } else {
            // Simulated local mode (logged to storage/logs/sms.log & DB audit_logs)
            $deliveryStatus = 'logged_locally';
            $providerResponse = 'Simulated mode: Add AFROMESSAGE_TOKEN or SMS_GATEWAY_URL in .env for live carrier delivery to real SIM cards.';
        }

        // Dedicated SMS Log File
        $logEntry = sprintf(
            "[%s] TO: %s | STATUS: %s | CARRIER: %s | EVENT: %s | MSG: %s\n",
            now()->toIso8601String(),
            $phone,
            $deliveryStatus,
            $isLiveCarrier ? 'LIVE' : 'SIMULATED',
            $metadata['event'] ?? 'general',
            $message
        );
        @file_put_contents(storage_path('logs/sms.log'), $logEntry, FILE_APPEND);

        // Database Audit Log
        try {
            AuditLog::logAction(
                auth()->id() ?? 1,
                'sms_notification_sent',
                'customer_sms',
                $metadata['order_id'] ?? 0,
                null,
                [
                    'phone' => $phone,
                    'message' => $message,
                    'status' => $deliveryStatus,
                    'is_live' => $isLiveCarrier,
                    'metadata' => $metadata,
                    'provider_response' => $providerResponse,
                ]
            );
        } catch (\Throwable $t) {
            Log::warning("Could not log SMS audit: " . $t->getMessage());
        }

        return [
            'ok' => in_array($deliveryStatus, ['delivered', 'logged_locally']),
            'status' => $deliveryStatus,
            'is_live' => $isLiveCarrier,
            'phone' => $phone,
            'message' => $message,
            'provider_response' => $providerResponse,
        ];
    }

    /**
     * Standardizes Ethiopian mobile numbers (09..., 07..., +251...)
     */
    public static function normalizeEthiopianPhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9+]/', '', trim($phone));

        if (str_starts_with($clean, '+251')) {
            return $clean;
        }

        if (str_starts_with($clean, '251')) {
            return '+' . $clean;
        }

        if (str_starts_with($clean, '09') || str_starts_with($clean, '07')) {
            return '+251' . substr($clean, 1);
        }

        if (str_starts_with($clean, '9') || str_starts_with($clean, '7')) {
            return '+251' . $clean;
        }

        return $clean;
    }
}