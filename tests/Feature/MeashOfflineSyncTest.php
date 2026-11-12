<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MeashOfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function getOrCreateStaff(): User
    {
        return User::whereIn('role', ['reception', 'owner'])->first() ?: User::create([
            'name' => 'Sync Staff',
            'username' => 'sync_staff',
            'email' => 'sync_staff@test.com',
            'password' => bcrypt('password'),
            'role' => 'reception',
        ]);
    }

    private function getOrCreateCleaner(): User
    {
        return User::where('role', 'cleaner')->first() ?: User::create([
            'name' => 'Sync Cleaner',
            'username' => 'sync_cleaner',
            'email' => 'sync_cleaner@test.com',
            'password' => bcrypt('password'),
            'role' => 'cleaner',
        ]);
    }

    private function getOrCreateCustomer(): Customer
    {
        return Customer::first() ?: Customer::create([
            'name' => 'Sync Customer',
            'phone' => '0911998877',
            'address' => 'Bole Medhanialem',
        ]);
    }

    private function getOrCreateOrder(): Order
    {
        $order = Order::first();
        if ($order) return $order;

        $customer = $this->getOrCreateCustomer();
        return Order::create([
            'order_number' => 'ORD-SYNC-001',
            'customer_id' => $customer->id,
            'source' => 'phone',
            'order_status' => 'assigned',
            'payment_status' => 'unpaid',
            'total' => 1400,
            'address' => 'Bole Medhanialem',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);
    }

    public function test_offline_sync_batch_creates_order_idempotently(): void
    {
        $staff = $this->getOrCreateStaff();
        $customer = $this->getOrCreateCustomer();
        $uuid = 'test-client-uuid-' . uniqid();

        $batchPayload = [
            'items' => [
                [
                    'client_uuid' => $uuid,
                    'entity_type' => 'order',
                    'action' => 'create',
                    'client_version' => 1,
                    'payload' => [
                        'customer_id' => $customer->id,
                        'appointment_date' => now()->addDays(2)->toDateString(),
                        'appointment_time_slot' => 'Morning',
                        'address' => 'Bole Medhanialem',
                        'subcity' => 'Bole',
                        'items' => [
                            [
                                'item_name' => 'Sofa Cleaning',
                                'quantity' => 4,
                                'unit_price' => 350.00,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // First sync
        $response1 = $this->actingAs($staff)->postJson('/api/sync/batch', $batchPayload);
        $response1->assertStatus(200);
        $this->assertEquals(1, $response1->json('synced_count'));

        // Repeat sync with same client_uuid (Idempotency test)
        $response2 = $this->actingAs($staff)->postJson('/api/sync/batch', $batchPayload);
        $response2->assertStatus(200);
        $this->assertEquals('already_processed', $response2->json('synced.0.status'));
    }

    public function test_sync_detects_conflict_when_server_version_diverges(): void
    {
        $cleaner = $this->getOrCreateCleaner();
        $order = $this->getOrCreateOrder();

        // Simulate server updated ahead (version incremented to 5, status cancelled by owner)
        $order->version = 5;
        $order->order_status = 'cancelled';
        $order->save();

        $conflictUuid = 'conflict-uuid-' . uniqid();
        $batchPayload = [
            'items' => [
                [
                    'client_uuid' => $conflictUuid,
                    'entity_type' => 'order',
                    'action' => 'job_action',
                    'client_version' => 1, // Cleaner has older version
                    'payload' => [
                        'order_id' => $order->id,
                        'action' => 'complete',
                        'order_status' => 'completed',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($cleaner)->postJson('/api/sync/batch', $batchPayload);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('conflict_count'));
        $this->assertDatabaseHas('sync_conflicts', [
            'entity_id' => $order->id,
            'client_uuid' => $conflictUuid,
        ]);
    }

    /**
     * Scenario C: Invalid or manipulated client price is overridden by authoritative catalog price.
     */
    public function test_sync_ignores_client_forged_prices_and_uses_catalog_prices(): void
    {
        $staff = $this->getOrCreateStaff();
        $customer = $this->getOrCreateCustomer();
        $uuid = 'test-forged-price-' . uniqid();

        $batchPayload = [
            'items' => [
                [
                    'client_uuid' => $uuid,
                    'entity_type' => 'order',
                    'action' => 'create',
                    'client_version' => 1,
                    'payload' => [
                        'customer_id' => $customer->id,
                        'appointment_date' => now()->addDays(2)->toDateString(),
                        'appointment_time_slot' => 'Morning',
                        'address' => 'Bole Medhanialem',
                        'items' => [
                            [
                                'item_name' => 'Sofa Cleaning',
                                'quantity' => 2,
                                'unit_price' => 1.00, // Client tries to forge 1 ETB instead of catalog price (350 ETB)
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($staff)->postJson('/api/sync/batch', $batchPayload);
        $response->assertStatus(200);

        $orderId = $response->json('synced.0.entity_id');
        $order = Order::find($orderId);

        // Server calculated 2 * 350 = 700 ETB, NOT 2 * 1 = 2 ETB!
        $this->assertEquals(700.00, (float)$order->total);
    }

    /**
     * Scenario D: Forged paid status in sync payload is strictly rejected and forced to unpaid.
     */
    public function test_sync_enforces_unpaid_status_even_if_client_claims_paid(): void
    {
        $staff = $this->getOrCreateStaff();
        $customer = $this->getOrCreateCustomer();
        $uuid = 'test-forged-status-' . uniqid();

        $batchPayload = [
            'items' => [
                [
                    'client_uuid' => $uuid,
                    'entity_type' => 'order',
                    'action' => 'create',
                    'client_version' => 1,
                    'payload' => [
                        'customer_id' => $customer->id,
                        'appointment_date' => now()->addDays(1)->toDateString(),
                        'address' => 'Bole',
                        'payment_status' => 'paid', // Forged paid status
                        'items' => [
                            [
                                'item_name' => 'Carpet Cleaning',
                                'quantity' => 10,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($staff)->postJson('/api/sync/batch', $batchPayload);
        $response->assertStatus(200);

        $orderId = $response->json('synced.0.entity_id');
        $order = Order::find($orderId);

        // Server strictly forces unpaid status for synced offline orders
        $this->assertEquals('unpaid', $order->payment_status);
    }

    /**
     * Scenario E: Unauthenticated request to sync returns 401.
     */
    public function test_sync_without_token_returns_401(): void
    {
        $batchPayload = [
            'items' => [
                [
                    'client_uuid' => 'unauth-uuid-1',
                    'entity_type' => 'order',
                    'action' => 'create',
                    'payload' => [],
                ],
            ],
        ];

        $this->postJson('/api/sync/batch', $batchPayload)->assertStatus(401);
    }

    /**
     * Scenario B: UUID ownership scoping prevents another user from hijacking an existing UUID.
     */
    public function test_sync_prevents_uuid_hijacking_across_different_users(): void
    {
        $user1 = $this->getOrCreateStaff();
        $user2 = User::create([
            'name' => 'Other Staff',
            'username' => 'other_staff_' . uniqid(),
            'email' => 'other_staff_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role' => 'reception',
        ]);
        $customer = $this->getOrCreateCustomer();
        $uuid = 'shared-uuid-' . uniqid();

        $batchPayload = [
            'items' => [
                [
                    'client_uuid' => $uuid,
                    'entity_type' => 'order',
                    'action' => 'create',
                    'payload' => [
                        'customer_id' => $customer->id,
                        'address' => 'Bole',
                        'items' => [['item_name' => 'Sofa Cleaning', 'quantity' => 1]],
                    ],
                ],
            ],
        ];

        // User 1 syncs successfully
        $res1 = $this->actingAs($user1)->postJson('/api/sync/batch', $batchPayload);
        $res1->assertStatus(200);

        // User 2 attempts to use User 1's UUID
        $res2 = $this->actingAs($user2)->postJson('/api/sync/batch', $batchPayload);
        $res2->assertStatus(200);
        $this->assertNotEmpty($res2->json('errors'));
        $this->assertStringContainsString('ownership mismatch', $res2->json('errors.0.error'));
    }
}
