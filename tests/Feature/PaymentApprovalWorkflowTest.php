<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CleaningTeam;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $reception;
    protected User $cleanerUser;
    protected CleaningTeam $team;
    protected Customer $customer;

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

        $this->cleanerUser = User::factory()->create([
            'role' => 'cleaner',
            'phone' => '0911000003',
        ]);

        $this->team = CleaningTeam::create([
            'team_name' => 'Alpha Team',
            'phone' => '0911000004',
            'status' => 'active',
        ]);

        TeamMember::create([
            'cleaning_team_id' => $this->team->id,
            'user_id' => $this->cleanerUser->id,
            'role_in_team' => 'cleaner',
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUST-001',
            'full_name' => 'ዳዊት ሰለሞን',
            'phone' => '0911223344',
            'address' => 'ቦሌ',
        ]);
    }

    public function test_cleaner_submitting_cash_payment_sets_pending_approval_and_never_direct_paid(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-501',
            'customer_id' => $this->customer->id,
            'assigned_team_id' => $this->team->id,
            'source' => 'phone',
            'order_status' => 'cleaning',
            'payment_status' => 'unpaid',
            'total' => 1500.00,
            'address' => 'ቦሌ',
            'appointment_date' => '2026-11-06',
            'version' => 1,
        ]);

        $response = $this->actingAs($this->cleanerUser)->postJson("/api/orders/{$order->id}/submit-payment", [
            'payment_method' => 'cash',
            'amount' => 1500.00,
            'notes' => 'ከደንበኛው በእጅ ተቀብያለሁ',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'order' => [
                'payment_status' => 'pending_approval',
                'payment_method' => 'cash',
            ],
        ]);

        // Assert database values
        $order->refresh();
        $this->assertEquals('pending_approval', $order->payment_status);
        $this->assertEquals('cash', $order->payment_method);
        $this->assertNotNull($order->payment_submitted_at);
        $this->assertEquals($this->cleanerUser->id, $order->payment_submitted_by_user_id);

        // Ensure NO Payment record was created yet (Not counted in revenue yet!)
        $this->assertEquals(0, Payment::where('order_id', $order->id)->count());

        // Ensure AuditLog recorded the submission
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment_submitted_for_approval',
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
            'user_id' => $this->cleanerUser->id,
        ]);
    }

    public function test_cleaner_submitting_transfer_payment_sets_pending_approval_with_reference(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-502',
            'customer_id' => $this->customer->id,
            'assigned_team_id' => $this->team->id,
            'source' => 'website',
            'order_status' => 'cleaning',
            'payment_status' => 'unpaid',
            'total' => 2000.00,
            'address' => 'የካ',
            'appointment_date' => '2026-11-06',
            'version' => 1,
        ]);

        $response = $this->actingAs($this->cleanerUser)->postJson("/api/orders/{$order->id}/submit-payment", [
            'payment_method' => 'telebirr',
            'payment_reference' => 'TXN-TB-998877',
            'amount' => 2000.00,
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('pending_approval', $order->payment_status);
        $this->assertEquals('telebirr', $order->payment_method);
        $this->assertEquals('TXN-TB-998877', $order->payment_reference);
        $this->assertEquals(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_owner_approves_payment_creates_payment_record_and_updates_revenue(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-503',
            'customer_id' => $this->customer->id,
            'assigned_team_id' => $this->team->id,
            'source' => 'phone',
            'order_status' => 'completed',
            'payment_status' => 'pending_approval',
            'payment_method' => 'telebirr',
            'payment_reference' => 'TXN-TB-112233',
            'total' => 2500.00,
            'address' => 'ቦሌ',
            'appointment_date' => '2026-11-06',
            'version' => 1,
        ]);

        // Prior to approval, finance report shows 0 revenue
        $reportBefore = $this->actingAs($this->owner)->getJson('/api/finance/profit-report?period=today');
        $this->assertEquals(0.0, (float)$reportBefore->json('revenue'));

        // Owner approves payment
        $response = $this->actingAs($this->owner)->postJson("/api/orders/{$order->id}/approve-payment", [
            'payment_method' => 'telebirr',
            'reference_number' => 'TXN-TB-112233',
            'amount' => 2500.00,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'order' => [
                'payment_status' => 'paid',
            ],
        ]);

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertNotNull($order->payment_approved_at);
        $this->assertEquals($this->owner->id, $order->payment_approved_by_user_id);

        // Verify Payment record was created
        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(2500.00, (float)$payment->amount);
        $this->assertEquals('telebirr', $payment->payment_method);
        $this->assertEquals('TXN-TB-112233', $payment->reference_number);

        // Verify FinanceController reflects the revenue immediately
        $reportAfter = $this->actingAs($this->owner)->getJson('/api/finance/profit-report?period=today');
        $this->assertEquals(2500.00, (float)$reportAfter->json('revenue'));
    }

    public function test_reception_rejects_pending_payment(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-504',
            'customer_id' => $this->customer->id,
            'assigned_team_id' => $this->team->id,
            'source' => 'phone',
            'order_status' => 'completed',
            'payment_status' => 'pending_approval',
            'payment_method' => 'cash',
            'total' => 1200.00,
            'address' => 'ቂርቆስ',
            'appointment_date' => '2026-11-06',
            'version' => 1,
        ]);

        $response = $this->actingAs($this->reception)->postJson("/api/orders/{$order->id}/reject-payment", [
            'reason' => 'የጥሬ ገንዘብ ክፍያው በሪሴፕሽን አልተረከበም',
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('unpaid', $order->payment_status);
        $this->assertStringContainsString('የጥሬ ገንዘብ ክፍያው በሪሴፕሽን አልተረከበም', $order->notes);
        $this->assertEquals(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_cleaner_cannot_approve_payment_or_record_direct_payment(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-505',
            'customer_id' => $this->customer->id,
            'assigned_team_id' => $this->team->id,
            'source' => 'phone',
            'order_status' => 'completed',
            'payment_status' => 'pending_approval',
            'total' => 1000.00,
            'address' => 'አራዳ',
            'appointment_date' => '2026-11-06',
            'version' => 1,
        ]);

        // Cleaner cannot approve
        $resApprove = $this->actingAs($this->cleanerUser)->postJson("/api/orders/{$order->id}/approve-payment", [
            'amount' => 1000.00,
        ]);
        $resApprove->assertStatus(403);

        // Cleaner cannot record direct payment
        $resDirect = $this->actingAs($this->cleanerUser)->postJson("/api/orders/{$order->id}/payment", [
            'amount' => 1000.00,
            'payment_method' => 'cash',
        ]);
        $resDirect->assertStatus(403);
    }
}
