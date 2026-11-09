<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\TelegramController;
use App\Models\Appointment;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\CleaningTeam;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Feedback;
use App\Models\Followup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Proforma;
use App\Models\ProformaItem;
use App\Models\SalesVisit;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\TelegramUser;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinalReleaseAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_provision_owner_command_creates_amha_and_supports_username_login(): void
    {
        // 1. Run artisan command to provision owner 'amha'
        $exitCode = Artisan::call('meash:provision-owner', [
            '--username' => 'amha',
            '--email' => 'amha.owner@meash.com',
            '--name' => 'Amha GM',
            '--phone' => '0911000001',
            '--password' => 'StrongOwnerPassword123!',
        ]);

        $this->assertEquals(0, $exitCode);

        // Verify record in database
        $owner = User::where('username', 'amha')->first();
        $this->assertNotNull($owner);
        $this->assertEquals('amha.owner@meash.com', $owner->email);
        $this->assertEquals('owner', $owner->role);
        $this->assertTrue(Hash::check('StrongOwnerPassword123!', $owner->password));

        // 2. Refuse duplicate / overwrite on second run
        $duplicateExit = Artisan::call('meash:provision-owner', [
            '--username' => 'amha',
            '--email' => 'amha.duplicate@meash.com',
            '--password' => 'AnotherStrongPass123!',
        ]);
        $this->assertEquals(0, $duplicateExit);
        $output = Artisan::output();
        $this->assertStringContainsString('Refusing to create duplicate Owner accounts', $output);

        // 3. Test logging in via username 'amha'
        $loginRes = $this->postJson('/api/auth/login', [
            'login' => 'amha',
            'password' => 'StrongOwnerPassword123!',
        ]);

        $loginRes->assertStatus(200);
        $loginRes->assertJsonStructure(['token', 'user' => ['id', 'name', 'username', 'email', 'role']]);
        $this->assertEquals('amha', $loginRes->json('user.username'));
        $this->assertEquals('owner', $loginRes->json('user.role'));
    }

    public function test_telegram_token_is_never_returned_in_admin_settings_get_or_post(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        Sanctum::actingAs($owner);

        WebsiteSetting::setVal('telegram_bot_token', '987654321:SECRET_TOKEN_VALUE_XYZ', 'telegram');
        WebsiteSetting::setVal('company_name', 'MEASH CLEANING SOLUTION', 'general');

        // 1. GET /api/admin/website/settings
        $getRes = $this->getJson('/api/admin/website/settings');
        $getRes->assertStatus(200);
        $getSettings = $getRes->json('settings');

        $this->assertArrayNotHasKey('telegram_bot_token', $getSettings);
        $this->assertArrayNotHasKey('telegram_admin_chat_id', $getSettings);
        $this->assertTrue($getSettings['telegram_bot_configured']);
        $this->assertStringNotContainsString('SECRET_TOKEN_VALUE_XYZ', $getRes->getContent());

        // 2. POST /api/admin/website/settings
        $postRes = $this->postJson('/api/admin/website/settings', [
            'company_name' => 'MEASH CLEANING UPDATED',
            'contact_phone_1' => '0970075509',
        ]);
        $postRes->assertStatus(200);
        $postSettings = $postRes->json('settings');

        $this->assertArrayNotHasKey('telegram_bot_token', $postSettings);
        $this->assertTrue($postSettings['telegram_bot_configured']);
        $this->assertStringNotContainsString('SECRET_TOKEN_VALUE_XYZ', $postRes->getContent());
    }

    public function test_reception_cannot_access_or_change_telegram_bot_credentials(): void
    {
        $reception = User::factory()->create(['role' => 'reception']);
        Sanctum::actingAs($reception);

        // Receptionist cannot set telegram webhook (Owner only)
        $resWebhook = $this->postJson('/api/telegram/set-webhook', []);
        $resWebhook->assertStatus(403);

        // Receptionist cannot send test message (Owner only)
        $resTestMsg = $this->postJson('/api/telegram/send-test', []);
        $resTestMsg->assertStatus(403);
    }

    public function test_role_based_read_and_write_access_across_all_roles(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $finance = User::factory()->create(['role' => 'finance']);
        $cleaner = User::factory()->create(['role' => 'cleaner']);
        $reception = User::factory()->create(['role' => 'reception']);

        $teamA = CleaningTeam::create(['team_name' => 'Team A', 'phone' => '0911000001', 'status' => 'active']);
        $teamB = CleaningTeam::create(['team_name' => 'Team B', 'phone' => '0911000002', 'status' => 'active']);

        TeamMember::create(['cleaning_team_id' => $teamA->id, 'user_id' => $cleaner->id, 'role_in_team' => 'technician']);

        $customer = Customer::create([
            'customer_code' => 'CUST-01',
            'full_name' => 'Sara Yohannes',
            'phone' => '0912445566',
            'address' => 'Bole Medhanialem Behind Edna Mall',
            'subcity' => 'Bole',
        ]);

        $orderA = Order::create([
            'order_number' => 'ORD-TEAM-A',
            'customer_id' => $customer->id,
            'assigned_team_id' => $teamA->id,
            'address' => 'Bole Medhanialem',
            'subcity' => 'Bole',
            'order_status' => 'assigned',
            'appointment_date' => now()->toDateString(),
            'total' => 1500,
        ]);

        $orderB = Order::create([
            'order_number' => 'ORD-TEAM-B',
            'customer_id' => $customer->id,
            'assigned_team_id' => $teamB->id,
            'address' => 'Kazanchis Near UNECA',
            'subcity' => 'Kirkos',
            'order_status' => 'assigned',
            'appointment_date' => now()->toDateString(),
            'total' => 2500,
        ]);

        // 1. Sales role cannot read orders or appointments or teams
        Sanctum::actingAs($sales);
        $this->getJson('/api/orders')->assertStatus(403);
        $this->getJson("/api/orders/{$orderA->id}")->assertStatus(403);
        $this->getJson('/api/appointments')->assertStatus(403);
        $this->getJson('/api/teams')->assertStatus(403);

        // 2. Cleaner role only sees own assigned team orders, appointments, and teams
        Sanctum::actingAs($cleaner);
        $cleanerOrders = $this->getJson('/api/orders')->assertStatus(200);
        $orderNumbers = collect($cleanerOrders->json('data'))->pluck('order_number')->toArray();
        $this->assertContains('ORD-TEAM-A', $orderNumbers);
        $this->assertNotContains('ORD-TEAM-B', $orderNumbers);

        // Cleaner cannot read other team's order details
        $this->getJson("/api/orders/{$orderB->id}")->assertStatus(403);
        $this->getJson("/api/orders/{$orderA->id}")->assertStatus(200);

        // Cleaner teams list only returns own team
        $cleanerTeams = $this->getJson('/api/teams')->assertStatus(200);
        $teamIds = collect($cleanerTeams->json())->pluck('id')->toArray();
        $this->assertContains($teamA->id, $teamIds);
        $this->assertNotContains($teamB->id, $teamIds);

        // 3. Finance role sees orders with masked customer phone and sanitized street address
        Sanctum::actingAs($finance);
        $financeOrders = $this->getJson('/api/orders')->assertStatus(200);
        $financeOrderA = collect($financeOrders->json('data'))->firstWhere('order_number', 'ORD-TEAM-A');
        $this->assertStringContainsString('****', $financeOrderA['customer_phone']);
        $this->assertEquals('Bole', $financeOrderA['address']);

        $financeShow = $this->getJson("/api/orders/{$orderA->id}")->assertStatus(200);
        $this->assertStringContainsString('****', $financeShow->json('customer.phone'));
        $this->assertEquals('Bole', $financeShow->json('address'));

        // Finance cannot access sales crm or appointments
        $this->getJson('/api/appointments')->assertStatus(403);
        $this->getJson('/api/teams')->assertStatus(403);
    }

    public function test_sync_batch_strict_positive_authorization_and_price_tampering_prevention(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $finance = User::factory()->create(['role' => 'finance']);
        $reception = User::factory()->create(['role' => 'reception']);

        $service = Service::create([
            'code' => 'sofa_deep',
            'name_en' => 'Sofa Deep Clean',
            'name_am' => 'የሶፋ ጥልቅ እጥበት',
            'base_price' => 350.00,
            'is_active' => true,
        ]);

        // 1. Sales role cannot create or modify orders via sync
        Sanctum::actingAs($sales);
        $salesOrderSync = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => 'uuid-sales-order-01',
                    'entity_type' => 'order',
                    'action' => 'create',
                    'payload' => [
                        'customer_name' => 'Sales Client',
                        'customer_phone' => '0911000000',
                        'address' => 'Bole',
                        'items' => [['service_id' => $service->id, 'quantity' => 2]],
                    ],
                ],
            ],
        ]);
        $salesOrderSync->assertStatus(200);
        $this->assertEquals(0, $salesOrderSync->json('synced_count'));
        $this->assertEquals(1, $salesOrderSync->json('error_count'));
        $this->assertStringContainsString('Unauthorized', $salesOrderSync->json('errors.0.error'));

        // 2. Finance role cannot create orders or change order statuses via sync
        Sanctum::actingAs($finance);
        $financeOrderSync = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => 'uuid-fin-order-01',
                    'entity_type' => 'order',
                    'action' => 'create',
                    'payload' => [
                        'customer_name' => 'Finance Client',
                        'customer_phone' => '0911000000',
                        'address' => 'CMC',
                        'items' => [['service_id' => $service->id, 'quantity' => 1]],
                    ],
                ],
            ],
        ]);
        $financeOrderSync->assertStatus(200);
        $this->assertEquals(0, $financeOrderSync->json('synced_count'));
        $this->assertEquals(1, $financeOrderSync->json('error_count'));

        // 3. Reception creates order: Server recalculates authoritative price, rejecting client-forged unit_price
        Sanctum::actingAs($reception);
        $receptionSync = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => 'uuid-auth-price-01',
                    'entity_type' => 'order',
                    'action' => 'create',
                    'payload' => [
                        'customer_name' => 'Tamper Test Customer',
                        'customer_phone' => '0911887766',
                        'address' => 'Ayat Square',
                        'subcity' => 'Bole',
                        'appointment_date' => now()->addDays(1)->toDateString(),
                        'appointment_time_slot' => 'morning',
                        // Forged fields: client attempts to force unit_price 1, subtotal 2, total 2, discount 500, status paid
                        'subtotal' => 2,
                        'total' => 2,
                        'discount' => 500,
                        'payment_status' => 'paid',
                        'items' => [
                            [
                                'service_id' => $service->id,
                                'quantity' => 2,
                                'unit_price' => 1.00, // Attacker sends 1 Birr
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $receptionSync->assertStatus(200);
        $this->assertEquals(1, $receptionSync->json('synced_count'));

        $createdOrder = Order::where('customer_id', Customer::where('phone', '0911887766')->first()->id)->first();
        $this->assertNotNull($createdOrder);
        // Verified: Server used authoritative catalog base_price (350.00 * 2 = 700.00)
        $this->assertEquals(700.00, (float)$createdOrder->subtotal);
        $this->assertEquals(700.00, (float)$createdOrder->total);
        $this->assertEquals(0.00, (float)$createdOrder->discount);
        $this->assertEquals('unpaid', $createdOrder->payment_status);

        // 4. Reject arbitrary / unrecognized order status
        $badStatusSync = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => 'uuid-bad-status-01',
                    'entity_type' => 'order',
                    'action' => 'status_change',
                    'payload' => [
                        'order_id' => $createdOrder->id,
                        'order_status' => 'injected_malicious_status',
                    ],
                ],
            ],
        ]);
        $badStatusSync->assertStatus(200);
        $this->assertEquals(0, $badStatusSync->json('synced_count'));
        $this->assertEquals(1, $badStatusSync->json('error_count'));
        $this->assertStringContainsString('Invalid or unrecognized order status', $badStatusSync->json('errors.0.error'));
    }

    public function test_miniapp_replay_and_concurrency_atomic_protection(): void
    {
        $botToken = 'test-bot-token-hardening-77';
        WebsiteSetting::setVal('telegram_bot_token', $botToken);

        $service = Service::create([
            'code' => 'sofa_replay_test',
            'name_en' => 'Sofa Test',
            'name_am' => 'የሶፋ ፍተሻ',
            'base_price' => 350.00,
            'is_active' => true,
        ]);

        $userData = json_encode(['id' => 55443322, 'first_name' => 'MiniAppUser']);
        $currentTime = time();

        // 1. Fresh init_data
        $params = [
            'auth_date' => (string)$currentTime,
            'query_id' => 'Q_FRESH_999',
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
        $validInitData = http_build_query(array_merge($params, ['hash' => $hash]));

        $payload = [
            'customer_name' => 'Atomic Replay Customer',
            'customer_phone' => '0911776655',
            'address' => 'Bole Medhanialem',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'init_data' => $validInitData,
            'items' => [
                [
                    'service_id' => $service->id,
                    'item_name' => 'Sofa',
                    'quantity' => 1,
                    'unit_price' => 350,
                ],
            ],
        ];

        // First attempt: should succeed atomically
        $firstRes = $this->postJson('/api/telegram/miniapp/book', $payload);
        $firstRes->assertStatus(201);

        // Concurrent or second attempt with exact same init_data: must be rejected atomically
        $secondRes = $this->postJson('/api/telegram/miniapp/book', $payload);
        $secondRes->assertStatus(422);
        $secondRes->assertJsonValidationErrors(['init_data']);

        // 2. Expired auth_date (> 15 minutes / 900 seconds) must fail immediately
        $expiredParams = $params;
        $expiredParams['auth_date'] = (string)($currentTime - 1000); // 16.6 minutes ago
        ksort($expiredParams);
        $dArr = [];
        foreach ($expiredParams as $k => $v) {
            $dArr[] = "{$k}={$v}";
        }
        $dString = implode("\n", $dArr);
        $eHash = hash_hmac('sha256', $dString, $secretKey);
        $expiredInitData = http_build_query(array_merge($expiredParams, ['hash' => $eHash]));

        $expiredPayload = $payload;
        $expiredPayload['init_data'] = $expiredInitData;

        Config::set('services.telegram.enforce_initdata', true);
        $expiredRes = $this->withServerVariables(['APP_ENV' => 'production'])
            ->postJson('/api/telegram/miniapp/book', $expiredPayload);
        $expiredRes->assertStatus(422);
    }

    public function test_clean_demo_data_command_purges_all_seeder_sample_entities_and_preserves_amha(): void
    {
        // Create sample records covering all DatabaseSeeder entities
        $demoUser = User::create([
            'name' => 'Cleaner Demo',
            'email' => 'cleaner1@meash.com',
            'phone' => '0911000003',
            'role' => 'cleaner',
            'password' => Hash::make('password'),
        ]);

        $legitOwner = User::create([
            'name' => 'Amha Owner',
            'username' => 'amha',
            'email' => 'amha@meash.com',
            'phone' => '0911999999',
            'role' => 'owner',
            'password' => Hash::make('StrongPassword123!'),
        ]);

        $demoCust = Customer::create([
            'customer_code' => 'CUST-DEMO-01',
            'full_name' => 'Abebe Kebede',
            'phone' => '0911223344',
            'address' => 'Bole Medhanialem',
        ]);

        $demoOrg = Organization::create([
            'org_code' => 'ORG-DEMO-01',
            'name' => 'Ethiopian Skylight Hotel',
            'email' => 'housekeeping@ethiopianskylighthotel.com',
            'phone' => '0116888000',
            'address' => 'Bole',
        ]);

        $demoVisit = SalesVisit::create([
            'visit_code' => 'VISIT-DEMO-01',
            'organization_id' => $demoOrg->id,
            'contact_person' => 'Daniel',
            'phone' => '0911887766',
            'address' => 'Bole',
            'salesperson_user_id' => $demoUser->id,
            'visit_date' => now()->toDateString(),
        ]);

        $demoProforma = Proforma::create([
            'proforma_number' => 'PRF-DEMO-01',
            'organization_id' => $demoOrg->id,
            'sales_visit_id' => $demoVisit->id,
            'prepared_by_user_id' => $demoUser->id,
            'subtotal' => 1000,
            'total' => 1000,
            'validity_date' => now()->addDays(30),
        ]);

        ProformaItem::create([
            'proforma_id' => $demoProforma->id,
            'description' => 'Demo proforma item',
            'quantity' => 10,
            'unit_price' => 100,
            'subtotal' => 1000,
        ]);

        $demoContract = Contract::create([
            'contract_number' => 'CNT-DEMO-01',
            'organization_id' => $demoOrg->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'agreed_value' => 50000,
        ]);

        $demoCamp = Campaign::create([
            'title' => 'የመስከረም አዲስ ዓመት ልዩ ቅናሽ (Enkutatash New Year Promo)',
            'message_text' => 'ልዩ የአዲስ ዓመት ቅናሽ ለደንበኞቻችን',
            'channel' => 'telegram',
            'status' => 'completed',
        ]);

        CampaignRecipient::create([
            'campaign_id' => $demoCamp->id,
            'recipient_id' => '0911223344',
            'status' => 'sent',
        ]);

        $demoTg = TelegramUser::create([
            'telegram_id' => 987654321,
            'username' => 'sarayohannes_eth',
            'first_name' => 'Sara',
            'phone_number' => '0912445566',
        ]);

        // 1. Dry Run Mode
        Artisan::call('meash:clean-demo-data', ['--dry-run' => true]);
        $output = Artisan::output();
        $this->assertStringContainsString('DRY RUN COMPLETE', $output);

        // Verify no records deleted in dry run
        $this->assertNotNull(User::where('email', 'cleaner1@meash.com')->first());
        $this->assertNotNull(Organization::where('email', 'housekeeping@ethiopianskylighthotel.com')->first());
        $this->assertNotNull(Contract::where('contract_number', 'CNT-DEMO-01')->first());

        // 2. Real Purge Execution
        $exitCode = Artisan::call('meash:clean-demo-data', [
            '--force' => true,
            '--backup-confirmed' => true,
        ]);
        $this->assertEquals(0, $exitCode);

        // Verified: All seeded sample entities are permanently removed
        $this->assertNull(User::where('email', 'cleaner1@meash.com')->first());
        $this->assertNull(Customer::where('phone', '0911223344')->first());
        $this->assertNull(Organization::where('email', 'housekeeping@ethiopianskylighthotel.com')->first());
        $this->assertNull(SalesVisit::where('visit_code', 'VISIT-DEMO-01')->first());
        $this->assertNull(Proforma::where('proforma_number', 'PRF-DEMO-01')->first());
        $this->assertNull(ProformaItem::where('description', 'Demo proforma item')->first());
        $this->assertNull(Contract::where('contract_number', 'CNT-DEMO-01')->first());
        $this->assertNull(Campaign::where('title', 'like', '%Enkutatash%')->first());
        $this->assertNull(TelegramUser::where('telegram_id', 987654321)->first());

        // Verified: Genuine Owner amha is strictly preserved
        $this->assertNotNull(User::where('username', 'amha')->first());
    }
}
