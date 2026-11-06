<?php

namespace Tests\Feature;

use App\Models\CleaningTeam;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\TeamMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealDashboardMetricsAndActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $reception;
    protected User $cleanerUser;
    protected CleaningTeam $team;
    protected Customer $customer;
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

        $this->cleanerUser = User::factory()->create([
            'role' => 'cleaner',
            'phone' => '0911000003',
        ]);

        $this->team = CleaningTeam::create([
            'team_name' => 'Delta Squad',
            'phone' => '0911000004',
            'status' => 'active',
            'team_leader_id' => $this->cleanerUser->id,
        ]);

        TeamMember::create([
            'cleaning_team_id' => $this->team->id,
            'user_id' => $this->cleanerUser->id,
            'role_in_team' => 'leader',
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUST-001',
            'full_name' => 'Alemayehu Tadesse',
            'phone' => '0911223344',
            'address' => 'Bole Medhanialem',
            'subcity' => 'Bole',
        ]);

        $this->service = Service::create([
            'code' => 'SRV-001',
            'name_en' => 'Deep Cleaning',
            'name_am' => 'ጥልቅ ጽዳት',
            'base_price' => 1500,
            'unit' => 'room',
            'is_active' => true,
        ]);
    }

    public function test_owner_dashboard_returns_real_satisfaction_metrics_and_time_series(): void
    {
        // 1. Create real feedbacks
        Feedback::create([
            'customer_id' => $this->customer->id,
            'rating' => 5,
            'comment' => 'Great service!',
            'is_approved' => true,
        ]);
        Feedback::create([
            'customer_id' => $this->customer->id,
            'rating' => 4,
            'comment' => 'Very good',
            'is_approved' => true,
        ]);

        // 2. Create real payments & expenses
        $initOrder = Order::create([
            'order_number' => 'ORD-INIT',
            'customer_id' => $this->customer->id,
            'source' => 'phone',
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total' => 3000,
            'address' => 'Bole Medhanialem',
            'appointment_date' => Carbon::today(),
        ]);

        Payment::create([
            'payment_number' => Payment::generateNextNumber(),
            'order_id' => $initOrder->id,
            'customer_id' => $this->customer->id,
            'payment_date' => Carbon::today(),
            'amount' => 3000,
            'payment_method' => 'telebirr',
            'status' => 'completed',
        ]);
        Expense::create([
            'expense_number' => 'EXP-001',
            'category' => 'chemicals',
            'amount' => 500,
            'description' => 'Cleaning detergent',
            'date' => Carbon::today(),
            'entered_by_user_id' => $this->owner->id,
        ]);

        // 3. Create active subscription
        Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_type' => 'weekly',
            'service_summary' => 'Weekly residential cleaning',
            'base_price' => 1000,
            'discounted_price' => 850,
            'discount_percent' => 15,
            'preferred_day' => 'Monday',
            'preferred_time_slot' => 'morning',
            'start_date' => Carbon::today(),
            'next_service_date' => Carbon::today()->addWeek(),
            'status' => 'active',
        ]);

        $res = $this->actingAs($this->owner)->getJson('/api/dashboard/owner');
        $res->assertOk();
        $res->assertJsonStructure([
            'today',
            'month_to_date',
            'satisfaction' => ['avg_rating', 'total_feedbacks', 'rating_distribution'],
            'team_performance',
            'subscriptions' => ['active_count', 'mrr'],
            'daily_trend',
            'channel_breakdown',
        ]);

        $data = $res->json();
        $this->assertEquals(4.5, $data['satisfaction']['avg_rating']);
        $this->assertEquals(2, $data['satisfaction']['total_feedbacks']);
        $this->assertCount(7, $data['daily_trend']);
        $this->assertGreaterThan(0, $data['subscriptions']['mrr']);
    }

    public function test_finance_profit_report_returns_daily_and_monthly_time_series(): void
    {
        $res = $this->actingAs($this->owner)->getJson('/api/finance/profit-report?period=this_month');
        $res->assertOk();
        $res->assertJsonStructure([
            'revenue',
            'expenses',
            'net_profit',
            'time_series' => [
                'daily',
                'monthly',
            ],
        ]);

        $daily = $res->json('time_series.daily');
        $this->assertIsArray($daily);
        $this->assertCount(7, $daily);
    }

    public function test_cleaner_my_jobs_returns_real_team_stats_and_persists_checklist(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-101',
            'customer_id' => $this->customer->id,
            'assigned_team_id' => $this->team->id,
            'source' => 'phone',
            'order_status' => 'cleaning',
            'payment_status' => 'unpaid',
            'total' => 1500,
            'address' => 'Bole Medhanialem',
            'appointment_date' => Carbon::today(),
        ]);

        $myJobsRes = $this->actingAs($this->cleanerUser)->getJson('/api/teams/my-jobs');
        $myJobsRes->assertOk();
        $myJobsRes->assertJsonStructure([
            'team',
            'stats' => ['total_completed', 'total_assigned', 'completion_rate', 'avg_rating'],
            'today_jobs',
        ]);

        // Complete job with checklist items
        $actionRes = $this->actingAs($this->cleanerUser)->postJson("/api/teams/jobs/{$order->id}/action", [
            'action' => 'complete',
            'notes' => 'Finished all rooms cleanly.',
            'checklist' => ['Living room deep cleaned', 'Windows polished', 'Floor vacuumed'],
        ]);
        $actionRes->assertOk();

        $order->refresh();
        $this->assertEquals('completed', $order->order_status);
        $this->assertNotNull($order->completed_at);
        $this->assertStringContainsString('Living room deep cleaned', $order->completion_notes);
        $this->assertStringContainsString('Finished all rooms cleanly.', $order->completion_notes);
    }

    public function test_reception_and_owner_can_create_draft_order_and_reassign_team(): void
    {
        $this->withoutExceptionHandling();
        // 1. Create draft order
        $createRes = $this->actingAs($this->reception)->postJson('/api/orders', [
            'customer_id' => $this->customer->id,
            'address' => 'Bole Medhanialem',
            'appointment_date' => Carbon::today()->toDateString(),
            'order_status' => 'pending_confirmation',
            'is_draft' => true,
            'source' => 'phone',
            'items' => [
                [
                    'service_id' => $this->service->id,
                    'quantity' => 2,
                    'unit_price' => 1500,
                ],
            ],
        ]);
        $createRes->assertStatus(201);
        $orderId = $createRes->json('order.id');

        $order = Order::findOrFail($orderId);
        $this->assertEquals('pending_confirmation', $order->order_status);

        // 2. Re-assign team directly
        $assignRes = $this->actingAs($this->reception)->postJson("/api/orders/{$orderId}/assign-team", [
            'team_id' => $this->team->id,
        ]);
        $assignRes->assertOk();

        $order->refresh();
        $this->assertEquals($this->team->id, $order->assigned_team_id);
    }

    public function test_subscription_creation_with_inline_customer_and_mrr_calculation(): void
    {
        $subRes = $this->actingAs($this->owner)->postJson('/api/subscriptions', [
            'phone' => '0933445566',
            'customer_name' => 'Tigist Haile',
            'plan_type' => 'biweekly',
            'base_price' => 2000,
            'service_summary' => 'Bi-weekly house cleaning',
            'start_date' => Carbon::today()->toDateString(),
        ]);
        $subRes->assertStatus(201);

        $indexRes = $this->actingAs($this->owner)->getJson('/api/subscriptions');
        $indexRes->assertOk();
        $indexRes->assertJsonStructure([
            'stats' => ['total_active', 'mrr', 'arr'],
            'subscriptions',
        ]);

        $this->assertGreaterThan(0, $indexRes->json('stats.mrr'));
    }
}
