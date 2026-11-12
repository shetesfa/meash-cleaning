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

    /**
     * Scenario A: 5,000 ETB order, 2,000 ETB approved payment = partially_paid.
     */
    public function test_scenario_a_partial_payment_marks_order_as_partially_paid(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-SCENARIO-A',
            'customer_id' => $this->customer->id,
            'source' => 'phone',
            'order_status' => 'cleaning',
            'payment_status' => 'unpaid',
            'subtotal' => 5000.00,
            'total' => 5000.00,
            'address' => 'ቦሌ',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        $res = $this->actingAs($this->owner)->postJson("/api/orders/{$order->id}/approve-payment", [
            'amount' => 2000.00,
            'payment_method' => 'telebirr',
            'reference_number' => 'TB-PARTIAL-2000',
        ]);

        $res->assertStatus(200);
        $order->refresh();

        $this->assertEquals('partially_paid', $order->payment_status);
        $this->assertEquals(2000.00, (float)Payment::where('order_id', $order->id)->sum('amount'));
    }

    /**
     * Scenario B: 5,000 ETB order, 2,000 ETB + 3,000 ETB approved payments = paid.
     */
    public function test_scenario_b_multiple_payments_reaching_total_marks_order_as_paid(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-SCENARIO-B',
            'customer_id' => $this->customer->id,
            'source' => 'phone',
            'order_status' => 'cleaning',
            'payment_status' => 'unpaid',
            'subtotal' => 5000.00,
            'total' => 5000.00,
            'address' => 'ቦሌ',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        // Payment 1: 2,000 ETB
        $this->actingAs($this->owner)->postJson("/api/orders/{$order->id}/approve-payment", [
            'amount' => 2000.00,
            'payment_method' => 'telebirr',
            'reference_number' => 'TB-PAY-1',
        ])->assertStatus(200);

        $order->refresh();
        $this->assertEquals('partially_paid', $order->payment_status);

        // Payment 2: 3,000 ETB
        $this->actingAs($this->owner)->postJson("/api/orders/{$order->id}/approve-payment", [
            'amount' => 3000.00,
            'payment_method' => 'cbe',
            'reference_number' => 'CBE-PAY-2',
        ])->assertStatus(200);

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals(5000.00, (float)Payment::where('order_id', $order->id)->sum('amount'));
    }

    /**
     * Scenario C: Rejected payment = no revenue and does not erase prior approved payment.
     */
    public function test_scenario_c_rejected_payment_does_not_create_revenue(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-SCENARIO-C',
            'customer_id' => $this->customer->id,
            'source' => 'phone',
            'order_status' => 'completed',
            'payment_status' => 'unpaid',
            'subtotal' => 4000.00,
            'total' => 4000.00,
            'address' => 'ቦሌ',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        // Cleaner submits payment
        $this->actingAs($this->cleanerUser)->postJson("/api/orders/{$order->id}/submit-payment", [
            'amount' => 4000.00,
            'payment_method' => 'cash',
        ])->assertStatus(200);

        // Owner rejects payment
        $this->actingAs($this->owner)->postJson("/api/orders/{$order->id}/reject-payment", [
            'reason' => 'ጥሬ ገንዘብ አልተረከብኩም',
        ])->assertStatus(200);

        $order->refresh();
        $this->assertEquals('unpaid', $order->payment_status);
        $this->assertEquals(0, Payment::where('order_id', $order->id)->count());

        $report = $this->actingAs($this->owner)->getJson('/api/finance/profit-report?period=all_time');
        $this->assertEquals(0.0, (float)$report->json('revenue'));
    }

    /**
     * Scenario D: Pending payment = no revenue.
     */
    public function test_scenario_d_pending_payment_does_not_count_as_revenue(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-SCENARIO-D',
            'customer_id' => $this->customer->id,
            'source' => 'phone',
            'order_status' => 'completed',
            'payment_status' => 'unpaid',
            'subtotal' => 3000.00,
            'total' => 3000.00,
            'address' => 'ቦሌ',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        $this->actingAs($this->cleanerUser)->postJson("/api/orders/{$order->id}/submit-payment", [
            'amount' => 3000.00,
            'payment_method' => 'telebirr',
            'payment_reference' => 'TB-PENDING-REF',
        ])->assertStatus(200);

        $order->refresh();
        $this->assertEquals('pending_approval', $order->payment_status);

        $report = $this->actingAs($this->owner)->getJson('/api/finance/profit-report?period=all_time');
        $this->assertEquals(0.0, (float)$report->json('revenue'));
    }

    /**
     * Scenario E: Duplicate approval = no duplicate financial record.
     */
    public function test_scenario_e_duplicate_approval_does_not_create_duplicate_payment(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-SCENARIO-E',
            'customer_id' => $this->customer->id,
            'source' => 'phone',
            'order_status' => 'completed',
            'payment_status' => 'pending_approval',
            'subtotal' => 2500.00,
            'total' => 2500.00,
            'address' => 'ቦሌ',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        // First approval
        $res1 = $this->actingAs($this->owner)->postJson("/api/orders/{$order->id}/approve-payment", [
            'amount' => 2500.00,
            'payment_method' => 'telebirr',
            'reference_number' => 'TB-UNIQUE-001',
        ]);
        $res1->assertStatus(200);

        $this->assertEquals(1, Payment::where('order_id', $order->id)->count());

        // Repeated duplicate approval call with same reference
        $res2 = $this->actingAs($this->owner)->postJson("/api/orders/{$order->id}/approve-payment", [
            'amount' => 2500.00,
            'payment_method' => 'telebirr',
            'reference_number' => 'TB-UNIQUE-001',
        ]);
        $res2->assertStatus(200);

        // Strictly 1 payment record exists, not 2
        $this->assertEquals(1, Payment::where('order_id', $order->id)->count());
        $this->assertEquals(2500.00, (float)Payment::where('order_id', $order->id)->sum('amount'));
    }

    /**
     * Scenario F: Consecutive approvals = correct final balance.
     */
    public function test_scenario_f_consecutive_approvals_calculate_correct_final_balance(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-SCENARIO-F',
            'customer_id' => $this->customer->id,
            'source' => 'phone',
            'order_status' => 'cleaning',
            'payment_status' => 'unpaid',
            'subtotal' => 6000.00,
            'total' => 6000.00,
            'address' => 'ቦሌ',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        $this->actingAs($this->owner)->postJson("/api/orders/{$order->id}/approve-payment", [
            'amount' => 2000.00,
            'payment_method' => 'telebirr',
        ])->assertStatus(200);

        $this->actingAs($this->owner)->postJson("/api/orders/{$order->id}/approve-payment", [
            'amount' => 2000.00,
            'payment_method' => 'cbe',
        ])->assertStatus(200);

        $this->actingAs($this->owner)->postJson("/api/orders/{$order->id}/approve-payment", [
            'amount' => 2000.00,
            'payment_method' => 'cash',
        ])->assertStatus(200);

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals(6000.00, (float)Payment::where('order_id', $order->id)->sum('amount'));
        $this->assertEquals(3, Payment::where('order_id', $order->id)->count());
    }

    /**
     * Scenario G: Unauthorized user cannot approve payments (403).
     */
    public function test_scenario_g_sales_and_cleaners_cannot_approve_payments(): void
    {
        $salesUser = User::factory()->create(['role' => 'sales']);
        $order = Order::create([
            'order_number' => 'ORD-SCENARIO-G',
            'customer_id' => $this->customer->id,
            'source' => 'phone',
            'order_status' => 'completed',
            'payment_status' => 'pending_approval',
            'subtotal' => 1000.00,
            'total' => 1000.00,
            'address' => 'ቦሌ',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        // Sales receives 403
        $this->actingAs($salesUser)->postJson("/api/orders/{$order->id}/approve-payment", [
            'amount' => 1000.00,
        ])->assertStatus(403);

        // Cleaner receives 403
        $this->actingAs($this->cleanerUser)->postJson("/api/orders/{$order->id}/approve-payment", [
            'amount' => 1000.00,
        ])->assertStatus(403);
    }

    /**
     * Refund Scenario: Order refund records expense, adjusts net paid, and adjusts payment status.
     */
    public function test_order_refund_records_expense_and_adjusts_status(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-REFUND-001',
            'customer_id' => $this->customer->id,
            'source' => 'phone',
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'subtotal' => 4000.00,
            'total' => 4000.00,
            'address' => 'ቦሌ',
            'appointment_date' => now()->toDateString(),
            'version' => 1,
        ]);

        Payment::create([
            'payment_number' => 'PAY-REFUND-001',
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'amount' => 4000.00,
            'payment_method' => 'telebirr',
            'payment_date' => now(),
            'recorded_by_user_id' => $this->owner->id,
        ]);

        // Process full refund
        $response = $this->actingAs($this->owner)->postJson("/api/orders/{$order->id}/refund", [
            'amount' => 4000.00,
            'reason' => 'ደንበኛው አገልግሎቱ ስላልተመቸው ተመላሽ ተደርጓል',
            'payment_method' => 'telebirr',
        ]);

        $response->assertStatus(201);
        $order->refresh();

        $this->assertEquals('unpaid', $order->payment_status);
        $this->assertDatabaseHas('expenses', [
            'category' => 'refund',
            'amount' => 4000.00,
        ]);
    }
}
