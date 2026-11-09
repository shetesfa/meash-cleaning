<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\TelegramUser;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramOptimizationAndChapaRemovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['services.telegram.bot_token' => '123456789:ABCdefGHIjklMNOpqrSTUvwxYZ']);
    }

    public function test_chapa_is_rejected_in_order_payment_and_website_booking(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $service = Service::create([
            'code' => 'sofa_cleaning',
            'name_en' => 'Sofa Cleaning',
            'name_am' => 'የሶፋ እጥበት',
            'category' => 'upholstery',
            'base_price' => 500,
            'price_unit' => 'seat',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_code' => Customer::generateNextCode(),
            'full_name' => 'Chapa Test User',
            'phone' => '0911223344',
            'address' => 'Bole Medhanialem',
            'subcity' => 'bole',
            'customer_type' => 'individual',
        ]);

        $order = Order::create([
            'order_number' => Order::generateNextNumber(),
            'customer_id' => $customer->id,
            'source' => 'website',
            'status' => 'confirmed',
            'address' => 'Bole Medhanialem',
            'subcity' => 'bole',
            'appointment_date' => now()->addDay(),
            'appointment_time_slot' => 'morning',
            'total' => 500,
            'payment_status' => 'unpaid',
        ]);

        // 1. Order payment record should reject chapa
        $payRes = $this->actingAs($admin)->postJson("/api/orders/{$order->id}/payment", [
            'amount' => 500,
            'payment_method' => 'chapa',
        ]);
        $payRes->assertStatus(422);
        $payRes->assertJsonValidationErrors(['payment_method']);

        // 2. Website booking should reject chapa
        $bookRes = $this->postJson('/api/public/book', [
            'customer_name' => 'Chapa Tester',
            'customer_phone' => '0911223355',
            'address' => 'Gerji',
            'subcity' => 'bole',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time_slot' => 'morning',
            'payment_method' => 'chapa',
            'items' => [
                [
                    'service_id' => $service->id,
                    'item_name' => 'Sofa',
                    'quantity' => 1,
                    'unit_price' => 500,
                ]
            ],
        ]);
        $bookRes->assertStatus(422);
        $bookRes->assertJsonValidationErrors(['payment_method']);
    }

    public function test_telegram_webhook_deduplication_via_cache(): void
    {
        $updateId = 88776655;

        // First call should process and cache update_id
        $res1 = $this->postJson('/api/telegram/webhook', [
            'update_id' => $updateId,
            'message' => [
                'message_id' => 101,
                'from' => [
                    'id' => 12345678,
                    'is_bot' => false,
                    'first_name' => 'Dedupe',
                ],
                'chat' => [
                    'id' => 12345678,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/start',
            ],
        ]);

        $res1->assertStatus(200);
        $this->assertTrue(Cache::has("tg_update_{$updateId}"));

        // Second call with same update_id should immediately return ok:true without reprocessing
        $res2 = $this->postJson('/api/telegram/webhook', [
            'update_id' => $updateId,
            'message' => [
                'message_id' => 101,
                'text' => '/start',
            ],
        ]);

        $res2->assertStatus(200);
        $res2->assertJson(['ok' => true]);
    }

    public function test_sms_service_handles_gateway_outage_safely_without_throwing(): void
    {
        Http::fake([
            'https://api.afromessage.com/*' => Http::response(null, 500),
            '*' => Http::response(null, 500),
        ]);

        $customer = Customer::create([
            'customer_code' => Customer::generateNextCode(),
            'full_name' => 'Sms Outage User',
            'phone' => '0911998877',
            'address' => 'Kazanchis',
            'subcity' => 'kirkos',
            'customer_type' => 'individual',
        ]);

        $order = Order::create([
            'order_number' => Order::generateNextNumber(),
            'customer_id' => $customer->id,
            'source' => 'telegram_miniapp',
            'status' => 'confirmed',
            'address' => 'Kazanchis',
            'subcity' => 'kirkos',
            'total' => 600,
            'appointment_date' => now()->addDay(),
            'appointment_time_slot' => 'morning',
        ]);

        // Must not throw an unhandled exception
        $result = SmsService::sendBookingConfirmation($order);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('ok', $result);
    }

    public function test_telegram_broadcast_chunks_and_dispatches_cleanly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create 5 telegram users
        for ($i = 1; $i <= 5; $i++) {
            TelegramUser::create([
                'telegram_id' => 900000000 + $i,
                'first_name' => "User {$i}",
                'marketing_consent' => true,
            ]);
        }

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $res = $this->actingAs($admin)->postJson('/api/telegram-broadcast/dispatch', [
            'title' => 'የወቅቱ ልዩ ቅናሽ',
            'message' => 'በዚህ ሳምንት 20% ቅናሽ አለን!',
        ]);

        $res->assertStatus(200);
        $res->assertJson([
            'success' => true,
            'status' => 'processing',
            'total_targets' => 5,
        ]);
    }
}
