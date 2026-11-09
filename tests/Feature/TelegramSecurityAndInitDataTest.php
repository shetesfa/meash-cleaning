<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\TelegramController;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TelegramSecurityAndInitDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_init_data_verification_algorithm(): void
    {
        $botToken = '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11';
        $controller = new TelegramController();

        // 1. Prepare valid payload
        $userData = json_encode(['id' => 987654321, 'first_name' => 'Abebe']);
        $authDate = (string)time();
        $queryId = 'AAHdF6IQAAAAAN0XohDhrOrc';

        $params = [
            'auth_date' => $authDate,
            'query_id' => $queryId,
            'user' => $userData,
        ];
        ksort($params);

        $dataCheckArr = [];
        foreach ($params as $k => $v) {
            $dataCheckArr[] = "{$k}={$v}";
        }
        $dataCheckString = implode("\n", $dataCheckArr);

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $correctHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        $validInitData = http_build_query(array_merge($params, ['hash' => $correctHash]));

        $extractedUser = null;
        $isValid = $controller->verifyTelegramInitData($validInitData, $botToken, $extractedUser);

        $this->assertTrue($isValid);
        $this->assertNotNull($extractedUser);
        $this->assertEquals(987654321, $extractedUser['id']);

        // 2. Tampered hash should fail
        $tamperedInitData = http_build_query(array_merge($params, ['hash' => 'invalid_hash_string']));
        $this->assertFalse($controller->verifyTelegramInitData($tamperedInitData, $botToken));
    }

    public function test_set_webhook_requires_sanctum_auth_and_role(): void
    {
        // Unauthenticated must be rejected (401)
        $unauthRes = $this->postJson('/api/telegram/set-webhook');
        $unauthRes->assertStatus(401);

        // Cleaner role must be forbidden (403)
        $cleaner = User::factory()->create(['role' => 'cleaner']);
        $cleanerRes = $this->actingAs($cleaner)->postJson('/api/telegram/set-webhook');
        $cleanerRes->assertStatus(403);

        // Reception must be forbidden (403) - Telegram webhook config is strictly Owner-only
        $reception = User::factory()->create(['role' => 'reception']);
        $recRes = $this->actingAs($reception)->postJson('/api/telegram/set-webhook');
        $recRes->assertStatus(403);

        // Owner should reach controller (role authorized)
        $owner = User::factory()->create(['role' => 'owner']);
        $ownerRes = $this->actingAs($owner)->postJson('/api/telegram/set-webhook');
        $this->assertNotEquals(401, $ownerRes->status());
        $this->assertNotEquals(403, $ownerRes->status());
    }

    public function test_miniapp_booking_verifies_init_data_and_sets_trusted_user_id(): void
    {
        $botToken = 'test-bot-token';
        Config::set('services.telegram.bot_token', $botToken);

        $service = Service::create([
            'code' => 'carpet_wash',
            'name_en' => 'Carpet Washing',
            'name_am' => 'የምንጣፍ እጥበት',
            'category' => 'upholstery',
            'base_price' => 100,
            'price_unit' => 'sqm',
            'is_active' => true,
        ]);

        $userData = json_encode(['id' => 777888999, 'first_name' => 'CryptographicUser']);
        $params = [
            'auth_date' => (string)time(),
            'query_id' => 'Q12345',
            'user' => $userData,
        ];
        ksort($params);

        $dataCheckArr = [];
        foreach ($params as $k => $v) {
            $dataCheckArr[] = "{$k}={$v}";
        }
        $dataCheckString = implode("\n", $dataCheckArr);
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);
        $initData = http_build_query(array_merge($params, ['hash' => $hash]));

        // Attacker attempts to spoof telegram_user_id = 111111111
        $response = $this->postJson('/api/telegram/miniapp/book', [
            'customer_name' => 'Trusted User',
            'customer_phone' => '0911009988',
            'address' => 'Bole Brass',
            'subcity' => 'bole',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time_slot' => 'morning',
            'telegram_user_id' => '111111111', // Spoofed!
            'init_data' => $initData,          // Cryptographically authentic
            'items' => [
                [
                    'service_id' => $service->id,
                    'item_name' => 'Carpet',
                    'quantity' => 10,
                    'unit_price' => 100,
                ]
            ],
        ]);

        $response->assertStatus(201);
        $order = $response->json('order');

        // Customer's telegram_user_id must be the cryptographically verified 777888999, NOT the spoofed 111111111
        $this->assertEquals('777888999', $order['customer']['telegram_user_id']);
    }
}
