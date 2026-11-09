<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\TelegramController;
use App\Models\Order;
use App\Models\Service;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MeashTelegramTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['services.telegram.bot_token' => '123456789:ABCdefGHIjklMNOpqrSTUvwxYZ']);
    }

    private function getOrCreateSofaService(): Service
    {
        return Service::where('code', 'sofa')->first() ?: Service::create([
            'name' => 'Sofa Deep Cleaning',
            'code' => 'sofa',
            'category' => 'residential',
            'pricing_type' => 'fixed',
            'base_price' => 350.00,
            'unit' => 'seat',
            'is_active' => true,
        ]);
    }

    public static function generateValidTelegramInitData(string $botToken, int $userId = 123456789): string
    {
        $params = [
            'auth_date' => (string)time(),
            'query_id' => 'AA' . uniqid(),
            'user' => json_encode(['id' => $userId, 'first_name' => 'Kidus', 'username' => 'kidus_tg']),
        ];
        ksort($params);
        $dataCheckArr = [];
        foreach ($params as $k => $v) {
            $dataCheckArr[] = "{$k}={$v}";
        }
        $dataCheckString = implode("\n", $dataCheckArr);
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);
        $params['hash'] = $hash;

        return http_build_query($params);
    }

    public function test_telegram_inline_query_generates_rich_cards(): void
    {
        $this->getOrCreateSofaService();
        $controller = new TelegramController();

        // 1. General search
        $results = $controller->buildInlineResults('');
        $this->assertNotEmpty($results);
        $this->assertEquals('article', $results[0]['type']);
        $this->assertStringContainsStringIgnoringCase('Meash Cleaning Solution', $results[0]['title']);

        // 2. Specific search for sofa
        $sofaResults = $controller->buildInlineResults('sofa');
        $this->assertNotEmpty($sofaResults);
        $this->assertStringContainsString('Sofa', $sofaResults[0]['title']);
        $this->assertArrayHasKey('reply_markup', $sofaResults[0]);
    }

    public function test_telegram_mini_app_booking_creates_central_order(): void
    {
        $service = $this->getOrCreateSofaService();
        $botToken = config('services.telegram.bot_token') ?: '123456789:ABCdefGHIjklMNOpqrSTUvwxYZ';
        $initData = self::generateValidTelegramInitData($botToken, 123456789);

        $payload = [
            'customer_name' => 'Kidus Tesfaye',
            'customer_phone' => '0911998877',
            'subcity' => 'Bole',
            'address' => 'Rwanda Embassy Area',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time_slot' => 'Morning',
            'telegram_user_id' => '123456789',
            'init_data' => $initData,
            'items' => [
                [
                    'service_id' => $service->id,
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
        $this->assertEquals((float)$service->base_price * 5, (float)$order->total);
    }
}
