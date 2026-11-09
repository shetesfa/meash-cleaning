<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\TelegramController;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Service;
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
            '--password' => 'AnotherPassword123!',
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

    public function test_clean_demo_data_command_purges_fictional_seed_records(): void
    {
        // Create demo users
        User::create([
            'name' => 'Cleaner Demo',
            'email' => 'cleaner1@meash.com',
            'phone' => '0911000003',
            'role' => 'cleaner',
            'password' => Hash::make('password'),
        ]);

        // Create legitimate Owner
        $legitOwner = User::create([
            'name' => 'Amha Owner',
            'username' => 'amha',
            'email' => 'amha@meash.com',
            'phone' => '0911999999',
            'role' => 'owner',
            'password' => Hash::make('StrongPassword123!'),
        ]);

        // Create demo customer and demo expense
        $demoCust = Customer::create([
            'customer_code' => 'CUST-DEMO-01',
            'full_name' => 'Abebe Kebede',
            'phone' => '0911223344',
            'address' => 'Bole Medhanialem',
        ]);

        Expense::create([
            'expense_number' => 'EXP-DEMO-01',
            'category' => 'chemicals',
            'amount' => 500,
            'reference_number' => 'REC-CHEM-901',
            'description' => 'Demo chemical expense',
            'date' => now()->toDateString(),
        ]);

        // Test Dry Run: records must NOT be deleted
        Artisan::call('meash:clean-demo-data', ['--dry-run' => true]);
        $this->assertNotNull(User::where('email', 'cleaner1@meash.com')->first());
        $this->assertNotNull(Customer::where('phone', '0911223344')->first());
        $this->assertNotNull(Expense::where('reference_number', 'REC-CHEM-901')->first());

        // Test Force Purge
        Artisan::call('meash:clean-demo-data', ['--force' => true]);

        // Demo records purged
        $this->assertNull(User::where('email', 'cleaner1@meash.com')->first());
        $this->assertNull(Customer::where('phone', '0911223344')->first());
        $this->assertNull(Expense::where('reference_number', 'REC-CHEM-901')->first());

        // Legitimate owner remains intact
        $this->assertNotNull(User::where('username', 'amha')->first());
    }

    public function test_telegram_webhook_secret_strictness_and_unauthorized_rejection(): void
    {
        Config::set('services.telegram.webhook_secret', 'expected-production-secret-999');

        // Request with missing header
        $resNoSecret = $this->postJson('/api/telegram/webhook', [
            'update_id' => 12345,
            'message' => ['chat' => ['id' => 999], 'text' => '/start'],
        ]);
        $resNoSecret->assertStatus(401);

        // Request with incorrect secret header
        $resWrongSecret = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret-token',
        ])->postJson('/api/telegram/webhook', [
            'update_id' => 12346,
            'message' => ['chat' => ['id' => 999], 'text' => '/start'],
        ]);
        $resWrongSecret->assertStatus(401);

        // Request with valid secret header
        $resValid = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'expected-production-secret-999',
        ])->postJson('/api/telegram/webhook', [
            'update_id' => 12347,
            'message' => ['chat' => ['id' => 999], 'text' => '/start'],
        ]);
        $resValid->assertStatus(200);

        // Replay of exact same update_id should return 200 without reprocessing
        $resReplay = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'expected-production-secret-999',
        ])->postJson('/api/telegram/webhook', [
            'update_id' => 12347,
            'message' => ['chat' => ['id' => 999], 'text' => '/start'],
        ]);
        $resReplay->assertStatus(200);
        $this->assertEquals('already_processed', $resReplay->json('status'));
    }

    public function test_miniapp_booking_rejects_replayed_init_data(): void
    {
        $botToken = 'test-bot-token-hardening-77';
        WebsiteSetting::setVal('telegram_bot_token', $botToken);

        $service = Service::create([
            'code' => 'sofa',
            'name_en' => 'Sofa Cleaning',
            'name_am' => 'የሶፋ ጥልቅ እጥበት',
            'base_price' => 350.00,
            'is_active' => true,
        ]);

        $userData = json_encode(['id' => 55443322, 'first_name' => 'MiniAppUser']);
        $params = [
            'auth_date' => (string)time(),
            'query_id' => 'Q_FRESH_123',
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
            'customer_name' => 'Mini App Customer',
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

        // First attempt: should succeed
        $firstRes = $this->postJson('/api/telegram/miniapp/book', $payload);
        $firstRes->assertStatus(201);

        // Second attempt with exact same init_data: must be rejected as replay attack
        $secondRes = $this->postJson('/api/telegram/miniapp/book', $payload);
        $secondRes->assertStatus(422);
        $secondRes->assertJsonValidationErrors(['init_data']);
    }

    public function test_sync_controller_blocks_cleaner_and_sales_from_unauthorized_records(): void
    {
        $cleanerUser = User::factory()->create(['role' => 'cleaner']);
        Sanctum::actingAs($cleanerUser);

        // 1. Cleaner attempting to sync expense creation: must fail
        $expenseSync = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => 'uuid-exp-01',
                    'entity_type' => 'expense',
                    'action' => 'create',
                    'payload' => [
                        'category' => 'chemicals',
                        'amount' => 500,
                        'description' => 'Unauthorized cleaner expense',
                    ],
                ],
            ],
        ]);

        $expenseSync->assertStatus(200);
        $this->assertEquals(0, $expenseSync->json('synced_count'));
        $this->assertEquals(1, $expenseSync->json('error_count'));
        $this->assertStringContainsString('Unauthorized', $expenseSync->json('errors.0.error'));

        // 2. Sales attempting to create expense: must fail
        $salesUser = User::factory()->create(['role' => 'sales']);
        Sanctum::actingAs($salesUser);

        $salesExpenseSync = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => 'uuid-sales-exp-01',
                    'entity_type' => 'expense',
                    'action' => 'create',
                    'payload' => [
                        'category' => 'materials',
                        'amount' => 700,
                        'description' => 'Unauthorized sales expense',
                    ],
                ],
            ],
        ]);

        $salesExpenseSync->assertStatus(200);
        $this->assertEquals(0, $salesExpenseSync->json('synced_count'));
        $this->assertEquals(1, $salesExpenseSync->json('error_count'));
        $this->assertStringContainsString('Unauthorized', $salesExpenseSync->json('errors.0.error'));
    }
}
