<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Tests\TestCase;

class MeashOfflineSyncTest extends TestCase
{
    public function test_offline_sync_batch_creates_order_idempotently(): void
    {
        $cleaner = User::where('role', 'cleaner')->first();
        $customer = Customer::first();
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
        $response1 = $this->actingAs($cleaner)->postJson('/api/sync/batch', $batchPayload);
        $response1->assertStatus(200);
        $this->assertEquals(1, $response1->json('synced_count'));

        // Repeat sync with same client_uuid (Idempotency test)
        $response2 = $this->actingAs($cleaner)->postJson('/api/sync/batch', $batchPayload);
        $response2->assertStatus(200);
        $this->assertEquals('already_processed', $response2->json('synced.0.status'));
    }

    public function test_sync_detects_conflict_when_server_version_diverges(): void
    {
        $cleaner = User::where('role', 'cleaner')->first();
        $order = Order::first();

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
}
