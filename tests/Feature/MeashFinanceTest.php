<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MeashFinanceTest extends TestCase
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
            'name' => 'Finance Owner',
            'username' => 'finance_owner',
            'email' => 'fin_owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);
    }

    private function getOrCreateUnpaidOrder(): Order
    {
        $order = Order::where('payment_status', 'unpaid')->first();
        if ($order) {
            return $order;
        }

        $customer = \App\Models\Customer::first() ?: \App\Models\Customer::create([
            'name' => 'Finance Customer',
            'phone' => '0911002233',
            'address' => 'Bole',
        ]);

        return Order::create([
            'order_number' => 'ORD-FIN-001',
            'customer_id' => $customer->id,
            'source' => 'phone',
            'order_status' => 'pending',
            'payment_status' => 'unpaid',
            'total' => 2500,
            'address' => 'Bole',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);
    }

    public function test_payment_updates_order_payment_status_automatically(): void
    {
        $owner = $this->getOrCreateOwner();
        $order = $this->getOrCreateUnpaidOrder();

        $response = $this->actingAs($owner)->postJson("/api/orders/{$order->id}/payment", [
            'amount' => $order->total,
            'payment_method' => 'telebirr',
            'reference_number' => 'TB-TEST-12345',
        ]);

        $response->assertStatus(201);
        $order->refresh();

        $this->assertEquals('paid', $order->payment_status);
    }

    public function test_profit_report_calculates_real_net_profit(): void
    {
        $owner = $this->getOrCreateOwner();

        $response = $this->actingAs($owner)->getJson('/api/finance/profit-report?period=all_time');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('revenue', $data);
        $this->assertArrayHasKey('expenses', $data);
        $this->assertArrayHasKey('net_profit', $data);
        $this->assertEquals($data['revenue'] - $data['expenses'], $data['net_profit']);
    }
}
