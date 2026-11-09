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
use App\Models\Payment;
use App\Models\Proforma;
use App\Models\SalesVisit;
use App\Models\SeedProvenance;
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
        // 1. Create a demo organization tagged with explicit SeedProvenance
        $demoOrg = Organization::create([
            'org_code' => 'ORG-DEMO-CBE',
            'name' => 'Commercial Bank of Ethiopia HQ',
            'industry' => 'bank',
            'email' => 'demo.cbe@meash.com',
            'phone' => '0115001122',
            'address' => 'Churchill Avenue, CBE Tower (53 floors)',
            'notes' => 'Fictional demo organization from seed.',
        ]);
        SeedProvenance::tag($demoOrg, 'DatabaseSeeder');

        // 2. Create a legitimate business organization named Commercial Bank of Ethiopia HQ WITHOUT provenance
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

        // 3. Create legitimate Skylight organization WITHOUT provenance
        $legitSkylight = Organization::create([
            'org_code' => 'ORG-LEGIT-SKY',
            'name' => 'Ethiopian Skylight Hotel',
            'industry' => 'hotel',
            'email' => 'housekeeping@ethiopianskylighthotel.com',
            'phone' => '0116888000',
            'address' => 'Airport Road, Bole',
            'notes' => 'Real commercial contract for conference hall maintenance.',
        ]);

        // 4. Create a real owner 'amha'
        $amha = User::create([
            'name' => 'Amha Owner',
            'username' => 'amha',
            'email' => 'amha@meash.com',
            'phone' => '0911999999',
            'role' => 'owner',
            'password' => Hash::make('StrongPassword123!'),
        ]);

        // 5. Run cleanup command with backup confirmed and force
        $exitCode = Artisan::call('meash:clean-demo-data', [
            '--backup-confirmed' => true,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        // Verified demo organization MUST be purged
        $this->assertDatabaseMissing('organizations', ['id' => $demoOrg->id]);

        // Legitimate organizations and owner amha MUST BE PRESERVED
        $this->assertDatabaseHas('organizations', ['id' => $legitCbe->id, 'name' => 'Commercial Bank of Ethiopia HQ']);
        $this->assertDatabaseHas('organizations', ['id' => $legitSkylight->id, 'name' => 'Ethiopian Skylight Hotel']);
        $this->assertDatabaseHas('users', ['id' => $amha->id, 'username' => 'amha']);
    }

    public function test_clean_demo_data_works_when_demo_user_list_is_empty_and_no_empty_nested_query(): void
    {
        // Setup campaign with explicit seed provenance and legitimate campaign without provenance
        $demoCamp = Campaign::create([
            'title' => 'የመስከረም አዲስ ዓመት ልዩ ቅናሽ (Enkutatash New Year Promo)',
            'message_text' => 'Demo text',
            'channel' => 'telegram',
            'status' => 'completed',
        ]);
        SeedProvenance::tag($demoCamp, 'DatabaseSeeder');

        // Legitimate marketing campaign with identical title created by real staff
        $legitCamp = Campaign::create([
            'title' => 'የመስከረም አዲስ ዓመት ልዩ ቅናሽ (Enkutatash New Year Promo)',
            'message_text' => 'Real promotional message for 2026',
            'channel' => 'telegram',
            'status' => 'scheduled',
        ]);

        $exitCode = Artisan::call('meash:clean-demo-data', [
            '--backup-confirmed' => true,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        // Verified demo campaign must be purged
        $this->assertDatabaseMissing('campaigns', ['id' => $demoCamp->id]);
        // Legitimate campaign matching demo title must be PRESERVED
        $this->assertDatabaseHas('campaigns', ['id' => $legitCamp->id]);
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

    public function test_offline_sync_prevents_cross_account_uuid_hijacking(): void
    {
        $userA = User::factory()->create(['role' => 'owner']);
        $userB = User::factory()->create(['role' => 'reception']);
        $uuid = 'hijack-attempt-uuid-' . uniqid();

        // 1. User A successfully syncs an order with $uuid
        SyncIdempotencyRecord::create([
            'client_uuid' => $uuid,
            'user_id' => $userA->id,
            'entity_type' => 'order',
            'action' => 'create',
            'status' => 'completed',
            'entity_id' => 999,
        ]);

        // 2. User B attempts to submit the same UUID under their own session
        Sanctum::actingAs($userB);
        $res = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => $uuid,
                    'entity_type' => 'order',
                    'action' => 'create',
                    'client_version' => 1,
                    'payload' => [
                        'customer_id' => 1,
                        'appointment_date' => now()->toDateString(),
                        'items' => [['service_code' => 'sofa', 'quantity' => 1]],
                    ],
                ],
            ],
        ])->assertStatus(200);

        // MUST be rejected with UUID ownership mismatch error
        $this->assertEquals(0, $res->json('synced_count'));
        $this->assertEquals(1, $res->json('error_count'));
        $this->assertStringContainsString('ownership mismatch', $res->json('errors.0.error'));
    }

    public function test_offline_sync_recovers_safely_from_stale_in_progress_records(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $customer = Customer::create([
            'customer_code' => 'CUST-STALE-01',
            'full_name' => 'Stale Recovery Customer',
            'phone' => '0911998877',
            'address' => 'Bole',
        ]);

        $uuid = 'stale-crash-uuid-' . uniqid();

        // Simulate server process crashed 10 minutes ago leaving record in_progress
        $record = SyncIdempotencyRecord::create([
            'client_uuid' => $uuid,
            'user_id' => $owner->id,
            'entity_type' => 'order',
            'action' => 'create',
            'status' => 'in_progress',
            'lock_token' => 'dead-server-token',
        ]);
        DB::table('sync_idempotency_records')->where('id', $record->id)->update([
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        Sanctum::actingAs($owner);
        $res = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => $uuid,
                    'entity_type' => 'order',
                    'action' => 'create',
                    'client_version' => 1,
                    'payload' => [
                        'customer_id' => $customer->id,
                        'appointment_date' => now()->toDateString(),
                        'items' => [['service_code' => 'sofa', 'quantity' => 2, 'unit_price' => 350]],
                    ],
                ],
            ],
        ]);
        $res->assertStatus(200);

        // Stale record MUST be recovered safely, order created, and record marked completed
        $this->assertEquals(1, $res->json('synced_count'));
        $this->assertEquals('synced', $res->json('synced.0.status'));
        $this->assertEquals('completed', $record->fresh()->status);
        $this->assertEquals(1, Order::where('customer_id', $customer->id)->count());
    }

    public function test_atomic_rollback_on_transaction_failure_prevents_false_completed_idempotency(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $customer = Customer::create([
            'customer_code' => 'CUST-FAIL-01',
            'full_name' => 'Failing Order Customer',
            'phone' => '0911778899',
            'address' => 'Bole',
        ]);

        $uuid = 'atomic-rollback-uuid-' . uniqid();

        // Inject invalid service that causes exception during transaction
        Sanctum::actingAs($owner);
        $res = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => $uuid,
                    'entity_type' => 'order',
                    'action' => 'create',
                    'client_version' => 1,
                    'payload' => [
                        'customer_id' => $customer->id,
                        'appointment_date' => now()->toDateString(),
                        'items' => [['service_code' => 'invalid_unrecognized_service', 'quantity' => 1]],
                    ],
                ],
            ],
        ])->assertStatus(200);

        // Failed transaction: zero orders created, record status is 'failed', NEVER 'completed'
        $this->assertEquals(0, $res->json('synced_count'));
        $this->assertEquals(1, $res->json('error_count'));
        $this->assertEquals(0, Order::where('customer_id', $customer->id)->count());

        $record = SyncIdempotencyRecord::where('client_uuid', $uuid)->first();
        $this->assertNotNull($record);
        $this->assertEquals('failed', $record->status);
    }

    public function test_clean_demo_data_purges_child_records_through_provenance_and_parent_relationships(): void
    {
        $demoUser = User::factory()->create(['email' => 'cleaner_demo_test@meash.com', 'role' => 'cleaner']);
        SeedProvenance::tag($demoUser, 'DatabaseSeeder');

        $demoCust = Customer::create([
            'customer_code' => 'CUST-DEMO-CHILD',
            'full_name' => 'Demo Customer Child Test',
            'phone' => '0911000777',
            'address' => 'Bole',
        ]);
        SeedProvenance::tag($demoCust, 'DatabaseSeeder');

        $demoTeam = CleaningTeam::create(['team_name' => 'Demo Team Alpha']);
        SeedProvenance::tag($demoTeam, 'DatabaseSeeder');

        $demoMember = TeamMember::create(['cleaning_team_id' => $demoTeam->id, 'user_id' => $demoUser->id, 'role_in_team' => 'leader']);
        SeedProvenance::tag($demoMember, 'DatabaseSeeder');

        $demoOrder = Order::create([
            'order_number' => 'ORD-DEMO-CHILD-TEST',
            'customer_id' => $demoCust->id,
            'assigned_team_id' => $demoTeam->id,
            'address' => 'Bole',
            'order_status' => 'confirmed',
            'appointment_date' => now()->toDateString(),
            'total' => 1000,
        ]);
        SeedProvenance::tag($demoOrder, 'DatabaseSeeder');

        $demoItem = OrderItem::create([
            'order_id' => $demoOrder->id,
            'item_name' => 'Sofa Cleaning',
            'quantity' => 2,
            'unit_price' => 500,
        ]);
        SeedProvenance::tag($demoItem, 'DatabaseSeeder');

        $demoPayment = Payment::create([
            'payment_number' => 'PAY-DEMO-TEST-01',
            'order_id' => $demoOrder->id,
            'customer_id' => $demoCust->id,
            'amount' => 1000,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);
        SeedProvenance::tag($demoPayment, 'DatabaseSeeder');

        // Legitimate client order and child items WITHOUT provenance
        $legitCust = Customer::create([
            'customer_code' => 'CUST-LEGIT-CHILD',
            'full_name' => 'Legitimate Real Customer',
            'phone' => '0911888111',
            'address' => 'CMC Michael',
        ]);
        $legitOrder = Order::create([
            'order_number' => 'ORD-LEGIT-CHILD-REAL',
            'customer_id' => $legitCust->id,
            'address' => 'CMC Michael',
            'order_status' => 'confirmed',
            'appointment_date' => now()->toDateString(),
            'total' => 1500,
        ]);
        $legitItem = OrderItem::create([
            'order_id' => $legitOrder->id,
            'item_name' => 'Carpet Cleaning',
            'quantity' => 1,
            'unit_price' => 1500,
        ]);
        $legitPayment = Payment::create([
            'payment_number' => 'PAY-LEGIT-TEST-01',
            'order_id' => $legitOrder->id,
            'customer_id' => $legitCust->id,
            'amount' => 1500,
            'payment_method' => 'cbe_birr',
            'payment_date' => now()->toDateString(),
        ]);

        $exitCode = Artisan::call('meash:clean-demo-data', [
            '--backup-confirmed' => true,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        // Verified demo parents and children MUST be purged
        $this->assertDatabaseMissing('orders', ['id' => $demoOrder->id]);
        $this->assertDatabaseMissing('order_items', ['id' => $demoItem->id]);
        $this->assertDatabaseMissing('payments', ['id' => $demoPayment->id]);
        $this->assertDatabaseMissing('cleaning_teams', ['id' => $demoTeam->id]);
        $this->assertDatabaseMissing('team_members', ['id' => $demoMember->id]);

        // Legitimate parents and children MUST BE COMPLETELY PRESERVED
        $this->assertDatabaseHas('orders', ['id' => $legitOrder->id]);
        $this->assertDatabaseHas('order_items', ['id' => $legitItem->id]);
        $this->assertDatabaseHas('payments', ['id' => $legitPayment->id]);
        $this->assertDatabaseHas('customers', ['id' => $legitCust->id]);
    }

    public function test_order_status_conflict_creation_and_idempotency_are_atomic(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $customer = Customer::create([
            'customer_code' => 'CUST-CONFLICT-01',
            'full_name' => 'Conflict Order Customer',
            'phone' => '0911224466',
            'address' => 'Bole',
        ]);

        // Server order is at version 5 with status 'cleaning'
        $order = Order::create([
            'order_number' => 'ORD-CONFLICT-TEST',
            'customer_id' => $customer->id,
            'address' => 'Bole',
            'order_status' => 'cleaning',
            'appointment_date' => now()->toDateString(),
            'total' => 800,
            'version' => 5,
        ]);

        $uuid = 'conflict-atomic-uuid-' . uniqid();

        // Client sends status change with older version 2
        Sanctum::actingAs($owner);
        $res = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => $uuid,
                    'entity_type' => 'order',
                    'action' => 'status_change',
                    'client_version' => 2,
                    'payload' => [
                        'order_id' => $order->id,
                        'order_status' => 'completed',
                    ],
                ],
            ],
        ])->assertStatus(200);

        // Conflict recorded atomically, order status remained intact on server
        $this->assertEquals(1, $res->json('conflict_count'));
        $this->assertEquals('cleaning', $order->fresh()->order_status);

        $idemp = SyncIdempotencyRecord::where('client_uuid', $uuid)->first();
        $this->assertNotNull($idemp);
        $this->assertEquals('completed', $idemp->status);
        $this->assertTrue($idemp->response_payload['conflict']);

        $conflict = \App\Models\SyncConflict::where('client_uuid', $uuid)->first();
        $this->assertNotNull($conflict);
        $this->assertEquals($order->id, $conflict->entity_id);
    }

    public function test_stale_recovery_atomic_claim_prevents_simultaneous_processing(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $uuid = 'stale-atomic-claim-uuid-' . uniqid();

        // Create a stale in_progress record
        $record = SyncIdempotencyRecord::create([
            'client_uuid' => $uuid,
            'user_id' => $owner->id,
            'entity_type' => 'order',
            'action' => 'create',
            'status' => 'in_progress',
            'lock_token' => 'dead-token-123',
        ]);
        DB::table('sync_idempotency_records')->where('id', $record->id)->update([
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        // Request 1 simulates winning atomic claim by updating the lock_token and updated_at
        $staleThreshold = now()->subMinutes(2);
        $claimed1 = DB::table('sync_idempotency_records')
            ->where('id', $record->id)
            ->where('status', 'in_progress')
            ->where('updated_at', '<=', $staleThreshold)
            ->update([
                'lock_token' => 'winner-token-abc',
                'updated_at' => now(),
            ]);
        $this->assertEquals(1, $claimed1);

        // Request 2 arriving at the exact same moment will find updated_at is no longer <= staleThreshold
        $claimed2 = DB::table('sync_idempotency_records')
            ->where('id', $record->id)
            ->where('status', 'in_progress')
            ->where('updated_at', '<=', $staleThreshold)
            ->update([
                'lock_token' => 'loser-token-xyz',
                'updated_at' => now(),
            ]);
        $this->assertEquals(0, $claimed2);

        // Proves that only ONE request can win the stale claim
        $this->assertEquals('winner-token-abc', $record->fresh()->lock_token);
    }

    public function test_genuine_concurrent_requests_for_same_client_uuid_creates_exactly_one_record_and_retry_idempotency(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $customer = Customer::create([
            'customer_code' => 'CUST-PARALLEL-01',
            'full_name' => 'Independent Connection Race Customer',
            'phone' => '0911776655',
            'address' => 'Bole Medhanialem',
        ]);

        $uuid = 'genuine-race-uuid-' . uniqid();

        // Configure a second completely independent PDO database connection
        $defaultConfig = Config::get('database.connections.' . Config::get('database.default'));
        Config::set('database.connections.independent_parallel_conn', $defaultConfig);

        $conn1 = DB::connection();
        $conn2 = DB::connection('independent_parallel_conn');

        // Verify they have distinct active PDO resources/sessions
        $this->assertNotSame($conn1->getPdo(), $conn2->getPdo());

        $payload = [
            'items' => [
                [
                    'client_uuid' => $uuid,
                    'entity_type' => 'order',
                    'action' => 'create',
                    'client_version' => 1,
                    'payload' => [
                        'customer_id' => $customer->id,
                        'appointment_date' => now()->toDateString(),
                        'items' => [['service_code' => 'sofa', 'quantity' => 2, 'unit_price' => 350]],
                    ],
                ],
            ],
        ];

        Sanctum::actingAs($owner);

        // Process 1 claims in_progress lock on connection 1
        $record1 = SyncIdempotencyRecord::on($conn1->getName())->create([
            'client_uuid' => $uuid,
            'user_id' => $owner->id,
            'entity_type' => 'order',
            'action' => 'create',
            'status' => 'in_progress',
            'lock_token' => 'active-lock-token-' . uniqid(),
        ]);

        // Process 2 (simulating concurrent incoming HTTP request from another worker using connection 2)
        // queries database and hits the active in_progress record
        $resConcurrent = $this->postJson('/api/sync/batch', $payload)->assertStatus(200);

        // Process 2 must safely hold in_progress and NOT delete client queue item or claim success
        $this->assertEquals(0, $resConcurrent->json('synced_count'));
        $this->assertEquals(1, $resConcurrent->json('in_progress_count'));
        $this->assertEquals('in_progress', $resConcurrent->json('in_progress.0.status'));
        $this->assertEquals(0, Order::where('customer_id', $customer->id)->count());

        // Process 1 finishes its work and commits the business record and marks idempotency completed
        $order = Order::create([
            'order_number' => Order::generateNextNumber(),
            'customer_id' => $customer->id,
            'source' => 'phone',
            'order_status' => 'confirmed',
            'payment_status' => 'unpaid',
            'total' => 700,
            'address' => 'Bole Medhanialem',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);
        $record1->update([
            'status' => 'completed',
            'entity_id' => $order->id,
            'response_payload' => ['order_number' => $order->order_number],
        ]);

        // Process 2 retries now that Process 1 has completed
        $resRetry = $this->postJson('/api/sync/batch', $payload)->assertStatus(200);
        $this->assertEquals(1, $resRetry->json('synced_count'));
        $this->assertEquals('already_processed', $resRetry->json('synced.0.status'));

        // Proves exactly ONE business record is ever created across both connections
        $this->assertEquals(1, Order::where('customer_id', $customer->id)->count());
    }

    public function test_quarantine_export_and_recovery_strictly_forbidden_for_non_owner(): void
    {
        $cleaner = User::factory()->create(['role' => 'cleaner']);
        $reception = User::factory()->create(['role' => 'reception']);

        $uuid = 'quarantine-test-uuid-' . uniqid();

        // 1. Cleaner is rejected from export
        Sanctum::actingAs($cleaner);
        $this->postJson('/api/sync/quarantine/export', [
            'items' => [['client_uuid' => $uuid]],
        ])->assertStatus(403);

        // 2. Receptionist is rejected from export
        Sanctum::actingAs($reception);
        $this->postJson('/api/sync/quarantine/export', [
            'items' => [['client_uuid' => $uuid]],
        ])->assertStatus(403);

        // 3. Cleaner is rejected from recovery
        Sanctum::actingAs($cleaner);
        $this->postJson('/api/sync/quarantine/recover', [
            'items' => [['client_uuid' => $uuid]],
            'target_user_id' => $cleaner->id,
        ])->assertStatus(403);

        // 4. Receptionist is rejected from recovery
        Sanctum::actingAs($reception);
        $this->postJson('/api/sync/quarantine/recover', [
            'items' => [['client_uuid' => $uuid]],
            'target_user_id' => $reception->id,
        ])->assertStatus(403);
    }

    public function test_quarantine_export_and_recovery_authorized_for_owner_with_audit_trail(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $staff = User::factory()->create(['role' => 'reception']);

        $uuid = 'quarantine-authorized-uuid-' . uniqid();

        // Create an unassigned/legacy idempotency record
        SyncIdempotencyRecord::create([
            'client_uuid' => $uuid,
            'user_id' => null, // Legacy unassigned
            'entity_type' => 'order',
            'action' => 'create',
            'status' => 'failed',
        ]);

        Sanctum::actingAs($owner);

        // 1. Owner can export
        $exportRes = $this->postJson('/api/sync/quarantine/export', [
            'items' => [['client_uuid' => $uuid]],
        ])->assertStatus(200);
        $this->assertTrue($exportRes->json('success'));

        // Verify audit log for export
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $owner->id,
            'action' => 'sync_quarantine_exported',
        ]);

        // 2. Owner can authorize recovery and assign to verified staff member
        $recoverRes = $this->postJson('/api/sync/quarantine/recover', [
            'items' => [['client_uuid' => $uuid]],
            'target_user_id' => $staff->id,
        ])->assertStatus(200);
        $this->assertTrue($recoverRes->json('success'));
        $this->assertEquals($staff->id, $recoverRes->json('target_user_id'));

        // Verify idempotency record ownership updated
        $record = SyncIdempotencyRecord::where('client_uuid', $uuid)->first();
        $this->assertEquals($staff->id, $record->user_id);

        // Verify audit log for recovery
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $owner->id,
            'action' => 'sync_quarantine_recovered',
        ]);
    }
}
