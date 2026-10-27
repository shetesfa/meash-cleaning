<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_sync_returns_errors_for_failed_items(): void
    {
        $user = User::factory()->create([
            'role' => 'owner',
        ]);
        Sanctum::actingAs($user);

        // Try to sync an update on non-existent order
        $response = $this->postJson('/api/sync/batch', [
            'items' => [
                [
                    'client_uuid' => 'test-uuid-fail-999',
                    'entity_type' => 'order',
                    'action' => 'job_action',
                    'payload' => [
                        'order_id' => 9999999, // non-existent order
                        'order_status' => 'completed',
                    ],
                ]
            ]
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('errors', $data);
        $this->assertGreaterThan(0, $data['error_count']);
        $this->assertEquals('test-uuid-fail-999', $data['errors'][0]['client_uuid']);
    }
}
