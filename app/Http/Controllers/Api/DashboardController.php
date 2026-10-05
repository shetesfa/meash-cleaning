<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CleaningTeam;
use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Followup;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Proforma;
use App\Models\SalesVisit;
use App\Services\EthiopianCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function ownerSummary(): JsonResponse
    {
        $today = Carbon::today();

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

        // Ethiopian date display
        $ethDate = EthiopianCalendarService::toEthiopian($today);

        // Recent activity
        $recentOrders = Order::with(['customer:id,full_name,phone,subcity', 'assignedTeam:id,team_name'])
            ->latest()
            ->limit(5)
            ->get();

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
            'recent_orders' => $recentOrders,
        ]);
    }

    public function receptionOverview(): JsonResponse
    {
        $today = Carbon::today();

        // Actionable queues for reception
        $newBookings = Order::with(['customer', 'items.service'])
            ->whereIn('order_status', ['new', 'pending_confirmation'])
            ->orderBy('appointment_date')
            ->limit(10)
            ->get();

        $todaySchedule = Order::with(['customer', 'assignedTeam', 'items.service'])
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

        $ethDate = EthiopianCalendarService::toEthiopian($today);

        return response()->json([
            'eth_date' => $ethDate['formatted_am'],
            'eth_date_en' => $ethDate['formatted_en'],
            'new_bookings' => $newBookings,
            'today_schedule' => $todaySchedule,
            'due_followups' => $dueFollowups,
            'urgent_complaints' => $urgentComplaints,
            'active_teams' => $activeTeams,
            'counts' => [
                'new_bookings' => Order::whereIn('order_status', ['new', 'pending_confirmation'])->count(),
                'today_jobs' => $todaySchedule->count(),
                'followups_due' => $dueFollowups->count(),
                'open_complaints' => $urgentComplaints->count(),
            ],
        ]);
    }
}
