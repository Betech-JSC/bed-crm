<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadAssignmentRule;
use App\Services\SalesIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadAssignmentRulesController extends Controller
{
    public function __construct(private SalesIntelligenceService $service) {}

    /** GET /lead-assignment-rules */
    public function index(): JsonResponse
    {
        $rules = LeadAssignmentRule::where('account_id', Auth::user()->account_id)
            ->orderByDesc('priority')->get();
        return response()->json($rules);
    }

    /** POST /lead-assignment-rules */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'assignment_type' => 'required|in:round_robin,load_balance,score_based,territory',
            'conditions' => 'nullable|array',
            'assignees' => 'required|array|min:1',
            'assignees.*' => 'integer',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0',
        ]);
        $validated['account_id'] = Auth::user()->account_id;
        return response()->json(LeadAssignmentRule::create($validated), 201);
    }

    /** PUT /lead-assignment-rules/{rule} */
    public function update(Request $request, LeadAssignmentRule $rule): JsonResponse
    {
        $rule->update($request->only(['name', 'assignment_type', 'conditions', 'assignees', 'is_active', 'priority']));
        return response()->json($rule);
    }

    /** DELETE /lead-assignment-rules/{rule} */
    public function destroy(LeadAssignmentRule $rule): JsonResponse
    {
        $rule->delete();
        return response()->json(['deleted' => true]);
    }

    /** POST /lead-assignment-rules/{rule}/test — test a rule against a sample lead */
    public function test(Request $request, LeadAssignmentRule $rule): JsonResponse
    {
        $leadId = $request->integer('lead_id');
        $lead = Lead::where('account_id', Auth::user()->account_id)->find($leadId);
        if (!$lead) return response()->json(['error' => 'Lead not found'], 404);

        $assignedId = $this->service->autoAssignLead($lead);
        return response()->json(['assigned_to' => $assignedId, 'lead_id' => $leadId]);
    }
}
