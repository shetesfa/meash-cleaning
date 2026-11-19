<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A1 — Order seen/accept buttons.
 *
 * The owner UI had no seen/accept actions. New endpoints:
 * POST /api/orders/{order}/seen   (new -> pending_confirmation)
 * POST /api/orders/{order}/accept (new / pending_confirmation -> confirmed)
 * Owner + reception only.
 */
class OrderSeenAcceptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function owner(): User
    {
        return User::where('role', 'owner')->first() ?: User::create([
            'name' => 'Seen Owner',
            'username' => 'seen_owner',
            'email' => 'seen_owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);
    }

    private function reception(): User
    {
        return User::where('role', 'reception')->first() ?: User::create([
            'name' => 'Seen Reception',
            'username' => 'seen_reception',
            'email' => 'seen_reception@test.com',
            'password' => bcrypt('password'),
            'role' => 'reception',
        ]);
    }

    private function cleaner(): User
    {
        return User::where('role', 'cleaner')->first() ?: User::create([
            'name' => 'Seen Cleaner',
            'username' => 'seen_cleaner',
            'email' => 'seen_cleaner@test.com',
            'password' => bcrypt('password'),
            'role' => 'cleaner',
        ]);
    }

    private function makeOrder(string $status = 'new'): Order
    {
        $customer = Customer::first() ?: Customer::create([
            'full_name' => 'Seen Customer',
            'phone' => '0911770001',
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

    public function test_owner_can_mark_new_order_as_seen(): void
    {
        $order = $this->makeOrder('new');

        $response = $this->actingAs($this->owner())
            ->postJson("/api/orders/{$order->id}/seen");

        $response->assertOk()->assertJson([
            'success' => true,
        ]);
        $this->assertSame('pending_confirmation', $order->fresh()->order_status);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $order->id,
            'action' => 'order_seen',
        ]);
    }

    public function test_reception_can_mark_new_order_as_seen(): void
    {
        $order = $this->makeOrder('new');

        $response = $this->actingAs($this->reception())
            ->postJson("/api/orders/{$order->id}/seen");

        $response->assertOk();
        $this->assertSame('pending_confirmation', $order->fresh()->order_status);
    }

    public function test_seen_fails_when_order_is_not_new(): void
    {
        $order = $this->makeOrder('confirmed');

        $response = $this->actingAs($this->owner())
            ->postJson("/api/orders/{$order->id}/seen");

        $response->assertStatus(422);
        $this->assertSame('confirmed', $order->fresh()->order_status);
    }

    public function test_owner_can_accept_new_order_directly(): void
    {
        $order = $this->makeOrder('new');

        $response = $this->actingAs($this->owner())
            ->postJson("/api/orders/{$order->id}/accept");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSame('confirmed', $order->fresh()->order_status);
    }

    public function test_owner_can_accept_seen_order(): void
    {
        $order = $this->makeOrder('pending_confirmation');

        $response = $this->actingAs($this->owner())
            ->postJson("/api/orders/{$order->id}/accept");

        $response->assertOk();
        $this->assertSame('confirmed', $order->fresh()->order_status);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $order->id,
            'action' => 'order_accepted',
        ]);
    }

    public function test_accept_fails_when_order_already_assigned(): void
    {
        $order = $this->makeOrder('assigned');

        $response = $this->actingAs($this->owner())
            ->postJson("/api/orders/{$order->id}/accept");

        $response->assertStatus(422);
        $this->assertSame('assigned', $order->fresh()->order_status);
    }

    public function test_cleaner_cannot_mark_seen_or_accept(): void
    {
        $order = $this->makeOrder('new');

        $this->actingAs($this->cleaner())
            ->postJson("/api/orders/{$order->id}/seen")
            ->assertStatus(403);

        $this->actingAs($this->cleaner())
            ->postJson("/api/orders/{$order->id}/accept")
            ->assertStatus(403);

        $this->assertSame('new', $order->fresh()->order_status);
    }

    public function test_accepted_order_shows_confirmed_state(): void
    {
        $order = $this->makeOrder('new');

        $this->actingAs($this->owner())
            ->postJson("/api/orders/{$order->id}/accept")
            ->assertOk();

        $response = $this->actingAs($this->owner())
            ->getJson("/api/orders/{$order->id}");

        $response->assertOk();
        $this->assertSame('confirmed', $response->json('data.order_status') ?? $response->json('order_status'));
    }
}
