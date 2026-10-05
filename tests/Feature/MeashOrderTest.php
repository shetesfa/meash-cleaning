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
    public function test_multi_item_order_calculates_totals_accurately(): void
    {
        $user = User::where('role', 'owner')->first();
        $customer = Customer::first();
        $sofa = Service::where('code', 'sofa')->first();
        $carpet = Service::where('code', 'carpet')->first();

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
        $cleaner = User::where('role', 'cleaner')->first();
        $customer = Customer::first();
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
