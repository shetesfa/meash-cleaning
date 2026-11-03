<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Carbon;

class SecurityTokenService
{
    /**
     * Generate an expiring signed tracking token for an order.
     */
    public static function generateTrackingToken(Order $order, int $validDays = 14): string
    {
        $payload = [
            'type' => 'tracking',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'exp' => Carbon::now()->addDays($validDays)->timestamp,
        ];

        return self::signPayload($payload);
    }

    /**
     * Verify a tracking token. Returns payload array if valid and not expired, null otherwise.
     */
    public static function verifyTrackingToken(?string $token): ?array
    {
        if (empty($token)) {
            return null;
        }

        $payload = self::verifyPayload($token);
        if (!$payload || ($payload['type'] ?? '') !== 'tracking') {
            return null;
        }

        if (($payload['exp'] ?? 0) < Carbon::now()->timestamp) {
            return null; // Expired
        }

        return $payload;
    }

    /**
     * Generate an expiring signed action token (e.g., reschedule) for an order.
     */
    public static function generateActionToken(Order $order, string $action = 'reschedule', int $validDays = 7): string
    {
        $payload = [
            'type' => 'action',
            'action' => $action,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'exp' => Carbon::now()->addDays($validDays)->timestamp,
        ];

        return self::signPayload($payload);
    }

    /**
     * Verify an action token for an expected action. Returns order_id if valid, null otherwise.
     */
    public static function verifyActionToken(?string $token, string $expectedAction = 'reschedule'): ?int
    {
        if (empty($token)) {
            return null;
        }

        $payload = self::verifyPayload($token);
        if (!$payload || ($payload['type'] ?? '') !== 'action') {
            return null;
        }

        if (($payload['action'] ?? '') !== $expectedAction) {
            return null;
        }

        if (($payload['exp'] ?? 0) < Carbon::now()->timestamp) {
            return null; // Expired
        }

        return (int)($payload['order_id'] ?? 0);
    }

    /**
     * Normalize an Ethiopian phone number to canonical 09XXXXXXXX format.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '251')) {
            $digits = '0' . substr($digits, 3);
        }

        return $digits;
    }

    /**
     * Sign an arbitrary payload with HMAC-SHA256 using APP_KEY.
     */
    private static function signPayload(array $payload): string
    {
        $json = json_encode($payload);
        $encodedPayload = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encodedPayload, config('app.key') ?: 'meash-default-secure-salt');
        $encodedSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $encodedPayload . '.' . $encodedSignature;
    }

    /**
     * Verify and decode a signed payload.
     */
    private static function verifyPayload(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$encodedPayload, $encodedSignature] = $parts;

        $expectedSig = hash_hmac('sha256', $encodedPayload, config('app.key') ?: 'meash-default-secure-salt');
        $expectedEncodedSig = rtrim(strtr(base64_encode($expectedSig), '+/', '-_'), '=');

        if (!hash_equals($expectedEncodedSig, $encodedSignature)) {
            return null; // Signature mismatch
        }

        $json = base64_decode(strtr($encodedPayload, '-_', '+/'));
        if (!$json) {
            return null;
        }

        $payload = json_decode($json, true);
        return is_array($payload) ? $payload : null;
    }
}
