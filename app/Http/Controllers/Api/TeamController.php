<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CleaningTeam;
use App\Models\Complaint;
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
    public function index(): JsonResponse
    {
        $teams = CleaningTeam::with(['leader:id,name,phone', 'members.user:id,name,phone'])
            ->withCount(['orders as active_jobs_count' => function ($q) {
                $q->whereIn('order_status', ['assigned', 'on_the_way', 'cleaning']);
            }])
            ->get();

        return response()->json($teams);
    }

    public function myJobs(Request $request): JsonResponse
    {
        $user = $request->user();

        // Allow explicit team selection (e.g. from UI team dropdown or role switcher)
        $explicitTeamId = $request->query('team_id');
        if ($explicitTeamId && CleaningTeam::where('id', $explicitTeamId)->exists()) {
            $teamId = $explicitTeamId;
        } else {
            // Find which team this cleaner belongs to
            $member = TeamMember::where('user_id', $user->id)->first();
            $leaderTeam = CleaningTeam::where('team_leader_id', $user->id)->first();
            $teamId = $member?->cleaning_team_id ?? $leaderTeam?->id;
        }

        if (!$teamId && ($user->isOwner() || $user->isReception())) {
            // Default to first cleaning team for owner/reception inspection
            $firstTeam = CleaningTeam::first();
            $teamId = $firstTeam?->id;
        }

        $allTeams = CleaningTeam::all(['id', 'team_name', 'phone', 'status']);

        if (!$teamId) {
            return response()->json([
                'team' => null,
                'teams_list' => $allTeams,
                'today_jobs' => [],
                'upcoming_jobs' => [],
                'completed_today' => [],
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

        $todayJobs = (clone $jobsQuery)
            ->whereDate('appointment_date', $today)
            ->whereIn('order_status', ['assigned', 'on_the_way', 'cleaning'])
            ->orderBy('appointment_time_slot')
            ->get();

        $completedToday = (clone $jobsQuery)
            ->whereDate('appointment_date', $today)
            ->where('order_status', 'completed')
            ->latest('completed_at')
            ->get();

        $upcomingJobs = (clone $jobsQuery)
            ->whereDate('appointment_date', '>', $today)
            ->whereIn('order_status', ['confirmed', 'assigned'])
            ->orderBy('appointment_date')
            ->limit(10)
            ->get();

        $formatJob = function ($order) {
            $eth = EthiopianCalendarService::toEthiopian($order->appointment_date);
            $order->eth_date = $eth['formatted_am'];
            $order->eth_date_en = $eth['formatted_en'];
            return $order;
        };

        return response()->json([
            'team' => $team,
            'teams_list' => $allTeams,
            'today_jobs' => $todayJobs->map($formatJob),
            'completed_today' => $completedToday->map($formatJob),
            'upcoming_jobs' => $upcomingJobs->map($formatJob),
        ]);
    }

    public function handleJobAction(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:start,complete,on_the_way,report_problem',
            'notes' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'problem_category' => 'nullable|string',
            'problem_description' => 'nullable|string',
        ]);

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
            if (!empty($validated['notes'])) {
                $order->completion_notes = $validated['notes'];
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

        $teamId = $validated['team_id'];
        if (!$teamId) {
            $user = $request->user();
            $member = TeamMember::where('user_id', $user->id)->first();
            $leaderTeam = CleaningTeam::where('team_leader_id', $user->id)->first();
            $teamId = $member?->cleaning_team_id ?? $leaderTeam?->id;
        }

        if (!$teamId) {
            return response()->json(['message' => 'No active cleaning team associated with user.'], 422);
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
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:32',
            'role' => 'required|string|in:owner,reception,cleaner,sales,finance',
            'password' => 'required|string|min:6',
        ]);

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
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
}
