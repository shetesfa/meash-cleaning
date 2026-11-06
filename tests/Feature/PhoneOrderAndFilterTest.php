<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\CleaningTeam;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneOrderAndFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $reception;
    protected CleaningTeam $team;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'phone' => '0911000001',
        ]);

        $this->reception = User::factory()->create([
            'role' => 'reception',
            'phone' => '0911000002',
        ]);

        $this->team = CleaningTeam::create([
            'team_name' => 'Team Alpha',
            'phone' => '0911000003',
            'status' => 'active',
        ]);

        $this->service = Service::create([
            'name_en' => 'Deep Sofa Cleaning',
            'name_am' => 'የሶፋ ጥልቅ ፅዳት',
            'code' => 'sofa_deep',
            'base_price' => 350.00,
            'unit' => 'ወንበር',
            'is_active' => true,
        ]);
    }

    public function test_phone_order_creation_auto_creates_customer_and_links_appointment(): void
    {
        $response = $this->actingAs($this->reception)->postJson('/api/orders', [
            'customer_name' => 'አበበ ከበደ',
            'customer_phone' => '0912345678',
            'address' => 'ቦሌ መድሃኒዓለም ህንፃ 3',
            'subcity' => 'Bole',
            'source' => 'phone',
            'appointment_date' => '2026-11-15',
            'appointment_time_slot' => 'morning',
            'team_id' => $this->team->id,
            'items' => [
                [
                    'service_id' => $this->service->id,
                    'quantity' => 5,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $orderData = $response->json('order');

        $this->assertNotEmpty($orderData['order_number']);
        $this->assertEquals('assigned', $orderData['order_status']);
        $this->assertEquals($this->team->id, $orderData['assigned_team_id']);

        // Verify customer was created
        $customer = Customer::where('phone', '0912345678')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('አበበ ከበደ', $customer->full_name);
        $this->assertEquals('አበበ ከበደ', $customer->name);

        // Verify appointment was created
        $appointment = Appointment::where('order_id', $orderData['id'])->first();
        $this->assertNotNull($appointment);
        $this->assertEquals($this->team->id, $appointment->cleaning_team_id);
        $this->assertEquals('scheduled', $appointment->status);
    }

    public function test_phone_order_reuses_existing_customer_by_phone(): void
    {
        $existingCustomer = Customer::create([
            'customer_code' => 'CUST-0001',
            'full_name' => 'ሙሉነሽ በቀለ',
            'phone' => '0922334455',
            'address' => 'የካ ኮተቤ',
            'subcity' => 'Yeka',
        ]);

        $response = $this->actingAs($this->owner)->postJson('/api/orders', [
            'customer_name' => 'ሙሉነሽ በቀለ',
            'customer_phone' => '0922334455',
            'address' => 'የካ ኮተቤ አዲስ ቤት',
            'source' => 'phone',
            'appointment_date' => '2026-11-20',
            'appointment_time_slot' => 'afternoon',
            'items' => [
                [
                    'service_id' => $this->service->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertEquals($existingCustomer->id, $response->json('order.customer_id'));
        $this->assertEquals(1, Customer::where('phone', '0922334455')->count());
    }

    public function test_order_index_filters_by_team_and_status_aliases(): void
    {
        $cust1 = Customer::create([
            'customer_code' => 'CUST-0101',
            'full_name' => 'ደንበኛ አንድ',
            'phone' => '0933112233',
            'address' => 'Bole',
        ]);

        $cust2 = Customer::create([
            'customer_code' => 'CUST-0102',
            'full_name' => 'ደንበኛ ሁለት',
            'phone' => '0933112244',
            'address' => 'Yeka',
        ]);

        $order1 = Order::create([
            'order_number' => 'ORD-101',
            'customer_id' => $cust1->id,
            'assigned_team_id' => $this->team->id,
            'source' => 'website',
            'order_status' => 'assigned',
            'payment_status' => 'unpaid',
            'address' => 'Bole',
            'appointment_date' => '2026-11-10',
            'version' => 1,
        ]);

        $order2 = Order::create([
            'order_number' => 'ORD-102',
            'customer_id' => $cust2->id,
            'assigned_team_id' => null,
            'source' => 'telegram_miniapp',
            'order_status' => 'new',
            'payment_status' => 'unpaid',
            'address' => 'Yeka',
            'appointment_date' => '2026-11-10',
            'version' => 1,
        ]);

        // Filter using team_id alias
        $resTeam = $this->actingAs($this->owner)->getJson("/api/orders?team_id={$this->team->id}");
        $resTeam->assertStatus(200);
        $dataTeam = $resTeam->json('data');
        $this->assertCount(1, $dataTeam);
        $this->assertEquals('ORD-101', $dataTeam[0]['order_number']);

        // Filter using order_status alias
        $resStatus = $this->actingAs($this->owner)->getJson("/api/orders?order_status=new");
        $resStatus->assertStatus(200);
        $dataStatus = $resStatus->json('data');
        $this->assertCount(1, $dataStatus);
        $this->assertEquals('ORD-102', $dataStatus[0]['order_number']);

        // Verify JSON accessors (customer_name, customer_phone, status, team_id)
        $this->assertArrayHasKey('customer_name', $dataTeam[0]);
        $this->assertArrayHasKey('status', $dataTeam[0]);
        $this->assertArrayHasKey('team_id', $dataTeam[0]);
        $this->assertEquals('assigned', $dataTeam[0]['status']);
        $this->assertEquals($this->team->id, $dataTeam[0]['team_id']);
    }

    public function test_order_index_supports_large_pagination_and_all_flag(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/orders?all=1');
        $response->assertStatus(200);
        $this->assertEquals(200, $response->json('per_page'));

        $responseCustom = $this->actingAs($this->owner)->getJson('/api/orders?per_page=150');
        $responseCustom->assertStatus(200);
        $this->assertEquals(150, $responseCustom->json('per_page'));
    }

    public function test_order_update_accepts_team_id_and_status(): void
    {
        $cust = Customer::create([
            'customer_code' => 'CUST-0201',
            'full_name' => 'ደንበኛ ሶስት',
            'phone' => '0933112255',
            'address' => 'Bole',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-201',
            'customer_id' => $cust->id,
            'source' => 'phone',
            'order_status' => 'new',
            'payment_status' => 'unpaid',
            'address' => 'Bole',
            'appointment_date' => '2026-11-12',
            'version' => 1,
        ]);

        $response = $this->actingAs($this->reception)->putJson("/api/orders/{$order->id}", [
            'team_id' => $this->team->id,
            'status' => 'assigned',
        ]);

        $response->assertStatus(200);
        $this->assertEquals($this->team->id, $response->json('order.assigned_team_id'));
        $this->assertEquals('assigned', $response->json('order.order_status'));
    }
}
