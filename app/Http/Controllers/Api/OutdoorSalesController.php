<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Proforma;
use App\Models\ProformaItem;
use App\Models\SalesVisit;
use App\Services\EthiopianCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutdoorSalesController extends Controller
{
    public function organizations(Request $request): JsonResponse
    {
        $query = Organization::withCount(['visits', 'proformas', 'contracts']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('industry', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $orgs = $query->latest()->paginate($request->input('per_page', 20));
        return response()->json($orgs);
    }

    public function storeOrganization(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'industry' => 'required|string|max:64',
            'address' => 'required|string',
            'phone' => 'required|string|max:32',
            'email' => 'nullable|email',
            'website' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['org_code'] = Organization::generateNextCode();
        $org = Organization::create($validated);

        return response()->json([
            'message' => 'Organization registered successfully',
            'organization' => $org,
        ], 201);
    }

    public function visits(Request $request): JsonResponse
    {
        $query = SalesVisit::with([
            'organization',
            'salesperson:id,name,phone',
            'proformas',
        ]);

        if ($stage = $request->input('stage')) {
            $query->where('stage', $stage);
        }

        if ($orgId = $request->input('organization_id')) {
            $query->where('organization_id', $orgId);
        }

        if ($request->input('followup_due') === 'today') {
            $query->whereDate('next_followup_date', Carbon::today());
        }

        $visits = $query->latest('visit_date')->paginate($request->input('per_page', 25));

        $daysAm = [
            0 => 'እሁድ',
            1 => 'ሰኞ',
            2 => 'ማክሰኞ',
            3 => 'ረቡዕ',
            4 => 'ሐሙስ',
            5 => 'አርብ',
            6 => 'ቅዳሜ',
        ];

        $visits->getCollection()->transform(function ($visit) use ($daysAm) {
            $carbonDate = Carbon::parse($visit->visit_date);
            $eth = EthiopianCalendarService::toEthiopian($visit->visit_date);
            $visit->day_of_week_am = $daysAm[$carbonDate->dayOfWeek] ?? '';
            $visit->eth_visit_date = $eth['formatted_am'];
            $visit->eth_visit_en = $eth['formatted_en'];
            if ($visit->next_followup_date) {
                $ethFollowup = EthiopianCalendarService::toEthiopian($visit->next_followup_date);
                $visit->eth_followup_date = $ethFollowup['formatted_am'];
            }
            return $visit;
        });

        return response()->json($visits);
    }

    public function storeVisit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'contact_person' => 'required|string|max:255',
            'contact_position' => 'nullable|string|max:128',
            'phone' => 'required|string|max:32',
            'address' => 'required|string',
            'services_introduced' => 'nullable|string',
            'visit_purpose' => 'nullable|string',
            'interest_level' => 'required|string|in:low,medium,high',
            'visit_date' => 'required|date',
            'notes' => 'nullable|string',
            'next_followup_date' => 'nullable|date',
            'stage' => 'nullable|string|in:new_lead,visited,contact_established,interested,proforma_requested,proforma_sent,negotiation,won,lost,followup_later',
        ]);

        $validated['visit_code'] = SalesVisit::generateNextCode();
        $validated['salesperson_user_id'] = $request->user()->id;
        $validated['stage'] = $validated['stage'] ?? 'visited';

        $visit = SalesVisit::create($validated);

        AuditLog::logAction(
            $request->user()->id,
            'sales_visit_logged',
            SalesVisit::class,
            $visit->id,
            null,
            $visit->toArray()
        );

        return response()->json([
            'message' => 'Sales visit recorded successfully',
            'visit' => $visit->load('organization', 'salesperson'),
        ], 201);
    }

    public function updateVisitStage(Request $request, SalesVisit $visit): JsonResponse
    {
        $validated = $request->validate([
            'stage' => 'required|string|in:new_lead,visited,contact_established,interested,proforma_requested,proforma_sent,negotiation,won,lost,followup_later',
            'notes' => 'nullable|string',
            'next_followup_date' => 'nullable|date',
        ]);

        $oldStage = $visit->stage;
        $visit->update($validated);

        // If stage is WON, ensure corporate customer record exists
        if ($validated['stage'] === 'won' && $oldStage !== 'won') {
            $org = $visit->organization;
            Customer::firstOrCreate(
                ['phone' => $org->phone],
                [
                    'customer_code' => Customer::generateNextCode(),
                    'full_name' => $org->name,
                    'customer_type' => 'corporate',
                    'address' => $org->address,
                    'notes' => "Converted from Outdoor Sales visit {$visit->visit_code}. Contact: {$visit->contact_person} ({$visit->contact_position})",
                ]
            );
        }

        AuditLog::logAction(
            $request->user()->id,
            'sales_stage_changed',
            SalesVisit::class,
            $visit->id,
            ['stage' => $oldStage],
            ['stage' => $visit->stage]
        );

        return response()->json([
            'message' => "Stage changed to {$visit->stage}",
            'visit' => $visit->fresh(['organization', 'salesperson']),
        ]);
    }

    public function pipeline(): JsonResponse
    {
        $stages = [
            'new_lead' => 'New Lead',
            'visited' => 'Visited',
            'contact_established' => 'Contact Established',
            'interested' => 'Interested',
            'proforma_requested' => 'Proforma Requested',
            'proforma_sent' => 'Proforma Sent',
            'negotiation' => 'Negotiation',
            'won' => 'Won / Contract',
            'lost' => 'Lost',
            'followup_later' => 'Follow-up Later',
        ];

        $allVisits = SalesVisit::with(['organization', 'salesperson:id,name'])->get();

        $pipeline = [];
        foreach ($stages as $key => $title) {
            $pipeline[$key] = [
                'stage_key' => $key,
                'title' => $title,
                'count' => $allVisits->where('stage', $key)->count(),
                'leads' => $allVisits->where('stage', $key)->values(),
            ];
        }

        return response()->json($pipeline);
    }

    public function proformas(): JsonResponse
    {
        $proformas = Proforma::with(['organization', 'preparedBy:id,name', 'items.service'])
            ->latest()
            ->paginate(20);

        return response()->json($proformas);
    }

    public function storeProforma(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'sales_visit_id' => 'nullable|exists:sales_visits,id',
            'validity_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'nullable|exists:services,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $proforma = Proforma::create([
                'proforma_number' => Proforma::generateNextNumber(),
                'organization_id' => $validated['organization_id'],
                'sales_visit_id' => $validated['sales_visit_id'] ?? null,
                'prepared_by_user_id' => $request->user()->id,
                'subtotal' => 0,
                'discount' => $validated['discount'] ?? 0,
                'tax' => $validated['tax'] ?? 0,
                'total' => 0,
                'validity_date' => $validated['validity_date'] ?? Carbon::today()->addDays(30),
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                ProformaItem::create([
                    'proforma_id' => $proforma->id,
                    'service_id' => $item['service_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => (float)$item['quantity'] * (float)$item['unit_price'],
                ]);
            }

            // Update visit stage if applicable
            if ($proforma->sales_visit_id) {
                SalesVisit::where('id', $proforma->sales_visit_id)->update(['stage' => 'proforma_sent']);
            }

            return response()->json([
                'message' => 'Proforma created successfully',
                'proforma' => $proforma->fresh(['items', 'organization', 'preparedBy']),
            ], 201);
        });
    }

    public function contracts(): JsonResponse
    {
        $contracts = Contract::with(['organization', 'responsiblePerson:id,name', 'proforma'])
            ->latest('start_date')
            ->paginate(20);

        return response()->json($contracts);
    }

    public function storeContract(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'proforma_id' => 'nullable|exists:proformas,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'agreed_value' => 'required|numeric|min:0',
            'cleaning_frequency' => 'required|string|in:daily,weekly,biweekly,monthly',
            'terms' => 'nullable|string',
            'responsible_person_id' => 'nullable|exists:users,id',
        ]);

        $validated['contract_number'] = Contract::generateNextNumber();
        $validated['status'] = 'active';

        $contract = Contract::create($validated);

        return response()->json([
            'message' => 'Contract registered successfully',
            'contract' => $contract->load('organization', 'responsiblePerson'),
        ], 201);
    }
}
