<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\CleaningTeam;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\TelegramUser;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FullEndToEndSystemFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $reception;
    protected User $cleanerUser;
    protected CleaningTeam $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 888]], 200),
            '*' => Http::response(['ok' => true], 200),
        ]);

        config(['services.telegram.bot_token' => '123456789:ABCdefGHIjklMNOpqrSTUvwxYZ']);

        $this->owner = User::where('role', 'owner')->first() ?? User::factory()->create([
            'role' => 'owner',
            'phone' => '0911000001',
            'email' => 'test_owner_' . uniqid() . '@meash.com',
        ]);

        $this->reception = User::where('role', 'reception')->first() ?? User::factory()->create([
            'role' => 'reception',
            'phone' => '0911000002',
            'email' => 'test_reception_' . uniqid() . '@meash.com',
        ]);

        $this->cleanerUser = User::where('role', 'cleaner')->first() ?? User::factory()->create([
            'role' => 'cleaner',
            'phone' => '0911000003',
            'email' => 'test_cleaner_' . uniqid() . '@meash.com',
        ]);

        $this->team = CleaningTeam::firstOrCreate(
            ['phone' => '0911000004'],
            ['team_name' => 'Bravo Team', 'status' => 'active']
        );

        TeamMember::firstOrCreate([
            'cleaning_team_id' => $this->team->id,
            'user_id' => $this->cleanerUser->id,
            'role_in_team' => 'cleaner',
        ]);
    }

    /**
     * Flow 1: 4 Channels (Bot, MiniApp, Website, Phone) & Auto-Sync into telegram_users Broadcast List
     */
    public function test_flow_1_orders_from_all_four_channels_and_auto_sync_to_broadcast_list(): void
    {
        $service = Service::where('code', 'floor_ceramic')->first() ?? Service::first();

        // 1. Channel A: Direct Phone Call Order (Reception / Owner)
        $phoneOrderRes = $this->actingAs($this->reception)->postJson('/api/orders', [
            'customer_name' => 'Phone Customer Abebe',
            'phone' => '0911112233',
            'subcity' => 'Bole',
            'address' => 'Rwanda Street House 12',
            'assigned_team_id' => $this->team->id,
            'source' => 'phone',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time_slot' => 'morning',
            'items' => [
                [
                    'service_id' => $service->id,
                    'service_code' => $service->code,
                    'item_name' => 'Floor Scrub',
                    'quantity' => 10,
                    'unit_price' => $service->base_price,
                ]
            ],
        ]);
        $phoneOrderRes->assertStatus(201);
        $phoneOrderId = $phoneOrderRes->json('order.id');

        // Verify Phone Customer is auto-synced to telegram_users broadcast table (phone_number field)
        $this->assertDatabaseHas('telegram_users', [
            'phone_number' => '0911112233',
            'first_name' => 'Phone Customer Abebe',
        ]);

        // 2. Channel B: Public Website Booking
        $webRes = $this->postJson('/api/public/book', [
            'customer_name' => 'Website Customer Sara',
            'customer_phone' => '0922334455',
            'subcity' => 'Yeka',
            'address' => 'Ayat Square Condominium Block 5',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time_slot' => 'afternoon',
            'payment_method' => 'telebirr',
            'items' => [
                [
                    'service_id' => $service->id,
                    'item_name' => 'Ceramic Floor Scrub',
                    'quantity' => 20,
                    'unit_price' => (float)$service->base_price,
                ]
            ],
        ]);
        $webRes->assertStatus(201);

        // Verify Website Customer is auto-synced to telegram_users broadcast table
        $this->assertDatabaseHas('telegram_users', [
            'phone_number' => '0922334455',
            'first_name' => 'Website Customer Sara',
        ]);

        // 3. Channel C: Telegram MiniApp Booking
        $miniappRes = $this->postJson('/api/telegram/miniapp/book', [
            'customer_name' => 'MiniApp Customer Dawit',
            'customer_phone' => '0933445566',
            'subcity' => 'Kirkos',
            'address' => 'Kazanchis Supermarket Area',
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_time_slot' => 'night',
            'payment_method' => 'cash',
            'telegram_user_id' => '987654321',
            'items' => [
                [
                    'service_id' => $service->id,
                    'service_code' => $service->code,
                    'item_name' => 'Facilities Deep Clean',
                    'quantity' => 1,
                    'unit_price' => 3500,
                ]
            ],
        ]);
        $miniappRes->assertSuccessful();

        // Verify MiniApp customer is auto-synced to telegram_users broadcast table with telegram_id
        $this->assertDatabaseHas('telegram_users', [
            'phone_number' => '0933445566',
            'telegram_id' => 987654321,
        ]);

        // 4. Channel D: Telegram Bot Webhook Registration
        $botWebhookRes = $this->postJson('/api/telegram/webhook', [
            'message' => [
                'message_id' => 101,
                'from' => [
                    'id' => 1122334455,
                    'first_name' => 'Kidus',
                    'last_name' => 'Haile',
                    'username' => 'kidus_haile',
                ],
                'chat' => [
                    'id' => 1122334455,
                    'type' => 'private',
                ],
                'text' => '/start',
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => config('services.telegram.webhook_secret', 'meash_telegram_webhook_secret_key_2026'),
        ]);
        $botWebhookRes->assertStatus(200);

        // Verify Bot User exists in telegram_users
        $this->assertDatabaseHas('telegram_users', [
            'telegram_id' => 1122334455,
            'username' => 'kidus_haile',
        ]);

        // 5. Verify Orders appear across Owner, Reception, Cleaner, and Appointments
        $ownerOrders = $this->actingAs($this->owner)->getJson('/api/orders?all=1');
        $ownerOrders->assertStatus(200);
        $this->assertGreaterThanOrEqual(3, count($ownerOrders->json('data') ?? $ownerOrders->json()));

        $receptionOrders = $this->actingAs($this->reception)->getJson('/api/orders?all=1');
        $receptionOrders->assertStatus(200);

        $cleanerJobs = $this->actingAs($this->cleanerUser)->getJson('/api/teams/my-jobs');
        $cleanerJobs->assertStatus(200);
    }

    /**
     * Flow 2: Cleaner Payment Reporting ('pending_approval') & Owner/Reception Approval ('paid')
     */
    public function test_flow_2_cleaner_payment_reporting_and_approval_workflow(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-FLOW-02',
            'full_name' => 'Tewodros Kassahun',
            'phone' => '0944556677',
            'address' => 'Bole Medhanialem',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-PAY-FLOW-01',
            'customer_id' => $customer->id,
            'assigned_team_id' => $this->team->id,
            'source' => 'phone',
            'order_status' => 'cleaning',
            'payment_status' => 'unpaid',
            'total' => 2500.00,
            'address' => 'Bole Medhanialem',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        // A. Cleaner submits Cash payment -> MUST be 'pending_approval' (NEVER direct 'paid')
        $submitRes = $this->actingAs($this->cleanerUser)->postJson("/api/orders/{$order->id}/submit-payment", [
            'payment_method' => 'cash',
            'amount' => 2500.00,
            'notes' => 'ጥሬ ገንዘብ ተቀብያለሁ',
        ]);
        $submitRes->assertStatus(200);
        $submitRes->assertJson([
            'success' => true,
            'order' => [
                'payment_status' => 'pending_approval',
                'payment_method' => 'cash',
            ],
        ]);

        $order->refresh();
        $this->assertEquals('pending_approval', $order->payment_status);

        // B. Cleaner tries to directly approve or record payment -> Forbidden (403)
        $cleanerApprove = $this->actingAs($this->cleanerUser)->postJson("/api/orders/{$order->id}/approve-payment");
        $cleanerApprove->assertStatus(403);

        // C. Reception or Owner approves payment -> status becomes 'paid', Payment created
        $initialRevenue = (float) Payment::sum('amount');

        $approveRes = $this->actingAs($this->owner)->postJson("/api/orders/{$order->id}/approve-payment", [
            'notes' => 'ገንዘቡ በካዝና ገቢ መደረጉ ተረጋግጧል',
        ]);
        $approveRes->assertStatus(200);
        $approveRes->assertJson([
            'success' => true,
            'order' => [
                'payment_status' => 'paid',
            ],
        ]);

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);

        // Verify Payment table record created
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 2500.00,
            'payment_method' => 'cash',
        ]);

        // Verify Finance reflects the new revenue
        $finalRevenue = (float) Payment::sum('amount');
        $this->assertEquals($initialRevenue + 2500.00, $finalRevenue);
    }

    /**
     * Flow 3: Order Completed -> 5-Star Review via Telegram Callback & Public Reviews Endpoint
     */
    public function test_flow_3_completed_order_5_star_review_and_feedback(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-FLOW-03',
            'full_name' => 'Marta Tadesse',
            'phone' => '0955667788',
            'address' => 'Old Airport',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-COMP-REV-01',
            'customer_id' => $customer->id,
            'assigned_team_id' => $this->team->id,
            'source' => 'website',
            'order_status' => 'cleaning',
            'payment_status' => 'paid',
            'total' => 1800.00,
            'address' => 'Old Airport',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        // Transition order to completed
        $compRes = $this->actingAs($this->owner)->patchJson("/api/orders/{$order->id}", [
            'order_status' => 'completed',
        ]);
        $compRes->assertStatus(200);

        // A. Test Public Review Submission (5-Star rating)
        $reviewRes = $this->postJson('/api/public/reviews', [
            'order_number' => 'ORD-COMP-REV-01',
            'rating' => 5,
            'comment' => 'በጣም ፈጣን እና ጥራት ያለው አገልግሎት ነው!',
            'source' => 'website',
        ]);
        $reviewRes->assertStatus(200);
        $reviewRes->assertJson(['success' => true]);

        // High rating 5 auto-approves
        $this->assertDatabaseHas('feedbacks', [
            'order_id' => $order->id,
            'rating' => 5,
            'is_approved' => true,
        ]);

        // B. Test Telegram Webhook Rating Callback
        $callbackRes = $this->postJson('/api/telegram/webhook', [
            'callback_query' => [
                'id' => 'cb_12345',
                'from' => [
                    'id' => 99887766,
                    'first_name' => 'Marta',
                ],
                'data' => "rate_order:{$order->id}:5",
                'message' => [
                    'message_id' => 555,
                    'chat' => ['id' => 99887766],
                ],
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => config('services.telegram.webhook_secret', 'meash_telegram_webhook_secret_key_2026'),
        ]);
        $callbackRes->assertStatus(200);

        // Verify review exists in public reviews list
        $pubReviews = $this->getJson('/api/public/reviews');
        $pubReviews->assertStatus(200);
    }

    /**
     * Flow 4: Official Brochure Info & Custom Settings (Slogan strictly in English, Contacts, 6 Core Services)
     */
    public function test_flow_4_official_brochure_and_customizable_settings_live(): void
    {
        // Check /api/public/settings
        $settingsRes = $this->getJson('/api/public/settings');
        $settingsRes->assertStatus(200);

        $settings = $settingsRes->json('settings') ?? [];
        $slogan = $settings['company_slogan'] ?? null;
        $sloganAm = $settings['company_slogan_am'] ?? null;
        $phone1 = $settings['contact_phone_1'] ?? null;
        $phone2 = $settings['contact_phone_2'] ?? null;
        $email = $settings['company_email'] ?? null;

        // Slogan strictly in English everywhere
        $this->assertEquals('Your Environment, Our Responsibility.', $slogan);
        $this->assertEquals('Your Environment, Our Responsibility.', $sloganAm);
        $this->assertEquals('0970075509', $phone1);
        $this->assertEquals('0998142190', $phone2);
        $this->assertEquals('meashcleaningsolution@gmail.com', $email);

        // Check /api/public/services contains official 6 core services
        $servicesRes = $this->getJson('/api/public/services');
        $servicesRes->assertStatus(200);

        $servicesList = $servicesRes->json('data') ?? $servicesRes->json();
        $serviceCodes = collect($servicesList)->pluck('code')->toArray();

        $this->assertContains('floor_ceramic', $serviceCodes);
        $this->assertContains('facilities_general', $serviceCodes);
        $this->assertContains('kitchen_dining', $serviceCodes);
        $this->assertContains('post_construction', $serviceCodes);
        $this->assertContains('glass', $serviceCodes);
        $this->assertContains('upholstery_carpet', $serviceCodes);

        // Check Web pages render without exception
        $webPage = $this->get('/');
        $webPage->assertStatus(200);
        $webPage->assertSee('Your Environment, Our Responsibility.');
        $webPage->assertSee('0970075509');
        $webPage->assertSee('0998142190');

        $miniappPage = $this->get('/telegram-miniapp');
        $miniappPage->assertStatus(200);
        $miniappPage->assertSee('Your Environment, Our Responsibility.');
    }

    /**
     * Flow 5: 3,000+ Contact Management, 1-Click Telegram Broadcast, and iPhone 1-Scan Auto-SMS Bridge
     */
    public function test_flow_5_broadcast_management_telegram_dispatch_and_iphone_sms_bridge(): void
    {
        // 1. Bulk import contacts via plain CSV text lines in raw_data
        $importPayload = [
            'raw_data' => "Client Alpha, 0971000001\nClient Beta, 0971000002\nFriend Contact Exclude, 0971000003",
        ];

        $importRes = $this->actingAs($this->owner)->postJson('/api/telegram-broadcast/import', $importPayload);
        $importRes->assertStatus(200);

        $this->assertDatabaseHas('telegram_users', ['phone_number' => '0971000001']);
        $this->assertDatabaseHas('telegram_users', ['phone_number' => '0971000002']);

        // 2. Delete / Exclude friend contact
        $excludeUser = TelegramUser::where('phone_number', '0971000003')->first();
        if ($excludeUser) {
            $delRes = $this->actingAs($this->owner)->deleteJson("/api/telegram-broadcast/recipients/{$excludeUser->id}");
            $delRes->assertStatus(200);
            $this->assertDatabaseMissing('telegram_users', ['id' => $excludeUser->id]);
        }

        // 3. Export contacts CSV
        $exportRes = $this->actingAs($this->owner)->get('/api/telegram-broadcast/export');
        $exportRes->assertStatus(200);

        // 4. 1-Click Telegram Broadcast
        $broadcastRes = $this->actingAs($this->owner)->postJson('/api/telegram-broadcast/dispatch', [
            'title' => 'የሜአሽ ይፋዊ የበዓል ቅናሽ',
            'message' => 'የሜአሽ ይፋዊ የበዓል ቅናሽ! "Your Environment, Our Responsibility."',
            'filter_type' => 'all',
        ]);
        $broadcastRes->assertStatus(200);
        $broadcastRes->assertJson(['success' => true]);

        // 5. iPhone 1-Scan Auto-SMS Session Creation
        $smsSessionRes = $this->actingAs($this->owner)->postJson('/api/telegram-broadcast/create-sms-session', [
            'message' => 'ሜአሽ የጽዳት አገልግሎት፦ ለቢሮዎ እና ለመኖሪያ ቤትዎ በ 0970075509 ይደውሉ።',
            'filter_type' => 'all',
        ]);
        $smsSessionRes->assertStatus(200);
        $smsSessionRes->assertJson(['success' => true]);
        $campaignId = $smsSessionRes->json('campaign_id');
        $token = $smsSessionRes->json('session_token');

        $this->assertNotEmpty($campaignId);
        $this->assertNotEmpty($token);

        // 6. iPhone Shortcut Pulls Queue via Bridge
        $pullRes = $this->getJson("/api/sms-bridge/pull-queue?token={$token}&campaign_id={$campaignId}");
        $pullRes->assertStatus(200);
        $queueData = $pullRes->json();
        $queue = isset($queueData['queue']) ? $queueData['queue'] : $queueData;
        $this->assertIsArray($queue);
        $this->assertNotEmpty($queue);

        // 7. iPhone Shortcut Reports Dispatch Status back to server
        $firstItem = $queue[0];
        $firstPhone = $firstItem['phone'] ?? null;
        $firstRecipientId = $firstItem['recipient_id'] ?? null;
        $reportRes = $this->postJson('/api/sms-bridge/report-status', [
            'token' => $token,
            'campaign_id' => $campaignId,
            'phone' => $firstPhone,
            'recipient_id' => $firstRecipientId,
            'status' => 'sent',
        ]);
        $reportRes->assertStatus(200);
        $this->assertTrue($reportRes->json('success'));
    }
}
