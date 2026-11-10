<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CleaningTeam;
use App\Models\Complaint;
use App\Models\Feedback;
use App\Models\Followup;
use App\Models\Order;
use App\Models\TeamMember;
use App\Services\EthiopianCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = CleaningTeam::with(['leader:id,name,phone', 'members.user:id,name,phone'])
            ->withCount(['orders as active_jobs_count' => function ($q) {
                $q->whereIn('order_status', ['assigned', 'on_the_way', 'cleaning']);
            }]);

        // Cleaners: Strict access limit to ONLY their own assigned team
        if ($user && $user->isCleaner()) {
            $cleanerTeamIds = DB::table('team_members')->where('user_id', $user->id)->pluck('cleaning_team_id')
                ->merge(DB::table('cleaning_teams')->where('team_leader_id', $user->id)->pluck('id'))
                ->unique();

            if ($cleanerTeamIds->isEmpty()) {
                return response()->json([]);
            }
            $query->whereIn('id', $cleanerTeamIds);
        }

        $teams = $query->get();

        $teams->transform(function ($team) {
            $totalAssigned = Order::where('assigned_team_id', $team->id)->count();
            $completed = Order::where('assigned_team_id', $team->id)->where('order_status', 'completed')->count();
            $team->total_jobs_count = $totalAssigned;
            $team->completed_jobs_count = $completed;
            $team->completion_rate = $totalAssigned > 0 ? round(($completed / $totalAssigned) * 100) : 100;
            $team->avg_rating = round((float) Feedback::whereHas('order', fn($q) => $q->where('assigned_team_id', $team->id))->avg('rating') ?: 5.0, 1);
            return $team;
        });

        return response()->json($teams);
    }

    public function myJobs(Request $request): JsonResponse
    {
        $user = $request->user();

        // Enforce strict cleaner team isolation
        if ($user->isCleaner()) {
            $cleanerTeamIds = DB::table('team_members')->where('user_id', $user->id)->pluck('cleaning_team_id')
                ->merge(DB::table('cleaning_teams')->where('team_leader_id', $user->id)->pluck('id'))
                ->unique();

            if ($cleanerTeamIds->isEmpty()) {
                return response()->json([
                    'team' => null,
                    'teams_list' => [],
                    'today_jobs' => [],
                    'upcoming_jobs' => [],
                    'completed_today' => [],
                ]);
            }

            $explicitTeamId = $request->query('team_id');
            if ($explicitTeamId && !$cleanerTeamIds->contains((int)$explicitTeamId)) {
                return response()->json([
                    'message' => 'Unauthorized. You may only view jobs assigned to your own team.',
                ], 403);
            }

            $teamId = $explicitTeamId ? (int)$explicitTeamId : $cleanerTeamIds->first();
            $allTeams = CleaningTeam::whereIn('id', $cleanerTeamIds)->get(['id', 'team_name', 'phone', 'status']);
        } else {
            // Owner and Reception may inspect any team
            $explicitTeamId = $request->query('team_id');
            $teamId = ($explicitTeamId && CleaningTeam::where('id', $explicitTeamId)->exists())
                ? (int)$explicitTeamId
                : CleaningTeam::first()?->id;

            $allTeams = CleaningTeam::all(['id', 'team_name', 'phone', 'status']);
        }

        if (!$teamId) {
            return response()->json([
                'team' => null,
                'teams_list' => $allTeams,
                'today_jobs' => [],
                'upcoming_jobs' => [],
                'completed_today' => [],
                'completed_jobs' => [],
            ]);
        }

        $team = CleaningTeam::with(['leader:id,name,phone', 'members.user:id,name,phone'])->find($teamId);

        $jobsQuery = Order::query()->with([
            'customer:id,customer_code,full_name,phone,alt_phone,address,subcity,landmark',
            'items.service:id,name_en,name_am,unit,icon',
            'appointments',
        ]);

        if ($teamId) {
            $jobsQuery->where('assigned_team_id', $teamId);
        }

        $today = Carbon::today();

        // 1. Active / Today & Immediate Assigned Jobs for this team
        $todayJobs = (clone $jobsQuery)
            ->where(function ($q) use ($today) {
                $q->whereDate('appointment_date', '<=', $today)
                  ->orWhereNull('appointment_date');
            })
            ->whereIn('order_status', ['assigned', 'on_the_way', 'cleaning', 'confirmed', 'new', 'pending_confirmation'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time_slot')
            ->get();

        // 2. Completed jobs today
        $completedToday = (clone $jobsQuery)
            ->whereDate('appointment_date', $today)
            ->where('order_status', 'completed')
            ->latest('completed_at')
            ->get();

        // 3. Completed history (all completed jobs for this team, latest 50)
        $completedHistory = (clone $jobsQuery)
            ->where('order_status', 'completed')
            ->orderByDesc('completed_at')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        // 4. Upcoming future appointments for this team
        $upcomingJobs = (clone $jobsQuery)
            ->whereDate('appointment_date', '>', $today)
            ->whereIn('order_status', ['confirmed', 'assigned', 'new'])
            ->orderBy('appointment_date')
            ->limit(20)
            ->get();

        $formatJob = function ($order) {
            $eth = EthiopianCalendarService::toEthiopian($order->appointment_date);
            $order->eth_date = $eth['formatted_am'];
            $order->eth_date_en = $eth['formatted_en'];
            return $order;
        };

        $totalCompleted = $teamId ? Order::where('assigned_team_id', $teamId)->where('order_status', 'completed')->count() : 0;
        $totalAssigned = $teamId ? Order::where('assigned_team_id', $teamId)->count() : 0;
        $completionRate = $totalAssigned > 0 ? round(($totalCompleted / $totalAssigned) * 100) : 100;
        $teamAvgRating = $teamId ? round((float) Feedback::whereHas('order', fn($q) => $q->where('assigned_team_id', $teamId))->avg('rating') ?: 5.0, 1) : 5.0;

        $teamStats = [
            'total_completed' => $totalCompleted,
            'total_assigned' => $totalAssigned,
            'completion_rate' => $completionRate,
            'avg_rating' => $teamAvgRating,
            'active_jobs_count' => $todayJobs->count(),
        ];

        return response()->json([
            'team' => $team,
            'teams_list' => $allTeams,
            'stats' => $teamStats,
            'today_jobs' => $todayJobs->map($formatJob),
            'completed_today' => $completedToday->map($formatJob),
            'completed_jobs' => $completedHistory->map($formatJob),
            'upcoming_jobs' => $upcomingJobs->map($formatJob),
        ]);
    }

    public function handleJobAction(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Verify cleaner ownership and team assignment
        if ($user->isCleaner()) {
            $cleanerTeamIds = DB::table('team_members')->where('user_id', $user->id)->pluck('cleaning_team_id')
                ->merge(DB::table('cleaning_teams')->where('team_leader_id', $user->id)->pluck('id'))
                ->unique();

            // Prevent cross-team access (IDOR)
            if ($cleanerTeamIds->isNotEmpty() && $order->assigned_team_id && !$cleanerTeamIds->contains($order->assigned_team_id)) {
                return response()->json([
                    'message' => 'Unauthorized. You can only perform actions on jobs assigned to your team.',
                ], 403);
            }
        }

        $validated = $request->validate([
            'action' => 'required|string|in:start,complete,on_the_way,report_problem',
            'notes' => 'nullable|string',
            'checklist' => 'nullable|array',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'problem_category' => 'nullable|string',
            'problem_description' => 'nullable|string',
        ]);

        // Enforce valid state transitions
        if ($user->isCleaner()) {
            if ($validated['action'] === 'start' && !in_array($order->order_status, ['assigned', 'on_the_way'])) {
                return response()->json([
                    'message' => 'Invalid state transition. Job must be assigned or on the way before starting cleaning.',
                ], 422);
            }

            if ($validated['action'] === 'complete' && !in_array($order->order_status, ['cleaning', 'assigned', 'on_the_way'])) {
                return response()->json([
                    'message' => 'Invalid state transition. Job must be in active cleaning or assigned status before completing.',
                ], 422);
            }
        }

        $oldStatus = $order->order_status;

        // If GPS coordinates provided, update team location
        if (!empty($validated['latitude']) && !empty($validated['longitude']) && $order->assigned_team_id) {
            CleaningTeam::where('id', $order->assigned_team_id)->update([
                'current_latitude' => $validated['latitude'],
                'current_longitude' => $validated['longitude'],
                'location_updated_at' => now(),
            ]);
        }

        if ($validated['action'] === 'on_the_way') {
            $order->order_status = 'on_the_way';
            // Send live dispatched SMS to customer
            \App\Services\SmsService::sendTeamDispatched($order);
        } elseif ($validated['action'] === 'start') {
            $order->order_status = 'cleaning';
        } elseif ($validated['action'] === 'complete') {
            $order->order_status = 'completed';
            $order->completed_at = now();
            $notesParts = [];
            if (!empty($validated['notes'])) {
                $notesParts[] = $validated['notes'];
            }
            if (!empty($validated['checklist']) && is_array($validated['checklist'])) {
                $notesParts[] = "የተከናወኑ ስራዎች ዝርዝር (Checklist):\n- " . implode("\n- ", $validated['checklist']);
            }
            if (!empty($notesParts)) {
                $order->completion_notes = implode("\n\n", $notesParts);
            }

            // Create next-day follow-up
            Followup::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'customer_id' => $order->customer_id,
                    'due_date' => Carbon::tomorrow(),
                    'status' => 'pending',
                    'notes' => 'Next-day automated follow-up scheduled upon cleaning completion',
                ]
            );
        } elseif ($validated['action'] === 'report_problem') {
            $order->order_status = 'problem_reported';
            if (!empty($validated['notes'])) {
                $order->notes = ($order->notes ? $order->notes . "\n" : '') . "[Problem Reported by Cleaner]: " . $validated['notes'];
            }

            // Also create a complaint record to notify reception/owner immediately
            Complaint::create([
                'complaint_number' => Complaint::generateNextNumber(),
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'category' => $validated['problem_category'] ?? 'service_issue',
                'description' => $validated['problem_description'] ?? $validated['notes'] ?? 'Problem reported by cleaning crew on site.',
                'priority' => 'high',
                'status' => 'new',
            ]);
        }

        $order->version += 1;
        $order->save();

        AuditLog::logAction(
            $request->user()?->id,
            'job_action_' . $validated['action'],
            Order::class,
            $order->id,
            ['status' => $oldStatus],
            ['status' => $order->order_status, 'notes' => $validated['notes'] ?? null]
        );

        return response()->json([
            'message' => "Job updated to {$order->order_status}",
            'order' => $order->fresh(['customer', 'items']),
        ]);
    }

    public function updateLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'team_id' => 'nullable|exists:cleaning_teams,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = $request->user();

        if ($user->isCleaner()) {
            $cleanerTeamIds = DB::table('team_members')->where('user_id', $user->id)->pluck('cleaning_team_id')
                ->merge(DB::table('cleaning_teams')->where('team_leader_id', $user->id)->pluck('id'))
                ->unique();

            if ($cleanerTeamIds->isEmpty()) {
                return response()->json(['message' => 'No active cleaning team associated with user.'], 403);
            }

            // Cleaners may ONLY update their own team location
            if (!empty($validated['team_id']) && !$cleanerTeamIds->contains((int)$validated['team_id'])) {
                return response()->json(['message' => 'Unauthorized. You can only update GPS for your own team.'], 403);
            }

            $teamId = $cleanerTeamIds->first();
        } else {
            $teamId = $validated['team_id'];
            if (!$teamId) {
                $teamId = CleaningTeam::first()?->id;
            }
        }

        if (!$teamId) {
            return response()->json(['message' => 'No active cleaning team found.'], 422);
        }

        $team = CleaningTeam::find($teamId);
        if ($team) {
            $team->update([
                'current_latitude' => $validated['latitude'],
                'current_longitude' => $validated['longitude'],
                'location_updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cleaner live GPS location updated.',
            'location' => [
                'latitude' => $team->current_latitude,
                'longitude' => $team->current_longitude,
                'updated_at' => $team->location_updated_at?->toIso8601String(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'team_name' => 'required|string|max:128',
            'team_leader_id' => 'nullable|exists:users,id',
            'phone' => 'nullable|string|max:32',
            'vehicle_plate' => 'nullable|string|max:64',
            'status' => 'nullable|string|in:active,on_job,off_duty',
            'notes' => 'nullable|string',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        $team = CleaningTeam::create([
            'team_name' => $validated['team_name'],
            'team_leader_id' => $validated['team_leader_id'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'vehicle_plate' => $validated['vehicle_plate'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        if (!empty($validated['member_ids'])) {
            foreach ($validated['member_ids'] as $uid) {
                TeamMember::firstOrCreate([
                    'cleaning_team_id' => $team->id,
                    'user_id' => $uid,
                ], [
                    'role_in_team' => ($uid == ($validated['team_leader_id'] ?? null)) ? 'leader' : 'cleaner',
                ]);
            }
        } elseif (!empty($validated['team_leader_id'])) {
            TeamMember::firstOrCreate([
                'cleaning_team_id' => $team->id,
                'user_id' => $validated['team_leader_id'],
            ], [
                'role_in_team' => 'leader',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'የፅዳት ቡድኑ በተሳካ ሁኔታ ተፈጥሯል::',
            'team' => $team->load(['leader:id,name,phone', 'members.user:id,name,phone']),
        ], 201);
    }

    public function getEmployees(Request $request): JsonResponse
    {
        $role = $request->query('role');
        $query = \App\Models\User::query()->select('id', 'name', 'email', 'phone', 'role', 'is_active', 'created_at');
        if ($role) {
            $query->where('role', $role);
        }
        $employees = $query->orderBy('name')->get();
        return response()->json($employees);
    }

    public function storeEmployee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:64|unique:users,username',
            'email' => 'nullable|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:32',
            'role' => 'required|string|in:owner,reception,cleaner,sales,finance',
            'password' => 'required|string|min:6',
        ]);

        $username = trim($validated['username'] ?? \Illuminate\Support\Str::slug($validated['name'], '_'));
        if (empty($username)) {
            $username = 'user_' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));
        }

        // Ensure username is strictly unique
        $baseUsername = $username;
        $counter = 1;
        while (\App\Models\User::where('username', $username)->exists()) {
            $username = $baseUsername . '_' . $counter++;
        }

        $email = $validated['email'] ?? ($username . '@meash.internal');
        $baseEmail = $email;
        $counter = 1;
        while (\App\Models\User::where('email', $email)->exists()) {
            $email = str_replace('@', "_{$counter}@", $baseEmail);
            $counter++;
        }

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'username' => $username,
            'email' => $email,
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'አዲሱ ሰራተኛ በተሳካ ሁኔታ ተመዝግቧል::',
            'employee' => $user,
        ], 201);
    }

    /**
     * Get single employee complete dossier: Monthly paid history & work history
     */
    public function getEmployeeHistory(Request $request, $id): JsonResponse
    {
        $employee = \App\Models\User::findOrFail($id);

        // 1. Monthly Paid / Payroll History from Expenses
        $payments = \App\Models\Expense::where('category', 'employee_payments')
            ->where(function ($q) use ($employee) {
                $q->where('description', 'like', "%{$employee->name}%")
                  ->orWhere('description', 'like', "%{$employee->username}%")
                  ->orWhere('reference_number', 'like', "%PAY-EMP{$employee->id}%");
            })
            ->orderByDesc('date')
            ->get()
            ->map(function ($p) {
                // Extract month name if present
                $monthMatch = '';
                if (preg_match('/\(([^)]+)\)/', $p->description, $matches)) {
                    $monthMatch = $matches[1];
                }
                $ethMonth = '';
                try {
                    if ($p->date) {
                        $eth = \App\Services\EthiopianCalendarService::toEthiopian($p->date);
                        $ethMonth = $eth['month_name_am'] ?? '';
                    }
                } catch (\Throwable $e) {}

                return [
                    'id' => $p->id,
                    'expense_number' => $p->expense_number,
                    'amount' => (float)$p->amount,
                    'date' => $p->date ? $p->date->format('Y-m-d') : '',
                    'month' => $monthMatch ?: $ethMonth,
                    'description' => $p->description,
                    'reference_number' => $p->reference_number,
                ];
            });

        $totalPaid = $payments->sum('amount');

        // 2. Teams and Work History
        $memberTeamIds = \App\Models\TeamMember::where('user_id', $employee->id)->pluck('cleaning_team_id')->toArray();
        $leaderTeamIds = \App\Models\CleaningTeam::where('leader_id', $employee->id)->pluck('id')->toArray();
        $allTeamIds = array_unique(array_merge($memberTeamIds, $leaderTeamIds));

        $teams = \App\Models\CleaningTeam::whereIn('id', $allTeamIds)->get();

        $orders = \App\Models\Order::whereIn('cleaning_team_id', $allTeamIds)
            ->with(['customer:id,full_name,phone,subcity', 'items.service:id,name_en,name_am'])
            ->orderByDesc('appointment_date')
            ->limit(50)
            ->get()
            ->map(function ($o) {
                return [
                    'id' => $o->id,
                    'order_number' => $o->order_number,
                    'customer_name' => $o->customer?->full_name ?: 'ደንበኛ',
                    'customer_phone' => $o->customer?->phone ?: '',
                    'subcity' => $o->customer?->subcity ?: '',
                    'status' => $o->order_status,
                    'total' => (float)$o->total,
                    'appointment_date' => $o->appointment_date ? $o->appointment_date->format('Y-m-d') : '',
                    'services' => $o->items->map(fn($it) => $it->service?->name_am ?: $it->service?->name_en)->filter()->implode(', '),
                ];
            });

        $completedJobsCount = $orders->where('status', 'completed')->count();
        $activeJobsCount = $orders->whereIn('status', ['confirmed', 'in_progress'])->count();

        return response()->json([
            'success' => true,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'username' => $employee->username,
                'phone' => $employee->phone,
                'email' => $employee->email,
                'role' => $employee->role,
                'is_active' => (bool)$employee->is_active,
                'created_at' => $employee->created_at ? $employee->created_at->format('Y-m-d') : '',
            ],
            'teams' => $teams->map(fn($t) => ['id' => $t->id, 'name' => $t->team_name]),
            'payments' => $payments,
            'total_paid' => $totalPaid,
            'work_history' => $orders,
            'completed_jobs_count' => $completedJobsCount,
            'active_jobs_count' => $activeJobsCount,
        ]);
    }
}
