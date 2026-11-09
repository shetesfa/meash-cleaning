<?php

namespace Tests\Feature;

use App\Models\CleaningTeam;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeashOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function getOrCreateOwner(): User
    {
        return User::where('role', 'owner')->first() ?: User::create([
            'name' => 'Order Owner',
            'username' => 'order_owner',
            'email' => 'order_owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);
    }

    private function getOrCreateCleaner(): User
    {
        return User::where('role', 'cleaner')->first() ?: User::create([
            'name' => 'Order Cleaner',
            'username' => 'order_cleaner',
            'email' => 'order_cleaner@test.com',
            'password' => bcrypt('password'),
            'role' => 'cleaner',
        ]);
    }

    private function getOrCreateCustomer(): Customer
    {
        return Customer::first() ?: Customer::create([
            'name' => 'Order Customer',
            'phone' => '0911776655',
            'address' => 'Bole Medhanialem',
        ]);
    }

    private function getOrCreateService(string $code, string $name, float $price): Service
    {
        return Service::where('code', $code)->first() ?: Service::create([
            'name' => $name,
            'code' => $code,
            'category' => 'residential',
            'pricing_type' => 'fixed',
            'base_price' => $price,
            'unit' => 'piece',
            'is_active' => true,
        ]);
    }

    public function test_multi_item_order_calculates_totals_accurately(): void
    {
        $user = $this->getOrCreateOwner();
        $customer = $this->getOrCreateCustomer();
        $sofa = $this->getOrCreateService('sofa', 'Sofa Cleaning', 350.00);
        $carpet = $this->getOrCreateService('carpet', 'Carpet Cleaning', 80.00);

        $payload = [
            'customer_id' => $customer->id,
            'appointment_date' => now()->toDateString(),
            'appointment_time_slot' => '09:00 - 12:00',
            'address' => 'Bole Medhanialem',
            'subcity' => 'Bole',
            'discount' => 100,
            'items' => [
                [
                    'service_id' => $sofa->id,
                    'item_name' => 'Living Room Sofa',
                    'quantity' => 5,
                    'unit_price' => 350.00,
                ],
                [
                    'service_id' => $carpet->id,
                    'item_name' => 'Bedroom Carpet',
                    'quantity' => 10,
                    'unit_price' => 80.00,
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/orders', $payload);

        $response->assertStatus(201);
        $order = Order::find($response->json('order.id'));

        $this->assertNotNull($order);
        // Subtotal = (5 * 350) + (10 * 80) = 1750 + 800 = 2550
        // Total = 2550 - 100 = 2450
        $this->assertEquals(2550.00, (float)$order->subtotal);
        $this->assertEquals(2450.00, (float)$order->total);
        $this->assertCount(2, $order->items);
    }

    public function test_cleaner_can_complete_job_and_schedule_followup(): void
    {
        $cleaner = $this->getOrCreateCleaner();
        $customer = $this->getOrCreateCustomer();
        $order = Order::firstOrCreate(
            ['order_status' => 'assigned'],
            [
                'order_number' => Order::generateNextNumber(),
                'customer_id' => $customer->id,
                'source' => 'phone',
                'order_status' => 'assigned',
                'payment_status' => 'unpaid',
                'appointment_date' => now()->toDateString(),
                'address' => 'Bole',
                'subcity' => 'Bole',
                'total' => 1000,
            ]
        );

        $cleanerTeamId = \Illuminate\Support\Facades\DB::table('team_members')->where('user_id', $cleaner->id)->value('cleaning_team_id')
            ?: \App\Models\CleaningTeam::where('team_leader_id', $cleaner->id)->value('id');
        if ($cleanerTeamId) {
            $order->assigned_team_id = $cleanerTeamId;
            $order->save();
        }

        $response = $this->actingAs($cleaner)->postJson("/api/teams/jobs/{$order->id}/action", [
            'action' => 'complete',
            'notes' => 'Job done with steam extraction.',
        ]);

        $response->assertStatus(200);
        $order->refresh();

        $this->assertEquals('completed', $order->order_status);
        $this->assertNotNull($order->completed_at);
        $this->assertDatabaseHas('followups', [
            'order_id' => $order->id,
            'status' => 'pending',
        ]);
    }
}
