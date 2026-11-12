<?php

namespace Tests\Feature;

use App\Models\CleaningTeam;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleaners_cannot_see_other_teams_orders(): void
    {
        $cleanerUser = User::factory()->create([
            'role' => 'cleaner',
        ]);

        $team1 = CleaningTeam::create([
            'team_name' => 'Team 1',
            'team_leader_id' => $cleanerUser->id,
            'leader_name' => 'Leader 1',
            'phone' => '0911111111',
            'status' => 'available'
        ]);

        $team2 = CleaningTeam::create([
            'team_name' => 'Team 2',
            'leader_name' => 'Leader 2',
            'phone' => '0922222222',
            'status' => 'available'
        ]);

        $customer = Customer::create([
            'full_name' => 'Abebe Kebede',
            'phone' => '0911000000',
        ]);

        // Order for team 1 (accessible)
        $order1 = Order::create([
            'order_number' => 'ORD-101',
            'customer_id' => $customer->id,
            'assigned_team_id' => $team1->id,
            'status' => 'assigned',
            'appointment_date' => now()->toDateString(),
            'appointment_time_slot' => 'morning',
            'address' => 'Bole',
            'total_amount' => 1000,
        ]);

        // Order for team 2 (forbidden)
        $order2 = Order::create([
            'order_number' => 'ORD-102',
            'customer_id' => $customer->id,
            'assigned_team_id' => $team2->id,
            'status' => 'assigned',
            'appointment_date' => now()->toDateString(),
            'appointment_time_slot' => 'afternoon',
            'address' => 'Yeka',
            'total_amount' => 2000,
        ]);

        Sanctum::actingAs($cleanerUser);

        // Cleaner listing orders should only include team 1's orders
        $response = $this->getJson('/api/orders');
        $response->assertStatus(200);

        $orderNumbers = collect($response->json('data'))->pluck('order_number')->all();
        $this->assertContains('ORD-101', $orderNumbers);
        $this->assertNotContains('ORD-102', $orderNumbers);

        // Accessing other team's order directly must return 403
        $forbiddenResponse = $this->getJson("/api/orders/{$order2->id}");
        $forbiddenResponse->assertStatus(403);
    }

    public function test_cleaners_cannot_see_customers_list(): void
    {
        $cleanerUser = User::factory()->create([
            'role' => 'cleaner',
        ]);

        Sanctum::actingAs($cleanerUser);

        $response = $this->getJson('/api/customers');
        $response->assertStatus(403);
    }

    /**
     * Active users across all legitimate roles can authenticate and access authorized routes.
     */
    public function test_active_users_across_all_roles_access_authorized_endpoints(): void
    {
        $roles = [
            'owner' => '/api/dashboard/owner',
            'reception' => '/api/dashboard/reception',
            'finance' => '/api/finance/expenses',
            'sales' => '/api/sales/pipeline',
            'cleaner' => '/api/teams/my-jobs',
        ];

        foreach ($roles as $role => $endpoint) {
            $user = User::factory()->create([
                'role' => $role,
                'is_active' => true,
            ]);

            Sanctum::actingAs($user);
            $res = $this->getJson($endpoint);
            $this->assertNotEquals(403, $res->status(), "Role '{$role}' was unexpectedly denied (403) at {$endpoint}");
            $this->assertNotEquals(401, $res->status(), "Role '{$role}' was unauthenticated (401) at {$endpoint}");
        }
    }

    /**
     * Inactive (suspended) users across all roles are strictly rejected with 403.
     */
    public function test_inactive_suspended_users_are_rejected_with_403(): void
    {
        $roles = ['owner', 'reception', 'finance', 'cleaner', 'sales'];

        foreach ($roles as $role) {
            $suspendedUser = User::factory()->create([
                'role' => $role,
                'is_active' => false,
            ]);

            Sanctum::actingAs($suspendedUser);
            $res = $this->getJson('/api/orders');
            $res->assertStatus(403);
            $res->assertJson([
                'message' => 'Your account has been deactivated. Please contact the administrator.',
            ]);
        }
    }

    /**
     * Role privilege isolation: Reception, Finance, Cleaner, Sales cannot breach role boundaries.
     */
    public function test_role_boundary_isolation_enforced(): void
    {
        $reception = User::factory()->create(['role' => 'reception']);
        $finance = User::factory()->create(['role' => 'finance']);
        $sales = User::factory()->create(['role' => 'sales']);
        $cleaner = User::factory()->create(['role' => 'cleaner']);

        // Reception cannot access owner-only admin summary
        Sanctum::actingAs($reception);
        $this->getJson('/api/dashboard/owner')->assertStatus(403);

        // Finance cannot access owner-only admin summary
        Sanctum::actingAs($finance);
        $this->getJson('/api/dashboard/owner')->assertStatus(403);

        // Sales cannot access finance expenses
        Sanctum::actingAs($sales);
        $this->getJson('/api/finance/expenses')->assertStatus(403);

        // Cleaner cannot access finance expenses or sales pipeline
        Sanctum::actingAs($cleaner);
        $this->getJson('/api/finance/expenses')->assertStatus(403);
        $this->getJson('/api/sales/pipeline')->assertStatus(403);
    }
}
