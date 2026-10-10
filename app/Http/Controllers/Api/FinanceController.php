<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Proforma;
use App\Models\SalesVisit;
use App\Services\EthiopianCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function expenses(Request $request): JsonResponse
    {
        $query = Expense::with('enteredBy:id,name');

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($request->input('date') === 'today') {
            $query->whereDate('date', Carbon::today());
        } elseif ($month = $request->input('month')) {
            $query->whereMonth('date', Carbon::parse($month)->month)
                  ->whereYear('date', Carbon::parse($month)->year);
        }

        $expenses = $query->latest('date')->paginate($request->input('per_page', 20));

        $expenses->getCollection()->transform(function ($exp) {
            $eth = EthiopianCalendarService::toEthiopian($exp->date);
            $exp->eth_date = $eth['formatted_am'];
            $exp->eth_date_en = $eth['formatted_en'];
            return $exp;
        });

        return response()->json($expenses);
    }

    public function storeExpense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'required|string|in:transport,chemicals,materials,employee_payments,equipment_repair,fuel,marketing,rent,utilities,other',
            'amount' => 'required|numeric|min:1',
            'reference_number' => 'nullable|string|max:64',
            'description' => 'required|string',
            'date' => 'required|date',
            'receipt_path' => 'nullable|string',
        ]);

        $validated['expense_number'] = Expense::generateNextNumber();
        $validated['entered_by_user_id'] = $request->user()->id;

        $expense = Expense::create($validated);

        AuditLog::logAction(
            $request->user()->id,
            'expense_recorded',
            Expense::class,
            $expense->id,
            null,
            $expense->toArray()
        );

        return response()->json([
            'message' => 'Expense recorded successfully',
            'expense' => $expense->load('enteredBy:id,name'),
        ], 201);
    }

    public function recordPayroll(Request $request): JsonResponse
    {
        if (!$request->has('employee_id') && $request->has('user_id')) {
            $request->merge(['employee_id' => $request->input('user_id')]);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'month' => 'nullable|string',
            'notes' => 'nullable|string',
            'reference_number' => 'nullable|string',
        ]);

        $employee = \App\Models\User::findOrFail($validated['employee_id']);
        $monthName = !empty($validated['month']) ? $validated['month'] : '';
        $note = $validated['notes'] ?: ("የደመወዝ ክፍያ ለ" . $employee->name . ($monthName ? " ({$monthName})" : ""));

        $expense = Expense::create([
            'expense_number' => Expense::generateNextNumber(),
            'category' => 'employee_payments',
            'amount' => $validated['amount'],
            'reference_number' => $validated['reference_number'] ?? ('PAY-' . date('Ymd')),
            'description' => $note,
            'date' => $validated['payment_date'],
            'entered_by_user_id' => $request->user()->id,
        ]);

        AuditLog::logAction(
            $request->user()->id,
            'payroll_recorded',
            Expense::class,
            $expense->id,
            null,
            ['employee' => $employee->name, 'amount' => $validated['amount']]
        );

        return response()->json([
            'success' => true,
            'message' => "ለ{$employee->name} የተከፈለው " . number_format($validated['amount'], 2) . " ብር ደመወዝ በወጪ መዝገብ ላይ ተመዝግቧል::",
            'expense' => $expense->load('enteredBy:id,name'),
        ], 201);
    }

    public function payments(Request $request): JsonResponse
    {
        $query = Payment::with([
            'customer:id,customer_code,full_name,phone',
            'order:id,order_number,total,payment_status',
            'recordedBy:id,name',
        ]);

        if ($method = $request->input('payment_method')) {
            $query->where('payment_method', $method);
        }

        $payments = $query->latest('payment_date')->paginate(20);

        $payments->getCollection()->transform(function ($pay) {
            $eth = EthiopianCalendarService::toEthiopian($pay->payment_date);
            $pay->eth_date = $eth['formatted_am'];
            return $pay;
        });

        return response()->json($payments);
    }

    public function profitReport(Request $request): JsonResponse
    {
        $period = $request->input('period', 'this_month'); // today, this_week, this_month, this_year, all_time

        $orderQuery = Order::query();
        $paymentQuery = Payment::query();
        $expenseQuery = Expense::query();
        $customerQuery = Customer::query();

        if ($period === 'today') {
            $orderQuery->whereDate('appointment_date', Carbon::today());
            $paymentQuery->whereDate('payment_date', Carbon::today());
            $expenseQuery->whereDate('date', Carbon::today());
            $customerQuery->whereDate('created_at', Carbon::today());
        } elseif ($period === 'this_week') {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
            $orderQuery->whereBetween('appointment_date', [$start, $end]);
            $paymentQuery->whereBetween('payment_date', [$start, $end]);
            $expenseQuery->whereBetween('date', [$start, $end]);
            $customerQuery->whereBetween('created_at', [$start, $end]);
        } elseif ($period === 'this_month') {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $orderQuery->whereBetween('appointment_date', [$start, $end]);
            $paymentQuery->whereBetween('payment_date', [$start, $end]);
            $expenseQuery->whereBetween('date', [$start, $end]);
            $customerQuery->whereBetween('created_at', [$start, $end]);
        }

        $totalRevenue = (float) $paymentQuery->sum('amount');
        $totalExpenses = (float) $expenseQuery->sum('amount');
        $netProfit = $totalRevenue - $totalExpenses;

        $completedJobs = (clone $orderQuery)->where('order_status', 'completed')->count();
        $cancelledJobs = (clone $orderQuery)->where('order_status', 'cancelled')->count();
        $totalJobs = (clone $orderQuery)->count();
        $newCustomers = $customerQuery->count();

        $repeatCustomers = Customer::has('orders', '>', 1)->count();

        // Expense category breakdown
        $expenseBreakdown = (clone $expenseQuery)
            ->select('category', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('category')
            ->get();

        // Rating average
        $avgRating = (float) Feedback::avg('rating') ?: 5.0;
        $complaintsCount = Complaint::where('status', '!=', 'closed')->count();

        // Outdoor sales conversion metrics
        $totalVisits = SalesVisit::count();
        $wonVisits = SalesVisit::where('stage', 'won')->count();
        $conversionRate = $totalVisits > 0 ? round(($wonVisits / $totalVisits) * 100, 1) : 0;
        $pendingProformas = Proforma::where('status', 'draft')->orWhere('status', 'sent')->count();

        return response()->json([
            'period' => $period,
            'revenue' => $totalRevenue,
            'expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'profit_margin' => $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 1) : 0,
            'completed_jobs' => $completedJobs,
            'cancelled_jobs' => $cancelledJobs,
            'total_jobs' => $totalJobs,
            'new_customers' => $newCustomers,
            'repeat_customers' => $repeatCustomers,
            'avg_rating' => round($avgRating, 1),
            'open_complaints' => $complaintsCount,
            'sales_metrics' => [
                'total_leads' => $totalVisits,
                'won_deals' => $wonVisits,
                'conversion_rate' => $conversionRate,
                'pending_proformas' => $pendingProformas,
            ],
            'expense_breakdown' => $expenseBreakdown,
        ]);
    }
}
