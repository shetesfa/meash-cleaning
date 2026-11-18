<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\TelegramController;
use App\Models\CleaningTeam;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductionHardeningSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_settings_never_exposes_bot_token_or_secrets(): void
    {
        WebsiteSetting::setVal('telegram_bot_token', '999999:VERY_SECRET_BOT_TOKEN_123', 'telegram');
        WebsiteSetting::setVal('admin_secret', 'ultra-secret-admin-key', 'security');
        WebsiteSetting::setVal('company_name', 'MEASH CLEANING SOLUTION', 'general');

        $response = $this->getJson('/api/public/settings');
        $response->assertStatus(200);

        $settings = $response->json('settings');
        $this->assertEquals('MEASH CLEANING SOLUTION', $settings['company_name'] ?? null);

        // Crucial security checks: bot token and secrets must NEVER be returned
        $this->assertArrayNotHasKey('telegram_bot_token', $settings);
        $this->assertArrayNotHasKey('admin_secret', $settings);

        $jsonString = $response->getContent();
        $this->assertStringNotContainsString('VERY_SECRET_BOT_TOKEN', $jsonString);
        $this->assertStringNotContainsString('ultra-secret-admin-key', $jsonString);
    }

    public function test_unauthorized_role_cannot_update_order_status(): void
    {
        $salesUser = User::factory()->create(['role' => 'sales']);
        $customer = Customer::create(['full_name' => 'Sara Desta', 'phone' => '0911223344']);
        $order = Order::create([
            'order_number' => 'ORD-SEC-01',
            'customer_id' => $customer->id,
            'address' => 'Bole Medhanialem',
            'status' => 'confirmed',
            'appointment_date' => now()->toDateString(),
            'total_amount' => 1500,
        ]);

        Sanctum::actingAs($salesUser);

        // Sales is not authorized to update order status
        $res = $this->postJson("/api/orders/{$order->id}/status", [
            'order_status' => 'completed',
        ]);
        $res->assertStatus(403);
    }

    public function test_cleaner_cannot_submit_payment_for_unassigned_team_order(): void
    {
        $cleanerUser = User::factory()->create(['role' => 'cleaner']);
        $otherTeam = CleaningTeam::create([
            'team_name' => 'Team Bravo',
            'phone' => '0911000099',
            'status' => 'available',
        ]);
        $customer = Customer::create(['full_name' => 'Dawit Mengistu', 'phone' => '0911334455']);
        $order = Order::create([
            'order_number' => 'ORD-SEC-02',
            'customer_id' => $customer->id,
            'assigned_team_id' => $otherTeam->id,
            'address' => 'Ayat Zone 2',
            'status' => 'cleaning',
            'appointment_date' => now()->toDateString(),
            'total_amount' => 2000,
        ]);

        Sanctum::actingAs($cleanerUser);

        $res = $this->postJson("/api/orders/{$order->id}/submit-payment", [
            'payment_method' => 'cash',
            'amount' => 2000,
        ]);
        $res->assertStatus(403);
    }

    public function test_sales_role_cannot_submit_or_approve_payments(): void
    {
        $salesUser = User::factory()->create(['role' => 'sales']);
        $customer = Customer::create(['full_name' => 'Helen Tsegaye', 'phone' => '0911445566']);
        $order = Order::create([
            'order_number' => 'ORD-SEC-03',
            'customer_id' => $customer->id,
            'address' => 'CMC Square',
            'status' => 'confirmed',
            'appointment_date' => now()->toDateString(),
            'total_amount' => 1800,
        ]);

        Sanctum::actingAs($salesUser);

        $submitRes = $this->postJson("/api/orders/{$order->id}/submit-payment", [
            'payment_method' => 'cash',
            'amount' => 1800,
        ]);
        $submitRes->assertStatus(403);

        $approveRes = $this->postJson("/api/orders/{$order->id}/approve-payment", [
            'amount' => 1800,
        ]);
        $approveRes->assertStatus(403);
    }

    public function test_finance_role_can_approve_and_record_payments_and_view_profit_report(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance']);
        $customer = Customer::create(['full_name' => 'Kidus Yohannes', 'phone' => '0911556677']);
        $order = Order::create([
            'order_number' => 'ORD-SEC-04',
            'customer_id' => $customer->id,
            'address' => 'Meganagna Plaza',
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'appointment_date' => now()->toDateString(),
            'total_amount' => 2500,
        ]);

        Sanctum::actingAs($financeUser);

        // 1. Finance can approve payment
        $approveRes = $this->postJson("/api/orders/{$order->id}/approve-payment", [
            'amount' => 2500,
            'payment_method' => 'telebirr',
            'reference_number' => 'TB-SEC-9988',
        ]);
        $approveRes->assertStatus(200);
        $this->assertEquals('paid', $order->fresh()->payment_status);

        // 2. Finance can view profit report
        $reportRes = $this->getJson('/api/finance/profit-report');
        $reportRes->assertStatus(200);

        // 3. Finance can view payments list
        $paymentsRes = $this->getJson('/api/finance/payments');
        $paymentsRes->assertStatus(200);
    }

    public function test_telegram_init_data_with_expired_auth_date_is_rejected(): void
    {
        $botToken = 'test-token-hardening';
        $controller = new TelegramController();

        $userData = json_encode(['id' => 1234567, 'first_name' => 'ExpiredUser']);
        // 2 days in the past (older than 86400 seconds)
        $expiredAuthDate = (string)(time() - 172800);

        $params = [
            'auth_date' => $expiredAuthDate,
            'query_id' => 'Q_EXPIRED',
            'user' => $userData,
        ];
        ksort($params);

        $dataCheckArr = [];
        foreach ($params as $k => $v) {
            $dataCheckArr[] = "{$k}={$v}";
        }
        $dataCheckString = implode("\n", $dataCheckArr);
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);
        $initData = http_build_query(array_merge($params, ['hash' => $hash]));

        $extractedUser = null;
        $isValid = $controller->verifyTelegramInitData($initData, $botToken, $extractedUser);

        // Must reject expired payload
        $this->assertFalse($isValid);
        $this->assertNull($extractedUser);
    }

    public function test_telegram_webhook_clears_dedup_cache_on_processing_failure(): void
    {
        Config::set('services.telegram.webhook_secret', 'valid-secret-token');

        // Update ID to test
        $updateId = 77665544;
        $cacheKey = "tg_update_{$updateId}";

        // Verify cache is currently clear
        Cache::forget($cacheKey);

        // Send a malformed update that will trigger an exception during handling
        $res = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'valid-secret-token',
        ])->postJson('/api/telegram/webhook', [
            'update_id' => $updateId,
            'message' => 'not-an-array-triggers-exception',
        ]);

        // Status is 500 error
        $res->assertStatus(500);

        // Cache for update_id must be forgotten so Telegram can retry
        $this->assertFalse(Cache::has($cacheKey));
    }

    // Prompt 12: HSTS only on HTTPS + production; X-XSS-Protection gone.

    public function test_hsts_sent_on_https_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        // Full https URL: Symfony only marks the request secure when
        // the request URL itself is https (server HTTPS vars are
        // overwritten from the URL scheme).
        $res = $this->get('https://meashcleaningsolution.com.et/');
        $res->assertStatus(200);
        $res->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_hsts_not_sent_on_plain_http_even_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        // Explicit http URL: $this->get('/') would inherit
        // config('app.url'), which is https in CI (.env.example), and
        // Laravel would then build a secure request.
        $res = $this->get('http://localhost/');
        $res->assertStatus(200);
        $res->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_not_sent_on_https_outside_production(): void
    {
        // Default test env is "testing": HTTPS alone must not pin HSTS.
        $res = $this->get('https://meashcleaningsolution.com.et/');
        $res->assertStatus(200);
        $res->assertHeaderMissing('Strict-Transport-Security');
    }

    // Q5: behind an HTTPS proxy, X-Forwarded-Proto: https must make the
    // request secure — url('/') is https and HSTS is sent in production.
    public function test_trusted_proxy_https_forwarding(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        // Plain-http URL, as the proxy forwards it, plus the header.
        $res = $this->get('http://meashcleaningsolution.com.et/', ['X-Forwarded-Proto' => 'https']);
        $res->assertStatus(200);
        $res->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        // og:url is built from url('/'), so it must be https too.
        $res->assertSee('<meta property="og:url" content="https://meashcleaningsolution.com.et">', false);
    }

    public function test_x_xss_protection_header_is_removed(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $res->assertHeaderMissing('X-XSS-Protection');
    }
}
