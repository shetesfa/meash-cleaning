<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeashChannelAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Service $service;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@meash.com',
            'phone' => '0911000001',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->service = Service::create([
            'code' => 'sofa',
            'name_en' => 'Sofa Cleaning',
            'name_am' => 'የሶፋ ጥልቅ እጥበት',
            'category' => 'furniture',
            'base_price' => 350.00,
            'unit' => 'ወንበር',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUST-0001',
            'full_name' => 'አበበ ከበደ',
            'phone' => '0911223344',
            'subcity' => 'Bole',
            'address' => 'Bole Medhanialem',
        ]);
    }

    public function test_order_filtering_by_channel_source(): void
    {
        // 1. Create orders from different sources
        Order::create([
            'order_number' => 'ORD-SRC-01',
            'customer_id' => $this->customer->id,
            'source' => 'website',
            'order_status' => 'new',
            'payment_status' => 'unpaid',
            'total' => 1050,
            'appointment_date' => now()->addDay()->toDateString(),
            'address' => 'Bole',
        ]);

        Order::create([
            'order_number' => 'ORD-SRC-02',
            'customer_id' => $this->customer->id,
            'source' => 'telegram_miniapp',
            'order_status' => 'new',
            'payment_status' => 'unpaid',
            'total' => 700,
            'appointment_date' => now()->addDay()->toDateString(),
            'address' => 'Yeka',
        ]);

        Order::create([
            'order_number' => 'ORD-SRC-03',
            'customer_id' => $this->customer->id,
            'source' => 'telegram_bot',
            'order_status' => 'new',
            'payment_status' => 'unpaid',
            'total' => 350,
            'appointment_date' => now()->addDay()->toDateString(),
            'address' => 'Kirkos',
        ]);

        // 2. Query filter for website
        $resWeb = $this->actingAs($this->admin, 'sanctum')->getJson('/api/orders?source=website');
        $resWeb->assertOk();
        $this->assertEquals(1, count($resWeb->json('data')));
        $this->assertEquals('ORD-SRC-01', $resWeb->json('data.0.order_number'));

        // 3. Query filter for telegram_miniapp
        $resTma = $this->actingAs($this->admin, 'sanctum')->getJson('/api/orders?source=telegram_miniapp');
        $resTma->assertOk();
        $this->assertEquals(1, count($resTma->json('data')));
        $this->assertEquals('ORD-SRC-02', $resTma->json('data.0.order_number'));
    }

    public function test_payment_methods_normalization_and_order_payment(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-PAY-01',
            'customer_id' => $this->customer->id,
            'source' => 'website',
            'order_status' => 'new',
            'payment_status' => 'unpaid',
            'total' => 1000,
            'appointment_date' => now()->addDay()->toDateString(),
            'address' => 'Bole',
        ]);

        // Record telebirr payment
        $payRes = $this->actingAs($this->admin, 'sanctum')->postJson("/api/orders/{$order->id}/payments", [
            'amount' => 400,
            'payment_method' => 'telebirr',
            'reference_number' => 'TEL-12345',
            'payment_date' => now()->toDateString(),
        ]);
        $payRes->assertStatus(201);

        $order->refresh();
        $this->assertEquals('partially_paid', $order->payment_status);

        // Record cbe payment for the rest
        $payRes2 = $this->actingAs($this->admin, 'sanctum')->postJson("/api/orders/{$order->id}/payments", [
            'amount' => 600,
            'payment_method' => 'cbe',
            'reference_number' => 'CBE-67890',
            'payment_date' => now()->toDateString(),
        ]);
        $payRes2->assertStatus(201);

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
    }

    public function test_dashboard_channel_and_payment_breakdown(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-DASH-01',
            'customer_id' => $this->customer->id,
            'source' => 'website',
            'order_status' => 'new',
            'payment_status' => 'paid',
            'total' => 1500,
            'appointment_date' => now()->toDateString(),
            'address' => 'Bole',
        ]);

        Payment::create([
            'payment_number' => 'PAY-001',
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'amount' => 1500,
            'payment_method' => 'telebirr',
            'payment_date' => now()->toDateString(),
        ]);

        $res = $this->actingAs($this->admin, 'sanctum')->getJson('/api/dashboard/owner');
        $res->assertOk();
        $res->assertJsonStructure([
            'channel_breakdown' => [
                'website',
                'telegram_miniapp',
                'telegram_bot',
                'phone',
                'walk_in',
                'internal',
            ],
            'payment_breakdown' => [
                'cash',
                'telebirr',
                'cbe',
                'bank_transfer',
            ]
        ]);

        $this->assertEquals(1, $res->json('channel_breakdown.website.orders_count'));
        $this->assertEquals(1500, $res->json('channel_breakdown.website.total_revenue'));
    }
}
