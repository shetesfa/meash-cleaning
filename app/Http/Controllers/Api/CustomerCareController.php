<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Feedback;
use App\Models\Followup;
use App\Services\EthiopianCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerCareController extends Controller
{
    public function followups(Request $request): JsonResponse
    {
        // Auto-create pending followups for completed orders that don't have one yet
        $completedOrdersWithoutFollowup = \App\Models\Order::where('order_status', 'completed')
            ->whereDoesntHave('followups')
            ->get();
        
        foreach ($completedOrdersWithoutFollowup as $cOrder) {
            Followup::create([
                'order_id' => $cOrder->id,
                'customer_id' => $cOrder->customer_id,
                'due_date' => Carbon::today(),
                'status' => 'pending',
                'notes' => 'Post-service follow-up call',
            ]);
        }

        $query = Followup::with([
            'order:id,order_number,total,appointment_date',
            'order.items.service:id,name_en,name_am',
            'customer:id,customer_code,full_name,phone,subcity',
            'handledBy:id,name',
        ]);

        if ($request->input('filter') === 'due_today') {
            $query->whereDate('due_date', '<=', Carbon::today())
                  ->where('status', 'pending');
        } elseif ($request->has('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        } elseif (!$request->has('status') && !$request->has('filter')) {
            // Default to pending follow-up queue only so completed ones leave the queue!
            $query->where('status', 'pending');
        }

        $followups = $query->latest('due_date')->paginate(20);

        $followups->getCollection()->transform(function ($item) {
            $eth = EthiopianCalendarService::toEthiopian($item->due_date);
            $item->eth_due_date = $eth['formatted_am'];
            return $item;
        });

        return response()->json($followups);
    }

    public function updateFollowup(Request $request, Followup $followup): JsonResponse
    {
        $status = $request->input('status', 'completed');
        $rating = $request->input('rating') ?? $request->input('satisfaction_score') ?? 5;
        $notes = $request->input('notes') ?? $request->input('feedback_notes');
        
        $outcome = $request->input('outcome');
        if (!$outcome) {
            $outcome = ($rating >= 4) ? 'satisfied' : (($rating <= 2) ? 'complaint' : 'satisfied');
        }

        $followup->update([
            'status' => 'completed',
            'outcome' => $outcome,
            'notes' => $notes,
            'handled_by_user_id' => $request->user()?->id,
            'completed_at' => now(),
        ]);

        // Record feedback into customer's profile history
        Feedback::create([
            'order_id' => $followup->order_id,
            'customer_id' => $followup->customer_id,
            'rating' => (int) $rating,
            'comment' => $notes ?: 'Customer feedback recorded via post-service follow-up call.',
            'source' => 'phone',
            'is_approved' => true,
        ]);

        if ($outcome === 'complaint' || $rating <= 2) {
            Complaint::create([
                'complaint_number' => Complaint::generateNextNumber(),
                'order_id' => $followup->order_id,
                'customer_id' => $followup->customer_id,
                'category' => 'followup_escalation',
                'description' => $notes ?: 'Customer raised concern during follow-up call.',
                'priority' => 'high',
                'status' => 'new',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'የክትትል ጥሪው እና የደንበኛው አስተያየት በተሳካ ሁኔታ ተመዝግቧል!',
            'followup' => $followup->fresh(['customer', 'order']),
        ]);
    }

    public function feedbacks(Request $request): JsonResponse
    {
        $query = Feedback::with([
            'order:id,order_number',
            'customer:id,customer_code,full_name,phone',
        ]);

        $feedbacks = $query->latest()->paginate(20);
        return response()->json($feedbacks);
    }

    public function storeFeedback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'customer_id' => 'required|exists:customers,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'source' => 'nullable|string|in:web,telegram,phone',
        ]);

        $feedback = Feedback::create($validated);

        return response()->json([
            'message' => 'Feedback saved successfully',
            'feedback' => $feedback->load('customer'),
        ], 201);
    }
    public function approveFeedback(Request $request, Feedback $feedback): JsonResponse
    {
        $validated = $request->validate([
            'is_approved' => 'required|boolean',
        ]);

        $feedback->update(['is_approved' => $validated['is_approved']]);

        return response()->json([
            'success' => true,
            'message' => $feedback->is_approved ? 'አስተያየቱ በይፋዊ ድረ-ገጹ ላይ እንዲታይ ተፈቅዷል' : 'አስተያየቱ ከድረ-ገጹ እንዲደበቅ ተደርጓል',
            'feedback' => $feedback,
        ]);
    }

    public function complaints(Request $request): JsonResponse
    {
        $query = Complaint::with([
            'customer:id,customer_code,full_name,phone,address',
            'order:id,order_number,total,appointment_date',
            'assignedTo:id,name',
        ]);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        $complaints = $query->latest()->paginate(20);
        return response()->json($complaints);
    }

    public function storeComplaint(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'nullable|exists:orders,id',
            'category' => 'required|string|max:64',
            'description' => 'required|string',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'assigned_to_user_id' => 'nullable|exists:users,id',
        ]);

        $validated['complaint_number'] = Complaint::generateNextNumber();
        $validated['priority'] = $validated['priority'] ?? 'medium';
        $validated['status'] = 'new';

        $complaint = Complaint::create($validated);

        AuditLog::logAction(
            $request->user()?->id,
            'complaint_filed',
            Complaint::class,
            $complaint->id,
            null,
            $complaint->toArray()
        );

        return response()->json([
            'message' => 'Complaint logged successfully',
            'complaint' => $complaint->load('customer', 'order'),
        ], 201);
    }

    public function resolveComplaint(Request $request, Complaint $complaint): JsonResponse
    {
        $validated = $request->validate([
            'resolution_notes' => 'required|string',
            'status' => 'nullable|string|in:in_progress,resolved,closed',
        ]);

        $old = $complaint->toArray();

        $status = $validated['status'] ?? 'resolved';
        $complaint->update([
            'resolution_notes' => $validated['resolution_notes'],
            'status' => $status,
            'resolved_at' => in_array($status, ['resolved', 'closed']) ? now() : null,
            'assigned_to_user_id' => $request->user()->id,
        ]);

        AuditLog::logAction(
            $request->user()->id,
            'complaint_resolved',
            Complaint::class,
            $complaint->id,
            $old,
            $complaint->toArray()
        );

        return response()->json([
            'message' => 'Complaint updated successfully',
            'complaint' => $complaint->fresh(['customer', 'assignedTo']),
        ]);
    }
}
