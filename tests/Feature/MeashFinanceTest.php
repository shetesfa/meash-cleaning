<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Tests\TestCase;

class MeashFinanceTest extends TestCase
{
    public function test_payment_updates_order_payment_status_automatically(): void
    {
        $owner = User::where('role', 'owner')->first();
        $order = Order::where('payment_status', 'unpaid')->first();

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
        $owner = User::where('role', 'owner')->first();

        $response = $this->actingAs($owner)->getJson('/api/finance/profit-report?period=all_time');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('revenue', $data);
        $this->assertArrayHasKey('expenses', $data);
        $this->assertArrayHasKey('net_profit', $data);
        $this->assertEquals($data['revenue'] - $data['expenses'], $data['net_profit']);
    }
}
