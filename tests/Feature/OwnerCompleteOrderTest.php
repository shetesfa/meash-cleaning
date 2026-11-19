<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Followup;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A3 — Owner mark-complete button.
 *
 * The owner had no way to mark an order complete (completion was a
 * cleaner-side action only). New endpoint POST /api/orders/{order}/complete
 * (owner only) reuses the same completion mechanics as the cleaner flow:
 * status -> completed, completed_at set, review follow-up row created,
 * OrderLifecycleService review trigger, audit log.
 */
class OwnerCompleteOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $role, string $username): User
    {
        return User::where('role', $role)->first() ?: User::create([
            'name' => ucfirst($username),
            'username' => $username,
            'email' => $username . '@test.com',
            'password' => bcrypt('password'),
            'role' => $role,
        ]);
    }

    private function makeOrder(string $status): Order
    {
        $customer = Customer::first() ?: Customer::create([
            'full_name' => 'Complete Customer',
            'phone' => '0911770003',
            'address' => 'Bole Medhanialem',
        ]);

        return Order::create([
            'order_number' => 'MEASH-' . str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'order_status' => $status,
            'payment_status' => 'unpaid',
            'total' => 60,
            'appointment_date' => now()->toDateString(),
            'appointment_time_slot' => 'morning',
            'address' => 'Bole Medhanialem',
        ]);
    }

    public function test_owner_can_complete_assigned_order(): void
    {
        $order = $this->makeOrder('assigned');

        $response = $this->actingAs($this->user('owner', 'complete_owner'))
            ->postJson("/api/orders/{$order->id}/complete", [
                'completion_notes' => 'All rooms cleaned.',
            ]);

        $response->assertOk()->assertJson(['success' => true]);
        $order->refresh();
        $this->assertSame('completed', $order->order_status);
        $this->assertNotNull($order->completed_at);
        $this->assertSame('All rooms cleaned.', $order->completion_notes);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $order->id,
            'action' => 'order_completed',
        ]);
    }

    public function test_owner_can_complete_from_on_the_way_and_cleaning(): void
    {
        foreach (['on_the_way', 'cleaning'] as $status) {
            $order = $this->makeOrder($status);

            $this->actingAs($this->user('owner', 'complete_owner'))
                ->postJson("/api/orders/{$order->id}/complete")
                ->assertOk();

            $this->assertSame('completed', $order->fresh()->order_status);
        }
    }

    public function test_completion_creates_review_followup(): void
    {
        $order = $this->makeOrder('assigned');

        $this->actingAs($this->user('owner', 'complete_owner'))
            ->postJson("/api/orders/{$order->id}/complete")
            ->assertOk();

        $this->assertDatabaseHas('followups', [
            'order_id' => $order->id,
            'type' => 'review',
            'status' => 'pending',
        ]);
    }

    public function test_complete_fails_when_order_is_still_new(): void
    {
        $order = $this->makeOrder('new');

        $response = $this->actingAs($this->user('owner', 'complete_owner'))
            ->postJson("/api/orders/{$order->id}/complete");

        $response->assertStatus(422);
        $this->assertSame('new', $order->fresh()->order_status);
        $this->assertNull($order->fresh()->completed_at);
    }

    public function test_reception_and_cleaner_cannot_use_owner_complete_endpoint(): void
    {
        $order = $this->makeOrder('assigned');

        $this->actingAs($this->user('reception', 'complete_reception'))
            ->postJson("/api/orders/{$order->id}/complete")
            ->assertStatus(403);

        $this->actingAs($this->user('cleaner', 'complete_cleaner'))
            ->postJson("/api/orders/{$order->id}/complete")
            ->assertStatus(403);

        $this->assertSame('assigned', $order->fresh()->order_status);
    }

    public function test_completed_order_shows_completed_state(): void
    {
        $order = $this->makeOrder('cleaning');

        $this->actingAs($this->user('owner', 'complete_owner'))
            ->postJson("/api/orders/{$order->id}/complete")
            ->assertOk();

        $response = $this->actingAs($this->user('owner', 'complete_owner'))
            ->getJson("/api/orders/{$order->id}");

        $response->assertOk();
        $this->assertSame('completed', $response->json('data.order_status') ?? $response->json('order_status'));
    }
}
