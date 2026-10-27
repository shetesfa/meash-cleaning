<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    public function test_telegram_webhook_returns_401_when_secret_is_missing(): void
    {
        Config::set('services.telegram.webhook_secret', 'expected-super-secret');

        $response = $this->postJson('/api/telegram/webhook', [
            'update_id' => 12345,
        ]);

        $response->assertStatus(401);
    }

    public function test_telegram_webhook_returns_401_when_secret_is_wrong(): void
    {
        Config::set('services.telegram.webhook_secret', 'expected-super-secret');

        $response = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret-token',
        ])->postJson('/api/telegram/webhook', [
            'update_id' => 12345,
        ]);

        $response->assertStatus(401);
    }
}
