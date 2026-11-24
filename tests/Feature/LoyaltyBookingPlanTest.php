<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Loyalty tiers (2026-11-24, commit c7db4bb): the website booking form
 * sends first_time / m1 / m2 / m4 / m5_8 as subscription_plan. The backend
 * must accept them, apply the fixed tier discount to the order total, and
 * must NOT create a recurring Subscription profile for them (plan_type
 * only holds weekly/biweekly/monthly). first_time = plain one-time booking.
 */
class LoyaltyBookingPlanTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = Service::create([
            'code' => 'floor_ceramic',
            'name_en' => 'Floor',
            'name_am' => 'ወለል',
            'base_price' => 60,
            'unit' => 'sqm',
            'is_active' => true,
            'description_en' => 'x',
            'description_am' => 'x',
            'sort_order' => 1,
        ]);
    }

    private function payload(string $plan): array
    {
        return [
            'customer_name' => 'Loyalty Test',
            'customer_phone' => '0911223344',
            'subcity' => 'Bole',
            'address' => 'Bole Atlas',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time_slot' => '09:00-11:00',
            'subscription_plan' => $plan,
            'payment_method' => 'cash',
            'items' => [[
                'service_id' => $this->service->id,
                'item_name' => 'Floor',
                'quantity' => 10,
                'unit_price' => 60,
            ]],
        ];
    }

    private function book(string $plan): Order
    {
        $res = $this->postJson('/api/public/book', $this->payload($plan));
        $res->assertStatus(201);
        $order = Order::where('order_number', $res->json('order_number'))->first();
        $this->assertNotNull($order, "Order must exist for plan {$plan}");

        return $order;
    }

    public function test_first_time_booking_is_a_plain_one_time_order(): void
    {
        $order = $this->book('first_time');

        $this->assertEquals('first_time', $order->subscription_plan);
        $this->assertEquals(600.0, (float) $order->subtotal);
        $this->assertEquals(0.0, (float) $order->subscription_discount);
        $this->assertEquals(600.0, (float) $order->total);
        $this->assertEquals(0, Subscription::count(), 'first_time must not create a Subscription');
    }

    public function test_m1_return_within_1_month_gets_50_percent_off(): void
    {
        $order = $this->book('m1');

        $this->assertEquals('m1', $order->subscription_plan);
        $this->assertEquals(300.0, (float) $order->subscription_discount);
        $this->assertEquals(300.0, (float) $order->total);
        $this->assertEquals(0, Subscription::count(), 'm1 must not create a Subscription');
    }

    public function test_m2_return_within_2_months_gets_30_percent_off(): void
    {
        $order = $this->book('m2');

        $this->assertEquals(180.0, (float) $order->subscription_discount);
        $this->assertEquals(420.0, (float) $order->total);
        $this->assertEquals(0, Subscription::count(), 'm2 must not create a Subscription');
    }

    public function test_m5_8_return_within_5_to_8_months_gets_15_percent_off(): void
    {
        $order = $this->book('m5_8');

        $this->assertEquals(90.0, (float) $order->subscription_discount);
        $this->assertEquals(510.0, (float) $order->total);
        $this->assertEquals(0, Subscription::count(), 'm5_8 must not create a Subscription');
    }

    public function test_weekly_still_creates_a_recurring_subscription(): void
    {
        $order = $this->book('weekly');

        $this->assertEquals(90.0, (float) $order->subscription_discount);
        $this->assertEquals(510.0, (float) $order->total);
        $this->assertEquals(1, Subscription::count(), 'weekly must still create a Subscription');
        $this->assertEquals('weekly', Subscription::first()->plan_type);
    }
}
