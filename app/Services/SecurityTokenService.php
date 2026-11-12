<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SecurityTokenService
{
    /**
     * Get the authoritative application signing key.
     * Fails securely in production if APP_KEY is absent.
     */
    private static function getSigningKey(): string
    {
        $key = config('app.key');

        if (empty($key)) {
            if (app()->environment('production')) {
                throw new \RuntimeException('SecurityTokenService: APP_KEY is missing. Application cannot sign or verify tokens securely in production.');
            }
            // Non-production test fallback if key has not been generated
            return 'meash-local-development-fallback-key-32-chars!!';
        }

        // Support Laravel's base64-encoded application keys
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return (string)$key;
    }

    /**
     * Generate an expiring signed tracking token for an order with replay-safe jti.
     */
    public static function generateTrackingToken(Order $order, int $validDays = 14): string
    {
        $payload = [
            'type' => 'tracking',
            'jti' => Str::random(24),
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'exp' => Carbon::now()->addDays($validDays)->timestamp,
        ];

        return self::signPayload($payload);
    }

    /**
     * Verify a tracking token. Returns payload array if valid, unrevoked, and not expired; null otherwise.
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

        // Check expiration
        if (($payload['exp'] ?? 0) < Carbon::now()->timestamp) {
            return null; // Expired
        }

        // Check revocation
        if (!empty($payload['jti']) && Cache::has("revoked_token_{$payload['jti']}")) {
            return null; // Revoked
        }

        return $payload;
    }

    /**
     * Generate an expiring signed action token (e.g., reschedule) for an order with replay-safe jti.
     */
    public static function generateActionToken(Order $order, string $action = 'reschedule', int $validDays = 7): string
    {
        $payload = [
            'type' => 'action',
            'action' => $action,
            'jti' => Str::random(24),
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'exp' => Carbon::now()->addDays($validDays)->timestamp,
        ];

        return self::signPayload($payload);
    }

    /**
     * Verify an action token for an expected action. Returns order_id if valid and unrevoked, null otherwise.
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

        if (!empty($payload['jti']) && Cache::has("revoked_token_{$payload['jti']}")) {
            return null; // Revoked
        }

        return (int)($payload['order_id'] ?? 0);
    }

    /**
     * Revoke a token immediately so it cannot be replayed.
     */
    public static function revokeToken(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        $payload = self::verifyPayload($token);
        if (!$payload || empty($payload['jti'])) {
            return false;
        }

        $exp = (int)($payload['exp'] ?? Carbon::now()->addDays(7)->timestamp);
        $ttl = max(60, $exp - Carbon::now()->timestamp);

        Cache::put("revoked_token_{$payload['jti']}", true, $ttl);
        return true;
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
     * Sign an arbitrary payload with HMAC-SHA256 using the application signing key.
     */
    private static function signPayload(array $payload): string
    {
        $json = json_encode($payload);
        $encodedPayload = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $key = self::getSigningKey();
        $signature = hash_hmac('sha256', $encodedPayload, $key);
        $encodedSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $encodedPayload . '.' . $encodedSignature;
    }

    /**
     * Verify and decode a signed payload using constant-time signature comparison.
     */
    private static function verifyPayload(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$encodedPayload, $encodedSignature] = $parts;

        $key = self::getSigningKey();
        $expectedSig = hash_hmac('sha256', $encodedPayload, $key);
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

