<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\TelegramUser;
use App\Models\User;
use Tests\TestCase;

class RealDataEndToEndTest extends TestCase
{
    /**
     * 1. Test Website Real Data Booking Flow
     */
    public function test_website_booking_with_real_data(): void
    {
        $sofa = Service::where('code', 'sofa')->first() ?: Service::first();

        $payload = [
            'customer_name' => 'Kidus Yohannes (Real Web Test)',
            'customer_phone' => '0911223344',
            'subcity' => 'Bole',
            'address' => 'Bole Atlas, Behind Edna Mall, Villa 42',
            'landmark' => 'Edna Mall Area',
            'latitude' => 9.0084,
            'longitude' => 38.7892,
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time_slot' => 'morning',
            'subscription_plan' => 'one_time',
            'notes' => 'Urgent living room sofa steam shampooing',
            'items' => [
                [
                    'service_id' => $sofa->id,
                    'item_name' => 'የሶፋ ጥልቅ ፅዳት (Sofa Deep Steam)',
                    'quantity' => 5,
                    'unit_price' => 350.00,
                ]
            ],
        ];

        $response = $this->postJson('/api/public/book', $payload);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));
        $orderNumber = $response->json('order_number') ?: $response->json('booking_id');
        $this->assertNotEmpty($orderNumber);

        // Verify order exists in Database
        $order = Order::where('order_number', $orderNumber)->first();
        $this->assertNotNull($order, 'Order must exist in database');
        $this->assertEquals('website', $order->source);
        $this->assertEquals('0911223344', $order->customer->phone);
        $this->assertEquals(1750.00, (float)$order->total);
        $this->assertEquals('Bole', $order->subcity);
    }

    /**
     * 2. Test Telegram Mini App Real Data Booking Flow
     */
    public function test_telegram_mini_app_booking_with_real_data(): void
    {
        $sofa = Service::where('code', 'sofa')->first() ?: Service::first();

        $payload = [
            'customer_name' => 'Sara Bekele (Real MiniApp Test)',
            'customer_phone' => '0922334455',
            'subcity' => 'Yeka',
            'address' => 'Megenagna, Behind Zefmesh Mall',
            'latitude' => 9.0221,
            'longitude' => 38.7981,
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_time_slot' => 'afternoon',
            'telegram_user_id' => '987654321',
            'items' => [
                [
                    'service_id' => $sofa->id,
                    'item_name' => 'የምንጣፍ እጥበት (Carpet Wash)',
                    'quantity' => 12,
                    'unit_price' => 100.00,
                ],
            ],
        ];

        $response = $this->postJson('/api/telegram/miniapp/book', $payload);

        $response->assertStatus(201);
        $bookingId = $response->json('booking_id');
        $this->assertNotEmpty($bookingId);

        // Verify order exists in Database
        $order = Order::where('order_number', $bookingId)->first();
        $this->assertNotNull($order, 'MiniApp Order must exist in database');
        $this->assertEquals('telegram_miniapp', $order->source);
        $this->assertEquals('0922334455', $order->customer->phone);
        $this->assertEquals(1200.00, (float)$order->total);
        $this->assertEquals('afternoon', $order->appointment_time_slot);
    }

    /**
     * 3. Test Telegram Bot Webhook Booking Flow
     */
    public function test_telegram_bot_webhook_booking_flow(): void
    {
        $chatId = 5566778899;

        // Step 1: Send /start
        $startUpdate = [
            'update_id' => 10001,
            'message' => [
                'message_id' => 1,
                'from' => ['id' => $chatId, 'first_name' => 'Abebe', 'username' => 'abebe_real'],
                'chat' => ['id' => $chatId, 'type' => 'private'],
                'text' => '/start',
            ]
        ];
        $resStart = $this->postJson('/api/telegram/webhook', $startUpdate);
        $resStart->assertStatus(200);

        // Step 2: Book via mini-app data handler webhook
        $webAppDataUpdate = [
            'update_id' => 10002,
            'message' => [
                'message_id' => 2,
                'from' => ['id' => $chatId, 'first_name' => 'Abebe', 'username' => 'abebe_real'],
                'chat' => ['id' => $chatId, 'type' => 'private'],
                'web_app_data' => [
                    'data' => json_encode([
                        'customer_name' => 'Abebe Bikila (Real Bot Test)',
                        'customer_phone' => '0933445566',
                        'subcity' => 'Kirkos',
                        'address' => 'Meskel Flower, House 102',
                        'latitude' => 9.0012,
                        'longitude' => 38.7612,
                        'appointment_date' => now()->addDays(1)->toDateString(),
                        'appointment_time_slot' => 'ጠዋት (ከ 3:00 - 6:00)',
                        'notes' => 'መኝታ ቤት እና ሳሎን ጥልቅ እጥበት',
                        'items' => [
                            [
                                'item_name' => 'የሶፋ ጥልቅ እጥበት',
                                'quantity' => 2,
                                'unit_price' => 350.00
                            ]
                        ]
                    ])
                ]
            ]
        ];
        $resBooking = $this->postJson('/api/telegram/webhook', $webAppDataUpdate);
        $resBooking->assertStatus(200);

        // Verify order created in Database from Bot
        $order = Order::where('source', 'telegram_miniapp')
            ->whereHas('customer', function($q) {
                $q->where('phone', '0933445566');
            })
            ->latest()
            ->first();

        $this->assertNotNull($order, 'Bot order must be created in database');
        $this->assertStringContainsString('MEASH-', $order->order_number);
        $this->assertEquals('Abebe Bikila (Real Bot Test)', $order->customer->full_name);
        $this->assertEquals('Kirkos', $order->subcity);
    }

    /**
     * 4. Verify Immediate Visibility in Admin and Reception
     */
    public function test_orders_immediately_visible_to_admin_and_reception(): void
    {
        $owner = User::where('role', 'owner')->first() ?: User::factory()->create(['role' => 'owner', 'email' => 'owner_test@meash.com']);
        $reception = User::where('role', 'reception')->first() ?: User::factory()->create(['role' => 'reception', 'email' => 'reception_test@meash.com']);

        // Check Owner can view latest orders including web, miniapp, and bot
        $ownerRes = $this->actingAs($owner)->getJson('/api/orders');
        $ownerRes->assertStatus(200);
        $ordersList = $ownerRes->json('data') ?: $ownerRes->json();
        $this->assertNotEmpty($ordersList, 'Admin must see orders');

        // Check Reception can view orders
        $receptionRes = $this->actingAs($reception)->getJson('/api/orders');
        $receptionRes->assertStatus(200);
        $receptionOrders = $receptionRes->json('data') ?: $receptionRes->json();
        $this->assertNotEmpty($receptionOrders, 'Receptionist must see orders');
    }

    /**
     * 5. Test Telegram Bot Interactive Step-by-Step Chat Wizard Booking Flow
     */
    public function test_telegram_bot_interactive_chat_wizard_booking_flow(): void
    {
        $wizardChatId = 7788990011;

        // Ensure user exists and has payload cache configured for confirmation step
        $tgUser = TelegramUser::updateOrCreate(
            ['telegram_id' => $wizardChatId],
            [
                'first_name' => 'Tewodros',
                'username' => 'tedros_real',
                'bot_state' => 'booking_confirm',
                'payload_cache' => [
                    'service_code' => 'sofa',
                    'service_name' => 'የሶፋ ጥልቅ እጥበት',
                    'quantity' => 4,
                    'unit_price' => 350.00,
                    'customer_name' => 'Tewodros Kassahun (Wizard Test)',
                    'customer_phone' => '0944556677',
                    'address' => 'Bole Medhanialem, Near Morning Star Mall',
                    'subcity' => 'Bole',
                    'appointment_date' => now()->addDays(2)->toDateString(),
                    'appointment_time_slot' => 'morning',
                ]
            ]
        );

        // Send callback query for book_confirm
        $callbackUpdate = [
            'update_id' => 20001,
            'callback_query' => [
                'id' => 'cb_confirm_123',
                'from' => ['id' => $wizardChatId, 'first_name' => 'Tewodros', 'username' => 'tedros_real'],
                'message' => [
                    'message_id' => 88,
                    'chat' => ['id' => $wizardChatId, 'type' => 'private'],
                ],
                'data' => 'book_confirm',
            ]
        ];

        $resCallback = $this->postJson('/api/telegram/webhook', $callbackUpdate);
        $resCallback->assertStatus(200);

        // Verify order created in Database with source telegram_bot
        $order = Order::where('source', 'telegram_bot')
            ->whereHas('customer', function($q) {
                $q->where('phone', '0944556677');
            })
            ->latest()
            ->first();

        $this->assertNotNull($order, 'Interactive Bot wizard order must be created in database');
        $this->assertStringContainsString('MEASH-', $order->order_number);
        $this->assertEquals('Tewodros Kassahun (Wizard Test)', $order->customer->full_name);
        $this->assertEquals(1400.00, (float)$order->total);
        $this->assertEquals('Bole', $order->subcity);
    }
}

