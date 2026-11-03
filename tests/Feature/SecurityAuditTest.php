<?php

namespace Tests\Feature;

use App\Models\CleaningTeam;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\User;
use App\Services\SecurityTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test 1: Unauthenticated requests to protected endpoints are rejected (401).
     */
    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/orders')->assertStatus(401);
        $this->getJson('/api/customers')->assertStatus(401);
        $this->getJson('/api/finance/expenses')->assertStatus(401);
        $this->getJson('/api/teams')->assertStatus(401);
        $this->postJson('/api/orders', [])->assertStatus(401);
    }

    /**
     * Test 2: Role authorization - cleaners cannot access reception/owner endpoints.
     */
    public function test_cleaners_cannot_access_restricted_endpoints(): void
    {
        $cleaner = User::factory()->create(['role' => 'cleaner']);

        // Cleaners cannot view or manage customers
        $this->actingAs($cleaner, 'sanctum')
            ->getJson('/api/customers')
            ->assertStatus(403);

        // Cleaners cannot access finance expenses
        $this->actingAs($cleaner, 'sanctum')
            ->getJson('/api/finance/expenses')
            ->assertStatus(403);

        // Cleaners cannot create orders
        $this->actingAs($cleaner, 'sanctum')
            ->postJson('/api/orders', [
                'customer_name' => 'Test User',
                'customer_phone' => '0911223344',
                'address' => 'Bole',
                'appointment_date' => now()->addDay()->toDateString(),
                'items' => [['item_name' => 'Sofa', 'quantity' => 1, 'unit_price' => 500]],
            ])
            ->assertStatus(403);
    }

    /**
     * Test 3: Cleaner cross-team access rejection & IDOR prevention.
     */
    public function test_cleaner_cross_team_access_is_rejected(): void
    {
        $cleaner1 = User::factory()->create([
            'role' => 'cleaner',
        ]);
        $cleaner2 = User::factory()->create([
            'role' => 'cleaner',
        ]);

        $team1 = CleaningTeam::create([
            'team_name' => 'Alpha Team',
            'team_leader_id' => $cleaner1->id,
            'phone' => '0911000001',
            'status' => 'idle',
        ]);
        $team2 = CleaningTeam::create([
            'team_name' => 'Beta Team',
            'team_leader_id' => $cleaner2->id,
            'phone' => '0911000002',
            'status' => 'idle',
        ]);

        $customer = Customer::create([
            'customer_code' => 'MEASH-C001',
            'full_name' => 'Abebe Kebede',
            'phone' => '0911000003',
            'address' => 'Bole Medhanialem',
        ]);

        // Order assigned to Team 2
        $orderTeam2 = Order::create([
            'order_number' => 'MEASH-000099',
            'customer_id' => $customer->id,
            'assigned_team_id' => $team2->id,
            'order_status' => 'assigned',
            'payment_status' => 'unpaid',
            'appointment_date' => now()->toDateString(),
            'address' => 'Bole Medhanialem',
        ]);

        // Cleaner 1 attempts to take action on Team 2's job
        $this->actingAs($cleaner1, 'sanctum')
            ->postJson("/api/teams/jobs/{$orderTeam2->id}/action", [
                'action' => 'start',
            ])
            ->assertStatus(403);

        // Cleaner 1 attempts to update GPS for Team 2
        $this->actingAs($cleaner1, 'sanctum')
            ->postJson('/api/teams/location', [
                'team_id' => $team2->id,
                'latitude' => 9.0300,
                'longitude' => 38.7400,
            ])
            ->assertStatus(403);
    }

    /**
     * Test 4: Server-side order price recalculation ignores forged client prices.
     */
    public function test_server_side_order_price_recalculation(): void
    {
        $service = Service::create([
            'code' => 'SVC-DEEP',
            'name_en' => 'Deep House Cleaning',
            'name_am' => 'ጥልቅ የቤት ፅዳት',
            'category' => 'residential',
            'base_price' => 2500.00,
            'pricing_type' => 'fixed',
            'is_active' => true,
        ]);

        // Attacker submits unit_price: 1.00 via public website booking
        $response = $this->postJson('/api/public/book', [
            'customer_name' => 'Honest Buyer',
            'customer_phone' => '0911998877',
            'address' => 'Summit, Addis Ababa',
            'subcity' => 'Bole',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time_slot' => 'morning',
            'items' => [
                [
                    'service_id' => $service->id,
                    'item_name' => 'Deep House Cleaning',
                    'quantity' => 2,
                    'unit_price' => 1.00, // Forged client price
                ],
            ],
        ]);

        $response->assertStatus(201);
        $orderData = $response->json('order');

        // Order total must be recalculated from catalogue (2 * 2500 = 5000), NOT forged (2 * 1 = 2)
        $this->assertEquals(5000.00, (float)$orderData['total']);
        $this->assertEquals(5000.00, (float)$orderData['subtotal']);
        $this->assertEquals(2500.00, (float)$orderData['items'][0]['unit_price']);

        // Verify tokens are present
        $this->assertNotEmpty($response->json('tracking_token'));
        $this->assertNotEmpty($response->json('action_token'));
    }

    /**
     * Test 5: Public tracking privacy protection & token verification.
     */
    public function test_public_tracking_privacy_and_token_protection(): void
    {
        $customer = Customer::create([
            'customer_code' => 'MEASH-C002',
            'full_name' => 'Aster Awoke',
            'phone' => '0922334455',
            'address' => 'Private Villa, House 123, Secret Lane',
            'subcity' => 'Yeka',
        ]);

        $order = Order::create([
            'order_number' => 'MEASH-000100',
            'customer_id' => $customer->id,
            'order_status' => 'assigned',
            'payment_status' => 'paid',
            'subtotal' => 3000,
            'total' => 3000,
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time_slot' => 'morning',
            'address' => 'Private Villa, House 123, Secret Lane',
            'subcity' => 'Yeka',
        ]);

        // 1. Unauthenticated public search by order_number without token
        $publicRes = $this->postJson('/api/public/track-order', [
            'tracking_id' => $order->order_number,
        ]);

        $publicRes->assertStatus(200);
        $publicData = $publicRes->json('order');

        // Exact address, phone, and financial totals must NOT be leaked
        $this->assertArrayNotHasKey('address', $publicData);
        $this->assertArrayNotHasKey('phone', $publicData);
        $this->assertArrayNotHasKey('total', $publicData);
        $this->assertArrayNotHasKey('payment_status', $publicData);
        $this->assertFalse($publicData['has_secure_token']);

        // 2. Tracking with a valid signed token
        $validToken = SecurityTokenService::generateTrackingToken($order);
        $tokenRes = $this->postJson('/api/public/track-order', [
            'token' => $validToken,
        ]);

        $tokenRes->assertStatus(200);
        $tokenData = $tokenRes->json('order');
        $this->assertTrue($tokenData['has_secure_token']);
        $this->assertNotEmpty($tokenData['reschedule_token']);
    }

    /**
     * Test 6: Public rescheduling requires a valid signed token.
     */
    public function test_public_rescheduling_requires_signed_token(): void
    {
        $customer = Customer::create([
            'customer_code' => 'MEASH-C003',
            'full_name' => 'Dawit Tsige',
            'phone' => '0933445566',
            'address' => 'CMC',
        ]);

        $order = Order::create([
            'order_number' => 'MEASH-000101',
            'customer_id' => $customer->id,
            'order_status' => 'new',
            'payment_status' => 'unpaid',
            'appointment_date' => now()->addDay()->toDateString(),
            'address' => 'CMC',
        ]);

        // Attempting to reschedule without a token must fail with 403
        $this->postJson('/api/public/reschedule-order', [
            'order_number' => $order->order_number,
            'new_appointment_date' => now()->addDays(4)->toDateString(),
        ])->assertStatus(403);

        // Attempting to reschedule with an invalid token must fail with 403
        $this->postJson('/api/public/reschedule-order', [
            'order_number' => $order->order_number,
            'token' => 'invalid.fake.token',
            'new_appointment_date' => now()->addDays(4)->toDateString(),
        ])->assertStatus(403);

        // Rescheduling with a valid signed action token must succeed
        $actionToken = SecurityTokenService::generateActionToken($order, 'reschedule');
        $successRes = $this->postJson('/api/public/reschedule-order', [
            'order_number' => $order->order_number,
            'action_token' => $actionToken,
            'new_appointment_date' => now()->addDays(4)->toDateString(),
            'reason' => 'Family travel',
        ]);

        $successRes->assertStatus(200);
        $order->refresh();
        $this->assertEquals(now()->addDays(4)->toDateString(), $order->appointment_date->toDateString());
        $this->assertEquals('rescheduled', $order->order_status);
    }

    /**
     * Test 7: Token revocation on logout and password change.
     */
    public function test_token_revocation_on_logout_and_password_change(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@meash.et',
            'password' => Hash::make('StrongPassword123!@#'),
            'role' => 'reception',
        ]);

        // Login to get token
        $loginRes = $this->postJson('/api/auth/login', [
            'login' => 'staff@meash.et',
            'password' => 'StrongPassword123!@#',
        ]);
        $token = $loginRes->json('token');
        $this->assertNotEmpty($token);

        // Authenticated request works
        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(200);

        // Change password - enforces 12 chars mixed and revokes existing tokens
        $pwdRes = $this->withToken($token)->postJson('/api/auth/change-password', [
            'current_password' => 'StrongPassword123!@#',
            'new_password' => 'BrandNewStrongPassword2026!@#',
            'new_password_confirmation' => 'BrandNewStrongPassword2026!@#',
        ]);
        $pwdRes->assertStatus(200);

        // Clear in-memory auth guard state for test runner
        $this->app['auth']->forgetGuards();

        // Old token must now be revoked (401)
        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);

        // Login with new credentials and test logout revocation
        $loginRes2 = $this->postJson('/api/auth/login', [
            'login' => 'staff@meash.et',
            'password' => 'BrandNewStrongPassword2026!@#',
        ]);
        $token2 = $loginRes2->json('token');
        $this->assertNotEmpty($token2);

        $this->withToken($token2)->postJson('/api/auth/logout')->assertStatus(200);

        $this->app['auth']->forgetGuards();
        $this->withToken($token2)->getJson('/api/auth/me')->assertStatus(401);
    }

    /**
     * Test 8: Production API error sanitization (no internal exception details).
     */
    public function test_production_api_sanitizes_errors(): void
    {
        Config::set('app.debug', false);

        // Requesting a non-existent order triggers a 404
        $res = $this->getJson('/api/orders/9999999');
        $res->assertStatus(401); // Needs auth first

        $owner = User::factory()->create(['role' => 'owner']);
        $res2 = $this->actingAs($owner, 'sanctum')->getJson('/api/orders/9999999');
        $res2->assertStatus(404);

        $json = $res2->json();
        // Ensure no stack trace, file path, or exception class is returned
        $this->assertArrayNotHasKey('trace', $json);
        $this->assertArrayNotHasKey('file', $json);
        $this->assertArrayNotHasKey('line', $json);
    }
}
