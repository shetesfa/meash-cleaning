<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\CleaningTeam;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Proforma;
use App\Models\SalesVisit;
use App\Models\Service;
use App\Models\SyncConflict;
use App\Models\SyncIdempotencyRecord;
use App\Models\TeamMember;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinalBlockerRepairTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Service::create([
            'code' => 'sofa',
            'name_en' => 'Sofa & Couch Deep Cleaning',
            'name_am' => 'የሶፋ ጥልቅ እጥበት',
            'base_price' => 350.00,
            'unit' => 'seat',
            'is_active' => true,
        ]);
    }

    public function test_offline_sync_concurrency_lock_never_returns_already_processed_and_retains_queue(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $customer = Customer::create([
            'customer_code' => 'CUST-001',
            'full_name' => 'Almaz Kebede',
            'phone' => '0911000111',
            'address' => 'Bole Medhanialem',
        ]);

        $uuid = 'concurrency-test-uuid-' . uniqid();

        // 1. Manually simulate another request holding the lock in_progress
        SyncIdempotencyRecord::create([
            'client_uuid' => $uuid,
            'user_id' => $owner->id,
            'entity_type' => 'order',
            'action' => 'create',
            'status' => 'in_progress',
            'lock_token' => 'active-lock-token-123',
        ]);

        $payload = [
            'items' => [
                [
                    'client_uuid' => $uuid,
                    'entity_type' => 'order',
                    'action' => 'create',
                    'client_version' => 1,
                    'payload' => [
                        'customer_id' => $customer->id,
                        'appointment_date' => now()->addDay()->toDateString(),
                        'items' => [
                            ['service_code' => 'sofa', 'quantity' => 2, 'unit_price' => 350],
                        ],
                    ],
                ],
            ],
        ];

        Sanctum::actingAs($owner);
        $res = $this->postJson('/api/sync/batch', $payload)->assertStatus(200);

        // MUST NOT be counted in synced_count! Must be returned in in_progress
        $this->assertEquals(0, $res->json('synced_count'));
        $this->assertEquals(1, $res->json('in_progress_count'));
        $this->assertEquals('in_progress', $res->json('in_progress.0.status'));
        $this->assertEmpty($res->json('synced'));

        // Order must NOT have been created yet
        $this->assertEquals(0, Order::count());
    }

    public function test_failed_first_sync_can_retry_successfully_and_cannot_process_twice(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $customer = Customer::create([
            'customer_code' => 'CUST-002',
            'full_name' => 'Dawit Tsige',
            'phone' => '0911000222',
            'address' => 'Kazanchis',
        ]);

        $uuid = 'retry-test-uuid-' . uniqid();

        // 1. Attempt with invalid service code (causes failure)
        $invalidPayload = [
            'items' => [
                [
                    'client_uuid' => $uuid,
                    'entity_type' => 'order',
                    'action' => 'create',
                    'client_version' => 1,
                    'payload' => [
                        'customer_id' => $customer->id,
                        'appointment_date' => now()->addDay()->toDateString(),
                        'items' => [
                            ['service_code' => 'non_existent_service_xyz', 'quantity' => 1, 'unit_price' => 999],
                        ],
                    ],
                ],
            ],
        ];

        Sanctum::actingAs($owner);
        $res1 = $this->postJson('/api/sync/batch', $invalidPayload)->assertStatus(200);
        $this->assertEquals(0, $res1->json('synced_count'));
        $this->assertEquals(1, $res1->json('error_count'));

        // Idempotency record is marked failed, allowing retry
        $record = SyncIdempotencyRecord::where('client_uuid', $uuid)->first();
        $this->assertNotNull($record);
        $this->assertEquals('failed', $record->status);

        // 2. Retry with valid service code: MUST SUCCEED
        $validPayload = [
            'items' => [
                [
                    'client_uuid' => $uuid,
                    'entity_type' => 'order',
                    'action' => 'create',
                    'client_version' => 1,
                    'payload' => [
                        'customer_id' => $customer->id,
                        'appointment_date' => now()->addDay()->toDateString(),
                        'items' => [
                            ['service_code' => 'sofa', 'quantity' => 3, 'unit_price' => 350],
                        ],
                    ],
                ],
            ],
        ];

        $res2 = $this->postJson('/api/sync/batch', $validPayload)->assertStatus(200);
        $this->assertEquals(1, $res2->json('synced_count'));
        $this->assertEquals('synced', $res2->json('synced.0.status'));
        $this->assertEquals(1, Order::count());

        $record->refresh();
        $this->assertEquals('completed', $record->status);

        // 3. Repeat exact same sync payload: MUST RETURN already_processed and NOT create duplicate
        $res3 = $this->postJson('/api/sync/batch', $validPayload)->assertStatus(200);
        $this->assertEquals(1, $res3->json('synced_count'));
        $this->assertEquals('already_processed', $res3->json('synced.0.status'));
        $this->assertEquals(1, Order::count()); // Still only 1 order!
    }

    public function test_owner_reception_sales_finance_cleaner_role_sync_matrix(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $reception = User::factory()->create(['role' => 'reception']);
        $cleaner = User::factory()->create(['role' => 'cleaner']);
        $sales = User::factory()->create(['role' => 'sales']);
        $finance = User::factory()->create(['role' => 'finance']);

        $customer = Customer::create([
            'customer_code' => 'CUST-003',
            'full_name' => 'Helen Berhe',
            'phone' => '0911000333',
            'address' => 'Bole',
        ]);

        $teamA = CleaningTeam::create(['team_name' => 'Team A Alpha']);
        TeamMember::create(['cleaning_team_id' => $teamA->id, 'user_id' => $cleaner->id, 'role_in_team' => 'technician']);

        $org = Organization::create([
            'org_code' => 'ORG-001',
            'name' => 'Standard Corporate Corp',
            'email' => 'corp@standard.com',
            'phone' => '0115000000',
            'address' => 'Bole',
        ]);

        // A. Owner can synchronize operational order status update
        $order = Order::create([
            'order_number' => 'ORD-OWNER-TEST',
            'customer_id' => $customer->id,
            'assigned_team_id' => $teamA->id,
            'address' => 'Bole',
            'order_status' => 'assigned',
            'appointment_date' => now()->toDateString(),
            'total' => 700,
            'version' => 1,
        ]);

        Sanctum::actingAs($owner);
        $ownerSync = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => 'owner-sync-uuid-' . uniqid(),
                    'entity_type' => 'order',
                    'action' => 'status_change',
                    'client_version' => 1,
                    'payload' => ['order_id' => $order->id, 'order_status' => 'confirmed'],
                ],
            ],
        ])->assertStatus(200);
        $this->assertEquals(1, $ownerSync->json('synced_count'));
        $this->assertEquals('confirmed', $order->fresh()->order_status);

        // B. Reception can synchronize new order creation
        Sanctum::actingAs($reception);
        $recSync = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => 'rec-sync-uuid-' . uniqid(),
                    'entity_type' => 'order',
                    'action' => 'create',
                    'client_version' => 1,
                    'payload' => [
                        'customer_id' => $customer->id,
                        'appointment_date' => now()->toDateString(),
                        'items' => [['service_code' => 'sofa', 'quantity' => 1, 'unit_price' => 350]],
                    ],
                ],
            ],
        ])->assertStatus(200);
        $this->assertEquals(1, $recSync->json('synced_count'));

        // C. Sales CANNOT create or update orders, but CAN sync sales_visit
        Sanctum::actingAs($sales);
        $salesOrderSync = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => 'sales-order-fail-' . uniqid(),
                    'entity_type' => 'order',
                    'action' => 'create',
                    'client_version' => 1,
                    'payload' => ['customer_id' => $customer->id, 'appointment_date' => now()->toDateString(), 'items' => [['service_code' => 'sofa', 'quantity' => 1]]],
                ],
            ],
        ])->assertStatus(200);
        $this->assertEquals(0, $salesOrderSync->json('synced_count'));
        $this->assertEquals(1, $salesOrderSync->json('error_count'));

        $salesVisitSync = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => 'sales-visit-ok-' . uniqid(),
                    'entity_type' => 'sales_visit',
                    'action' => 'create',
                    'client_version' => 1,
                    'payload' => [
                        'organization_id' => $org->id,
                        'contact_person' => 'Ato Daniel',
                        'phone' => '0911888888',
                        'address' => 'Bole',
                    ],
                ],
            ],
        ])->assertStatus(200);
        $this->assertEquals(1, $salesVisitSync->json('synced_count'));

        // D. Finance CANNOT create or update orders, but CAN sync expense
        Sanctum::actingAs($finance);
        $finOrderSync = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => 'fin-order-fail-' . uniqid(),
                    'entity_type' => 'order',
                    'action' => 'status_change',
                    'client_version' => 2,
                    'payload' => ['order_id' => $order->id, 'order_status' => 'completed'],
                ],
            ],
        ])->assertStatus(200);
        $this->assertEquals(0, $finOrderSync->json('synced_count'));
        $this->assertEquals(1, $finOrderSync->json('error_count'));

        $finExpenseSync = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => 'fin-expense-ok-' . uniqid(),
                    'entity_type' => 'expense',
                    'action' => 'create',
                    'client_version' => 1,
                    'payload' => ['category' => 'supplies', 'amount' => 450, 'description' => 'Shampoo bottles'],
                ],
            ],
        ])->assertStatus(200);
        $this->assertEquals(1, $finExpenseSync->json('synced_count'));

        // E. Cleaner can only update assigned team order, cannot update other team
        $teamB = CleaningTeam::create(['team_name' => 'Team B Bravo']);
        $orderTeamB = Order::create([
            'order_number' => 'ORD-TEAM-B-TEST',
            'customer_id' => $customer->id,
            'assigned_team_id' => $teamB->id,
            'address' => 'Kazanchis',
            'order_status' => 'assigned',
            'appointment_date' => now()->toDateString(),
            'total' => 700,
            'version' => 1,
        ]);

        Sanctum::actingAs($cleaner);
        $cleanerOtherTeam = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => 'cleaner-fail-' . uniqid(),
                    'entity_type' => 'order',
                    'action' => 'status_change',
                    'client_version' => 1,
                    'payload' => ['order_id' => $orderTeamB->id, 'order_status' => 'cleaning'],
                ],
            ],
        ])->assertStatus(200);
        $this->assertEquals(0, $cleanerOtherTeam->json('synced_count'));
        $this->assertEquals(1, $cleanerOtherTeam->json('error_count'));

        $cleanerOwnTeam = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => 'cleaner-ok-' . uniqid(),
                    'entity_type' => 'order',
                    'action' => 'status_change',
                    'client_version' => 2,
                    'payload' => ['order_id' => $order->id, 'order_status' => 'cleaning'],
                ],
            ],
        ])->assertStatus(200);
        $this->assertEquals(1, $cleanerOwnTeam->json('synced_count'));
        $this->assertEquals('cleaning', $order->fresh()->order_status);
    }

    public function test_clean_demo_data_preserves_legitimate_organizations_named_cbe_hyatt_skylight(): void
    {
        // 1. Create a legitimate business organization named Commercial Bank of Ethiopia HQ
        $legitStaff = User::factory()->create(['name' => 'Legit Salesperson', 'role' => 'sales', 'email' => 'legit.sales@meash.com']);
        $legitCbe = Organization::create([
            'org_code' => 'ORG-LEGIT-CBE',
            'name' => 'Commercial Bank of Ethiopia HQ',
            'industry' => 'bank',
            'email' => 'facilities@cbe.com.et',
            'phone' => '0115515004',
            'address' => 'Churchill Avenue, CBE Tower (53 floors)',
            'notes' => 'Official contracted corporate client registered by active sales team.',
        ]);

        SalesVisit::create([
            'visit_code' => 'VISIT-LEGIT-01',
            'organization_id' => $legitCbe->id,
            'contact_person' => 'Ato Bekele',
            'phone' => '0911776655',
            'address' => 'Churchill Road',
            'salesperson_user_id' => $legitStaff->id,
            'visit_date' => now()->toDateString(),
        ]);

        // 2. Create legitimate Skylight and Hyatt organizations
        $legitSkylight = Organization::create([
            'org_code' => 'ORG-LEGIT-SKY',
            'name' => 'Ethiopian Skylight Hotel',
            'industry' => 'hotel',
            'email' => 'housekeeping@ethiopianskylighthotel.com',
            'phone' => '0116888000',
            'address' => 'Airport Road, Bole',
            'notes' => 'Real commercial contract for conference hall maintenance.',
        ]);

        // 3. Create a real owner 'amha'
        $amha = User::create([
            'name' => 'Amha Owner',
            'username' => 'amha',
            'email' => 'amha@meash.com',
            'phone' => '0911999999',
            'role' => 'owner',
            'password' => Hash::make('StrongPassword123!'),
        ]);

        // 4. Run cleanup command with backup confirmed and force
        $exitCode = Artisan::call('meash:clean-demo-data', [
            '--backup-confirmed' => true,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        // Assert legitimate organizations and owner amha MUST BE PRESERVED
        $this->assertDatabaseHas('organizations', ['id' => $legitCbe->id, 'name' => 'Commercial Bank of Ethiopia HQ']);
        $this->assertDatabaseHas('organizations', ['id' => $legitSkylight->id, 'name' => 'Ethiopian Skylight Hotel']);
        $this->assertDatabaseHas('users', ['id' => $amha->id, 'username' => 'amha']);
    }

    public function test_clean_demo_data_works_when_demo_user_list_is_empty_and_no_empty_nested_query(): void
    {
        // Setup campaign and telegram sample without any demo users in the DB
        $demoCamp = Campaign::create([
            'title' => 'የመስከረም አዲስ ዓመት ልዩ ቅናሽ (Enkutatash New Year Promo)',
            'message_text' => 'Demo text',
            'channel' => 'telegram',
            'status' => 'completed',
        ]);

        $exitCode = Artisan::call('meash:clean-demo-data', [
            '--backup-confirmed' => true,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertDatabaseMissing('campaigns', ['id' => $demoCamp->id]);
    }

    public function test_finance_role_cannot_view_staff_and_leader_phone_numbers_in_order_show(): void
    {
        $finance = User::factory()->create(['role' => 'finance']);
        $reception = User::factory()->create(['role' => 'reception']);
        $leader = User::factory()->create(['name' => 'Leader Abebe', 'phone' => '0911888999', 'role' => 'cleaner']);
        $member = User::factory()->create(['name' => 'Member Chala', 'phone' => '0922777888', 'role' => 'cleaner']);

        $team = CleaningTeam::create(['team_name' => 'Alpha Scrub', 'team_leader_id' => $leader->id]);
        TeamMember::create(['cleaning_team_id' => $team->id, 'user_id' => $member->id, 'role_in_team' => 'technician']);

        $customer = Customer::create([
            'customer_code' => 'CUST-004',
            'full_name' => 'Tigist Alemu',
            'phone' => '0911554433',
            'address' => 'Bole Medhanialem Behind Edna Mall',
            'subcity' => 'Bole',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-PRIVACY-TEST',
            'customer_id' => $customer->id,
            'assigned_team_id' => $team->id,
            'address' => 'Bole Medhanialem Behind Edna Mall',
            'subcity' => 'Bole',
            'order_status' => 'assigned',
            'appointment_date' => now()->toDateString(),
            'total' => 1200,
        ]);

        // 1. Finance inspection: staff phones must be hidden / unset, customer phone masked
        Sanctum::actingAs($finance);
        $finRes = $this->getJson("/api/orders/{$order->id}")->assertStatus(200);

        $this->assertStringContainsString('****', $finRes->json('customer.phone'));
        $this->assertEquals('Bole', $finRes->json('customer.address'));

        // Leader and Member private phone numbers must NOT exist in finance response
        $this->assertArrayNotHasKey('phone', $finRes->json('assigned_team.leader') ?? []);
        $this->assertArrayNotHasKey('phone', $finRes->json('assigned_team.members.0.user') ?? []);

        // 2. Reception inspection: operational staff phones are visible for dispatch coordination
        Sanctum::actingAs($reception);
        $recRes = $this->getJson("/api/orders/{$order->id}")->assertStatus(200);
        $this->assertEquals('0911888999', $recRes->json('assigned_team.leader.phone'));
        $this->assertEquals('0922777888', $recRes->json('assigned_team.members.0.user.phone'));
    }

    public function test_notification_anti_idor_and_per_user_read_state(): void
    {
        $userA = User::factory()->create(['name' => 'User A']);
        $userB = User::factory()->create(['name' => 'User B']);

        // 1. Personal Notification for User A
        $notifA = Notification::create([
            'user_id' => $userA->id,
            'title' => 'Personal alert for User A',
            'body' => 'Your assigned shift starts soon.',
            'is_read' => false,
        ]);

        // User B attempts to mark User A's notification as read (Cross-user IDOR attempt)
        Sanctum::actingAs($userB);
        $idorRes = $this->postJson("/api/notifications/{$notifA->id}/read");
        $idorRes->assertStatus(403);
        $this->assertFalse($notifA->fresh()->is_read);

        // User A marks own notification as read: MUST SUCCEED
        Sanctum::actingAs($userA);
        $ownRes = $this->postJson("/api/notifications/{$notifA->id}/read");
        $ownRes->assertStatus(200);
        $this->assertTrue($notifA->fresh()->is_read);

        // 2. Global Broadcast Notification (user_id is null)
        $broadcastNotif = Notification::create([
            'user_id' => null,
            'title' => 'Company-wide holiday announcement',
            'body' => 'Office will be closed on Meskel holiday.',
            'is_read' => false,
        ]);

        // User A marks broadcast as read
        Sanctum::actingAs($userA);
        $this->postJson("/api/notifications/{$broadcastNotif->id}/read")->assertStatus(200);

        // User A's feed shows is_read = true and unread_count = 0
        $feedA = $this->getJson('/api/notifications')->assertStatus(200);
        $bItemA = collect($feedA->json('notifications.data'))->firstWhere('id', $broadcastNotif->id);
        $this->assertTrue($bItemA['is_read']);
        $this->assertEquals(0, $feedA->json('unread_count'));

        // User B's feed MUST STILL show is_read = false and unread_count = 1 (NOT globally marked read!)
        Sanctum::actingAs($userB);
        $feedB = $this->getJson('/api/notifications')->assertStatus(200);
        $bItemB = collect($feedB->json('notifications.data'))->firstWhere('id', $broadcastNotif->id);
        $this->assertFalse($bItemB['is_read']);
        $this->assertEquals(1, $feedB->json('unread_count'));
    }

    public function test_provision_owner_command_enforces_amha_and_strong_password(): void
    {
        // 1. Weak password (missing special character) must be rejected
        $weakExit = Artisan::call('meash:provision-owner', [
            '--username' => 'amha',
            '--email' => 'owner@meash.com',
            '--password' => 'WeakPassword123', // 15 chars, has Upper, lower, number, but NO special char
        ]);
        $this->assertEquals(1, $weakExit);

        // 2. Strong password with special characters: MUST SUCCEED
        $successExit = Artisan::call('meash:provision-owner', [
            '--username' => 'amha',
            '--email' => 'owner@meash.com',
            '--password' => 'StrongP@ssw0rd!#2026',
        ]);
        $this->assertEquals(0, $successExit);

        // Verify owner amha was created
        $owner = User::where('username', 'amha')->first();
        $this->assertNotNull($owner);
        $this->assertEquals('owner', $owner->role);

        // 3. Test login with username 'amha'
        $loginRes = $this->postJson('/api/auth/login', [
            'username' => 'amha',
            'password' => 'StrongP@ssw0rd!#2026',
        ])->assertStatus(200);

        $this->assertNotNull($loginRes->json('token'));
        $this->assertEquals('amha', $loginRes->json('user.username'));

        // 4. Repeat command: MUST BE IDEMPOTENT (returns 0 without error or duplicate)
        $idempotentExit = Artisan::call('meash:provision-owner', [
            '--username' => 'amha',
            '--email' => 'owner@meash.com',
            '--password' => 'StrongP@ssw0rd!#2026',
        ]);
        $this->assertEquals(0, $idempotentExit);
        $this->assertEquals(1, User::where('username', 'amha')->count());
    }
}
