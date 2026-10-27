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
}
