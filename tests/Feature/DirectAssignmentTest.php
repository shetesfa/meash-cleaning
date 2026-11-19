<?php

namespace Tests\Feature;

use App\Models\CleaningTeam;
use App\Models\Customer;
use App\Models\Order;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A2 — Direct cleaner assignment silently failed.
 *
 * Root cause: OrderController::assignTeam refused a team-less cleaner with
 * 422 ("worker has no team, create a team first") by design, and the
 * assign modal stayed open on failure, so the owner saw nothing change.
 * Fix: a team-less cleaner is now assigned via a visible auto-created
 * solo team ("<name> (Solo)"), and the response says so. Failures
 * (e.g. a non-cleaner) still return a clear 422 message, which the
 * frontend shows with alert().
 */
class DirectAssignmentTest extends TestCase
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
            'name' => 'Assign Owner',
            'username' => 'assign_owner',
            'email' => 'assign_owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);
    }

    private function makeCleaner(string $username, string $name): User
    {
        return User::create([
            'name' => $name,
            'username' => $username,
            'email' => $username . '@test.com',
            'password' => bcrypt('password'),
            'phone' => '09000000' . random_int(10, 99),
            'role' => 'cleaner',
        ]);
    }

    private function makeOrder(string $status = 'new'): Order
    {
        $customer = Customer::first() ?: Customer::create([
            'full_name' => 'Assign Customer',
            'phone' => '0911770002',
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

    public function test_direct_assign_of_teamless_cleaner_succeeds_and_creates_solo_team(): void
    {
        $cleaner = $this->makeCleaner('solo_cleaner', 'Solo Cleaner');
        $order = $this->makeOrder('new');

        $response = $this->actingAs($this->owner())
            ->postJson("/api/orders/{$order->id}/assign-team", [
                'worker_id' => $cleaner->id,
            ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertStringContainsString('solo team', strtolower($response->json('message')));

        $order->refresh();
        $this->assertSame('assigned', $order->order_status);
        $this->assertNotNull($order->assigned_team_id);

        $team = CleaningTeam::find($order->assigned_team_id);
        $this->assertSame('Solo Cleaner (Solo)', $team->team_name);
        $this->assertSame($cleaner->id, $team->team_leader_id);
        $this->assertSame('active', $team->status);
        $this->assertDatabaseHas('team_members', [
            'cleaning_team_id' => $team->id,
            'user_id' => $cleaner->id,
            'role_in_team' => 'leader',
        ]);
        $this->assertDatabaseHas('appointments', [
            'order_id' => $order->id,
            'cleaning_team_id' => $team->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_direct_assign_of_cleaner_with_team_uses_existing_team(): void
    {
        $cleaner = $this->makeCleaner('teamed_cleaner', 'Teamed Cleaner');
        $team = CleaningTeam::create([
            'team_name' => 'Existing Team',
            'team_leader_id' => $cleaner->id,
            'status' => 'active',
        ]);
        TeamMember::create([
            'cleaning_team_id' => $team->id,
            'user_id' => $cleaner->id,
            'role_in_team' => 'leader',
        ]);
        $order = $this->makeOrder('new');

        $response = $this->actingAs($this->owner())
            ->postJson("/api/orders/{$order->id}/assign-team", [
                'worker_id' => $cleaner->id,
            ]);

        $response->assertOk();
        $this->assertSame($team->id, $order->fresh()->assigned_team_id);
        $this->assertSame(1, CleaningTeam::where('team_leader_id', $cleaner->id)->count());
    }

    public function test_direct_assign_of_non_cleaner_fails_with_clear_message(): void
    {
        $reception = User::where('role', 'reception')->first() ?: User::create([
            'name' => 'Assign Reception',
            'username' => 'assign_reception',
            'email' => 'assign_reception@test.com',
            'password' => bcrypt('password'),
            'role' => 'reception',
        ]);
        $order = $this->makeOrder('new');

        $response = $this->actingAs($this->owner())
            ->postJson("/api/orders/{$order->id}/assign-team", [
                'worker_id' => $reception->id,
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('cleaner', strtolower($response->json('message')));
        $this->assertSame('new', $order->fresh()->order_status);
        $this->assertNull($order->fresh()->assigned_team_id);
    }

    public function test_team_assignment_still_works(): void
    {
        $cleaner = $this->makeCleaner('team_assign_cleaner', 'Team Assign Cleaner');
        $team = CleaningTeam::create([
            'team_name' => 'Assign Team',
            'team_leader_id' => $cleaner->id,
            'status' => 'active',
        ]);
        $order = $this->makeOrder('confirmed');

        $response = $this->actingAs($this->owner())
            ->postJson("/api/orders/{$order->id}/assign-team", [
                'team_id' => $team->id,
            ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSame('assigned', $order->fresh()->order_status);
        $this->assertSame($team->id, $order->fresh()->assigned_team_id);
    }
}
