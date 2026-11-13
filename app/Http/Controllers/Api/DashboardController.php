<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CleaningTeam;
use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Feedback;
use App\Models\Followup;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Proforma;
use App\Models\SalesVisit;
use App\Models\Subscription;
use App\Services\EthiopianCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function ownerSummary(): JsonResponse
    {
        // "Today" is the user's day in Addis Ababa (UTC+3), not the
        // server's UTC day: at 01:00 EAT the UTC date is still
        // yesterday, so UTC bucketing labelled Friday's early
        // transactions under Thursday.
        $today = Carbon::today('Africa/Addis_Ababa');

        // Jobs
        $todayJobs = Order::whereDate('appointment_date', $today)->count();
        $pendingBookings = Order::whereIn('order_status', ['new', 'pending_confirmation'])->count();
        $unassignedJobs = Order::whereDate('appointment_date', '>=', $today)
            ->whereNull('assigned_team_id')
            ->whereNotIn('order_status', ['cancelled', 'completed'])
            ->count();

        // Customer & care
        $newCustomersToday = Customer::whereDate('created_at', $today)->count();
        $followupsDue = Followup::whereDate('due_date', '<=', $today)
            ->where('status', 'pending')
            ->count();
        $openComplaints = Complaint::where('status', '!=', 'closed')->count();

        // Corporate & Sales
        $corporateVisits = SalesVisit::whereDate('visit_date', $today)->count();
        $pendingProformas = Proforma::whereIn('status', ['draft', 'sent'])->count();

        // Real Financials for today
        $todayIncome = (float) Payment::whereDate('payment_date', $today)->sum('amount');
        $todayExpenses = (float) Expense::whereDate('date', $today)->sum('amount');
        $netResult = $todayIncome - $todayExpenses;

        // Month-to-date Financials
        $monthStart = Carbon::now()->startOfMonth();
        $monthIncome = (float) Payment::where('payment_date', '>=', $monthStart)->sum('amount');
        $monthExpenses = (float) Expense::where('date', '>=', $monthStart)->sum('amount');
        $monthNet = $monthIncome - $monthExpenses;

        // Channel Source Breakdown & Statistics
        $channelStats = Order::select('source', DB::raw('count(*) as count'), DB::raw('sum(total) as revenue'))
            ->groupBy('source')
            ->get()
            ->keyBy(function ($item) {
                return ($item->source === 'walkin') ? 'walk_in' : $item->source;
            });

        $channelDefinitions = [
            'website' => ['label_am' => 'ድረ-ገጽ', 'label_en' => 'Website', 'icon' => 'globe', 'color' => 'indigo'],
            'telegram_miniapp' => ['label_am' => 'ቴሌግራም ሚኒአፕ', 'label_en' => 'Telegram MiniApp', 'icon' => 'smartphone', 'color' => 'sky'],
            'telegram_bot' => ['label_am' => 'ቴሌግራም ቦት', 'label_en' => 'Telegram Bot', 'icon' => 'bot', 'color' => 'cyan'],
            'phone' => ['label_am' => 'ስልክ ጥሪ', 'label_en' => 'Phone Call', 'icon' => 'phone', 'color' => 'emerald'],
            'walk_in' => ['label_am' => 'በአካል የመጣ', 'label_en' => 'Walk-in', 'icon' => 'footprints', 'color' => 'purple'],
            'internal' => ['label_am' => 'ውስጣዊ / ሪሴፕሽን', 'label_en' => 'Internal / Reception', 'icon' => 'home', 'color' => 'indigo'],
            'corporate_contract' => ['label_am' => 'ኮርፖሬት ውል', 'label_en' => 'Corporate', 'icon' => 'briefcase', 'color' => 'amber'],
        ];

        $channelBreakdown = [];
        foreach ($channelDefinitions as $key => $meta) {
            $stat = $channelStats->get($key);
            $channelBreakdown[$key] = [
                'source' => $key,
                'label_am' => $meta['label_am'],
                'label_en' => $meta['label_en'],
                'icon' => $meta['icon'],
                'color' => $meta['color'],
                'orders_count' => $stat ? (int)$stat->count : 0,
                'total_revenue' => $stat ? (float)$stat->revenue : 0.0,
            ];
        }

        // Payment Method breakdown
        $paymentMethodStats = Payment::select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
            ->groupBy('payment_method')
            ->get()
            ->keyBy(function ($item) {
                return ($item->payment_method === 'cbe_birr') ? 'cbe' : $item->payment_method;
            });

        $paymentMethodDefs = [
            'cash' => ['label_am' => 'ጥሬ ገንዘብ', 'label_en' => 'Cash'],
            'telebirr' => ['label_am' => 'ቴሌብር', 'label_en' => 'Telebirr'],
            'cbe' => ['label_am' => 'የኢትዮጵያ ንግድ ባንክ (CBE)', 'label_en' => 'Commercial Bank of Ethiopia'],
            'bank_transfer' => ['label_am' => 'የባንክ ዝውውር', 'label_en' => 'Bank Transfer'],
        ];

        $paymentBreakdown = [];
        foreach ($paymentMethodDefs as $pmKey => $pmMeta) {
            $pStat = $paymentMethodStats->get($pmKey);
            $paymentBreakdown[$pmKey] = [
                'method' => $pmKey,
                'label_am' => $pmMeta['label_am'],
                'label_en' => $pmMeta['label_en'],
                'count' => $pStat ? (int)$pStat->count : 0,
                'total_amount' => $pStat ? (float)$pStat->total_amount : 0.0,
            ];
        }

        // Ethiopian date display
        $ethDate = EthiopianCalendarService::toEthiopian($today);

        // Recent activity
        $recentOrders = Order::with(['customer:id,full_name,phone,subcity', 'assignedTeam:id,team_name'])
            ->latest()
            ->limit(8)
            ->get();

        // 1. Rating & Customer Satisfaction Stats
        $totalFeedbacks = Feedback::count();
        // No reviews yet => null, never a fake 5.0.
        $avgRating = $totalFeedbacks > 0 ? (float) Feedback::avg('rating') : null;
        $starCounts = Feedback::select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating');

        $ratingDistribution = [];
        for ($s = 5; $s >= 1; $s--) {
            $c = (int)($starCounts[$s] ?? 0);
            $ratingDistribution[$s] = [
                'stars' => $s,
                'count' => $c,
                'percentage' => $totalFeedbacks > 0 ? round(($c / $totalFeedbacks) * 100) : 0,
            ];
        }

        // 2. Team Performance Metrics
        $teams = CleaningTeam::where('status', 'active')->with('leader:id,name')->get();
        $teamPerformance = $teams->map(function ($t) {
            $totalAssigned = Order::where('assigned_team_id', $t->id)->count();
            $completed = Order::where('assigned_team_id', $t->id)->where('order_status', 'completed')->count();
            $rate = $totalAssigned > 0 ? round(($completed / $totalAssigned) * 100) : null;
            return [
                'id' => $t->id,
                'name' => $t->team_name,
                'leader' => $t->leader?->name ?? 'N/A',
                'total_assigned' => $totalAssigned,
                'completed' => $completed,
                'completion_rate' => $rate,
            ];
        });

        // 3. Subscriptions MRR
        $activeSubs = Subscription::where('status', 'active')->get();
        $mrr = 0.0;
        foreach ($activeSubs as $sub) {
            $price = (float)($sub->discounted_price ?: $sub->base_price);
            $mrr += match ($sub->plan_type) {
                'weekly' => $price * 4.0,
                'biweekly' => $price * 2.0,
                default => $price,
            };
        }

        // 4. Daily trend (past 7 days) — bucketed on the Addis Ababa
        // day, matching how the dates were recorded (see
        // OrderController::recordPayment / FinanceController::recordRefund).
        $dailyTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today('Africa/Addis_Ababa')->subDays($i);
            $dStr = $d->toDateString();
            $eth = EthiopianCalendarService::toEthiopian($d);
            $inc = (float) Payment::whereDate('payment_date', $dStr)->sum('amount');
            $exp = (float) Expense::whereDate('date', $dStr)->sum('amount');
            $dailyTrend[] = [
                'date' => $dStr,
                'eth_date' => $eth['formatted_am'] ?? $dStr,
                'day_name' => $d->format('D'),
                'income' => $inc,
                'expense' => $exp,
                'net' => $inc - $exp,
            ];
        }

        return response()->json([
            'today' => [
                'eth_date' => $ethDate['formatted_am'],
                'eth_date_en' => $ethDate['formatted_en'],
                'cleaning_jobs' => $todayJobs,
                'pending_bookings' => $pendingBookings,
                'unassigned_jobs' => $unassignedJobs,
                'new_customers' => $newCustomersToday,
                'followups_due' => $followupsDue,
                'open_complaints' => $openComplaints,
                'corporate_visits' => $corporateVisits,
                'pending_proformas' => $pendingProformas,
                'income' => $todayIncome,
                'expenses' => $todayExpenses,
                'net_result' => $netResult,
            ],
            'month_to_date' => [
                'income' => $monthIncome,
                'expenses' => $monthExpenses,
                'net_profit' => $monthNet,
            ],
            'satisfaction' => [
                'avg_rating' => $avgRating !== null ? round($avgRating, 1) : null,
                'total_feedbacks' => $totalFeedbacks,
                'rating_distribution' => $ratingDistribution,
            ],
            'team_performance' => $teamPerformance,
            'subscriptions' => [
                'active_count' => $activeSubs->count(),
                'mrr' => round($mrr, 2),
            ],
            'daily_trend' => $dailyTrend,
            'channel_breakdown' => $channelBreakdown,
            'payment_breakdown' => $paymentBreakdown,
            'recent_orders' => $recentOrders,
        ]);
    }

    public function receptionOverview(): JsonResponse
    {
        // Addis Ababa day, not the server UTC day (see ownerSummary).
        $today = Carbon::today('Africa/Addis_Ababa');

        // Actionable queues for reception - ordered by latest so incoming Website/MiniApp/Bot bookings appear on top
        $newBookings = Order::with(['customer', 'items.service', 'assignedTeam'])
            ->whereIn('order_status', ['new', 'pending_confirmation'])
            ->latest('created_at')
            ->limit(20)
            ->get();

        $todaySchedule = Order::with(['customer', 'assignedTeam', 'items.service', 'appointments'])
            ->whereDate('appointment_date', $today)
            ->orderBy('appointment_time_slot')
            ->get();

        $dueFollowups = Followup::with(['customer', 'order'])
            ->whereDate('due_date', '<=', $today)
            ->where('status', 'pending')
            ->limit(10)
            ->get();

        $urgentComplaints = Complaint::with(['customer', 'order'])
            ->whereIn('status', ['new', 'assigned'])
            ->orderByDesc('priority')
            ->limit(10)
            ->get();

        $activeTeams = CleaningTeam::with(['leader:id,name,phone'])
            ->where('status', 'active')
            ->get();

        // Source distribution for pending queue
        $newBookingsBySource = Order::whereIn('order_status', ['new', 'pending_confirmation'])
            ->select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->get()
            ->mapWithKeys(function ($item) {
                $src = ($item->source === 'walkin') ? 'walk_in' : $item->source;
                return [$src => (int)$item->count];
            });

        $ethDate = EthiopianCalendarService::toEthiopian($today);

        $websiteMessages = Notification::where('type', 'customer_inquiry')
            ->latest()
            ->limit(15)
            ->get();

        return response()->json([
            'eth_date' => $ethDate['formatted_am'],
            'eth_date_en' => $ethDate['formatted_en'],
            'new_bookings' => $newBookings,
            'today_schedule' => $todaySchedule,
            'due_followups' => $dueFollowups,
            'urgent_complaints' => $urgentComplaints,
            'active_teams' => $activeTeams,
            'channel_counts' => $newBookingsBySource,
            'website_messages' => $websiteMessages,
            'counts' => [
                'new_bookings' => Order::whereIn('order_status', ['new', 'pending_confirmation'])->count(),
                'today_jobs' => $todaySchedule->count(),
                'followups_due' => $dueFollowups->count(),
                'open_complaints' => $urgentComplaints->count(),
                'website_messages' => Notification::where('type', 'customer_inquiry')->where('is_read', false)->count(),
            ],
        ]);
    }
}
