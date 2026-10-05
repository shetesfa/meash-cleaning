<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\TelegramController;
use App\Models\Order;
use Tests\TestCase;

class MeashTelegramTest extends TestCase
{
    public function test_telegram_inline_query_generates_rich_cards(): void
    {
        $controller = new TelegramController();

        // 1. General search
        $results = $controller->buildInlineResults('');
        $this->assertNotEmpty($results);
        $this->assertEquals('article', $results[0]['type']);
        $this->assertStringContainsString('Meash Cleaning Solution', $results[0]['title']);

        // 2. Specific search for sofa
        $sofaResults = $controller->buildInlineResults('sofa');
        $this->assertNotEmpty($sofaResults);
        $this->assertStringContainsString('Sofa', $sofaResults[0]['title']);
        $this->assertArrayHasKey('reply_markup', $sofaResults[0]);
    }

    public function test_telegram_mini_app_booking_creates_central_order(): void
    {
        $payload = [
            'customer_name' => 'Kidus Tesfaye',
            'customer_phone' => '0911998877',
            'subcity' => 'Bole',
            'address' => 'Rwanda Embassy Area',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time_slot' => 'Morning',
            'telegram_user_id' => '123456789',
            'items' => [
                [
                    'item_name' => 'Sofa Deep Cleaning',
                    'quantity' => 5,
                    'unit_price' => 350.00,
                ],
            ],
        ];

        $response = $this->postJson('/api/telegram/miniapp/book', $payload);

        $response->assertStatus(201);
        $bookingId = $response->json('booking_id');
        $this->assertNotNull($bookingId);

        $order = Order::where('order_number', $bookingId)->first();
        $this->assertNotNull($order);
        $this->assertEquals('telegram_miniapp', $order->source);
        $this->assertEquals(1750.00, (float)$order->total);
    }
}
