<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerReviewAndRatingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 999]], 200),
            '*' => Http::response(['ok' => true], 200),
        ]);
    }

    private function getOrCreateCustomer(): Customer
    {
        return Customer::first() ?: Customer::create([
            'name' => 'Test Customer',
            'phone' => '0911223344',
            'address' => 'Bole, Addis Ababa',
        ]);
    }

    private function getOrCreateOwner(): User
    {
        return User::where('role', 'owner')->first() ?: User::create([
            'name' => 'Test Owner',
            'username' => 'test_owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);
    }

    public function test_public_website_review_submission_with_high_rating_auto_approves(): void
    {
        $customer = $this->getOrCreateCustomer();
        $order = Order::create([
            'order_number' => 'ORD-REV-001',
            'customer_id' => $customer->id,
            'source' => 'website',
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total' => 1200,
            'address' => 'Bole, Addis Ababa',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        $res = $this->postJson('/api/public/reviews', [
            'order_number' => 'ORD-REV-001',
            'phone' => $customer->phone,
            'rating' => 5,
            'comment' => 'አገልግሎቱ በጣም ምርጥ ነበር፣ እናመሰግናለን!',
        ]);

        $res->assertStatus(200);
        $res->assertJson(['success' => true]);

        $this->assertDatabaseHas('feedbacks', [
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'rating' => 5,
            'comment' => 'አገልግሎቱ በጣም ምርጥ ነበር፣ እናመሰግናለን!',
            'is_approved' => true,
        ]);

        // Verify it appears in public reviews endpoint
        $publicRes = $this->getJson('/api/public/reviews');
        $publicRes->assertStatus(200);
        $publicRes->assertJsonFragment([
            'comment' => 'አገልግሎቱ በጣም ምርጥ ነበር፣ እናመሰግናለን!',
        ]);
    }

    public function test_public_website_review_submission_with_low_rating_stays_unapproved(): void
    {
        $customer = $this->getOrCreateCustomer();
        $order = Order::create([
            'order_number' => 'ORD-REV-002',
            'customer_id' => $customer->id,
            'source' => 'website',
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total' => 800,
            'address' => 'CMC, Addis Ababa',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        // Batch 11a (F5): the endpoint requires the order's customer phone.
        $res = $this->postJson('/api/public/reviews', [
            'order_number' => 'ORD-REV-002',
            'rating' => 2,
            'phone' => $customer->phone,
            'comment' => 'ትንሽ ዘግይተው ነበር የመጡት',
        ]);

        $res->assertStatus(200);
        $res->assertJson(['success' => true]);

        $this->assertDatabaseHas('feedbacks', [
            'order_id' => $order->id,
            'rating' => 2,
            'is_approved' => false,
        ]);
    }

    /**
     * Batch 18: locks in the batch 11a (F5) rule — a review WITHOUT the
     * order's customer phone is rejected, so nobody can post a review
     * in a stranger's name by guessing a sequential order number.
     */
    public function test_public_website_review_without_phone_is_rejected(): void
    {
        $customer = $this->getOrCreateCustomer();
        $order = Order::create([
            'order_number' => 'ORD-REV-003',
            'customer_id' => $customer->id,
            'source' => 'website',
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total' => 800,
            'address' => 'CMC, Addis Ababa',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        $res = $this->postJson('/api/public/reviews', [
            'order_number' => 'ORD-REV-003',
            'rating' => 5,
            'comment' => 'ያለ ስልክ የተላከ ግምገማ',
        ]);

        $res->assertStatus(422);
        $this->assertDatabaseMissing('feedbacks', [
            'order_id' => $order->id,
        ]);
    }

    public function test_telegram_webhook_rate_order_callback_creates_feedback(): void
    {
        $customer = $this->getOrCreateCustomer();
        $order = Order::create([
            'order_number' => 'ORD-TG-REV-001',
            'customer_id' => $customer->id,
            'source' => 'telegram_bot',
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total' => 1500,
            'address' => 'Megenagna, Addis Ababa',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        $chatId = 123456789;

        $update = [
            'update_id' => 9001,
            'callback_query' => [
                'id' => 'cb_query_1',
                'from' => [
                    'id' => $chatId,
                    'first_name' => 'Abebe',
                ],
                'message' => [
                    'message_id' => 456,
                    'chat' => [
                        'id' => $chatId,
                        'first_name' => 'Abebe',
                    ],
                ],
                'data' => "rate_order:{$order->id}:5",
            ],
        ];

        $res = $this->postJson('/api/telegram/webhook', $update);
        $res->assertStatus(200);

        $this->assertDatabaseHas('feedbacks', [
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'rating' => 5,
            'source' => 'telegram',
            'is_approved' => true,
        ]);

        // Awaiting text comment cache must be active
        $this->assertTrue(Cache::has("tg_awaiting_feedback_{$chatId}"));

        // Now test sending text comment
        $messageUpdate = [
            'update_id' => 9002,
            'message' => [
                'message_id' => 457,
                'chat' => [
                    'id' => $chatId,
                    'first_name' => 'Abebe',
                ],
                'from' => [
                    'id' => $chatId,
                    'first_name' => 'Abebe',
                ],
                'text' => 'ስራቸው እጅግ አስደናቂ እና ጥራት ያለው ነው!',
            ],
        ];

        $msgRes = $this->postJson('/api/telegram/webhook', $messageUpdate);
        $msgRes->assertStatus(200);

        $this->assertDatabaseHas('feedbacks', [
            'order_id' => $order->id,
            'comment' => 'ስራቸው እጅግ አስደናቂ እና ጥራት ያለው ነው!',
        ]);

        $this->assertFalse(Cache::has("tg_awaiting_feedback_{$chatId}"));
    }

    public function test_manual_send_review_request_from_reception_or_owner(): void
    {
        $owner = $this->getOrCreateOwner();
        $customer = $this->getOrCreateCustomer();
        $customer->telegram_user_id = '987654321';
        $customer->save();

        $order = Order::create([
            'order_number' => 'ORD-MAN-REV-001',
            'customer_id' => $customer->id,
            'source' => 'telegram_bot',
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total' => 900,
            'address' => 'Ayat, Addis Ababa',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        $res = $this->actingAs($owner, 'sanctum')->postJson("/api/orders/{$order->id}/send-review-request");
        $res->assertStatus(200);
        $res->assertJson([
            'success' => true,
            'channel' => 'telegram',
        ]);
    }

    public function test_order_completion_triggers_review_notification(): void
    {
        $owner = $this->getOrCreateOwner();
        $customer = $this->getOrCreateCustomer();
        $customer->phone = '0911554433';
        $customer->save();

        $order = Order::create([
            'order_number' => 'ORD-AUTO-REV-001',
            'customer_id' => $customer->id,
            'source' => 'phone',
            'order_status' => 'assigned',
            'payment_status' => 'unpaid',
            'total' => 1000,
            'address' => 'Kazanchis, Addis Ababa',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        $res = $this->actingAs($owner, 'sanctum')->postJson("/api/orders/{$order->id}/status", [
            'order_status' => 'completed',
        ]);

        $res->assertStatus(200);
        $order->refresh();
        $this->assertEquals('completed', $order->order_status);
        $this->assertNotNull($order->completed_at);
    }
}
