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
        $query = Followup::with([
            'order:id,order_number,total,appointment_date',
            'order.items.service:id,name_en,name_am',
            'customer:id,customer_code,full_name,phone,subcity',
            'handledBy:id,name',
        ]);

        if ($request->input('filter') === 'due_today') {
            $query->whereDate('due_date', '<=', Carbon::today())
                  ->where('status', 'pending');
        } elseif ($status = $request->input('status')) {
            $query->where('status', $status);
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
        $validated = $request->validate([
            'status' => 'required|string|in:pending,contacted,no_answer,completed',
            'outcome' => 'nullable|string|in:satisfied,dissatisfied,complaint,requested_service',
            'notes' => 'nullable|string',
        ]);

        $validated['handled_by_user_id'] = $request->user()->id;
        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        $followup->update($validated);

        // If customer expressed satisfaction, record a 5-star feedback
        if ($validated['outcome'] === 'satisfied') {
            Feedback::create([
                'order_id' => $followup->order_id,
                'customer_id' => $followup->customer_id,
                'rating' => 5,
                'comment' => $validated['notes'] ?? 'Customer expressed complete satisfaction during follow-up call.',
                'source' => 'phone',
            ]);
        } elseif ($validated['outcome'] === 'complaint') {
            Complaint::create([
                'complaint_number' => Complaint::generateNextNumber(),
                'order_id' => $followup->order_id,
                'customer_id' => $followup->customer_id,
                'category' => 'followup_escalation',
                'description' => $validated['notes'] ?? 'Customer raised concern during follow-up call.',
                'priority' => 'high',
                'status' => 'new',
            ]);
        }

        return response()->json([
            'message' => 'Follow-up recorded successfully',
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
