<?php

namespace App\Http\Controllers;

use App\Models\OrgObjective;
use App\Models\OrgKeyResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OrgObjectivesController extends Controller
{
    /**
     * GET /org-objectives — OKR Dashboard
     */
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;
        $periodLabel = $request->get('period', now()->format('Y') . '-Q' . ceil(now()->month / 3));
        $level = $request->get('level');

        $query = OrgObjective::where('account_id', $accountId)
            ->with(['owner', 'department', 'team', 'keyResults.owner', 'children', 'parent']);

        if ($periodLabel) {
            $query->where('period_label', $periodLabel);
        }

        if ($level) {
            $query->where('level', $level);
        } else {
            $query->whereNull('parent_id'); // Show top-level only
        }

        $objectives = $query->orderByDesc('created_at')->get();

        // Summary stats
        $allObjectives = OrgObjective::where('account_id', $accountId)
            ->where('period_label', $periodLabel)
            ->get();

        $stats = [
            'total' => $allObjectives->count(),
            'on_track' => $allObjectives->where('health', 'on_track')->count(),
            'at_risk' => $allObjectives->where('health', 'at_risk')->count(),
            'behind' => $allObjectives->where('health', 'behind')->count(),
            'completed' => $allObjectives->where('health', 'completed')->count(),
            'avg_progress' => round($allObjectives->avg('progress'), 1),
        ];

        return Inertia::render('OrgObjectives/Index', [
            'objectives' => $objectives,
            'stats' => $stats,
            'filters' => [
                'period' => $periodLabel,
                'level' => $level,
            ],
        ]);
    }

    /**
     * POST /org-objectives — Create objective
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'required|in:company,department,team,individual',
            'parent_id' => 'nullable|exists:org_objectives,id',
            'department_id' => 'nullable|exists:departments,id',
            'team_id' => 'nullable|exists:teams,id',
            'owner_user_id' => 'nullable|exists:users,id',
            'period' => 'required|string|max:20',
            'period_label' => 'required|string|max:20',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'weight' => 'nullable|integer|min:1|max:100',
            'key_results' => 'nullable|array',
            'key_results.*.title' => 'required|string',
            'key_results.*.metric_type' => 'required|in:number,percentage,currency,boolean',
            'key_results.*.start_value' => 'nullable|numeric',
            'key_results.*.target_value' => 'required|numeric',
            'key_results.*.unit' => 'nullable|string|max:30',
            'key_results.*.weight' => 'nullable|integer',
        ]);

        $validated['account_id'] = Auth::user()->account_id;
        $validated['created_by'] = Auth::id();
        $validated['status'] = 'active';

        $keyResultsData = $validated['key_results'] ?? [];
        unset($validated['key_results']);

        $objective = OrgObjective::create($validated);

        foreach ($keyResultsData as $kr) {
            $objective->keyResults()->create($kr);
        }

        return back()->with('success', 'Objective created.');
    }

    /**
     * PUT /org-objectives/{objective}
     */
    public function update(Request $request, OrgObjective $orgObjective): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'required|in:company,department,team,individual',
            'department_id' => 'nullable|exists:departments,id',
            'team_id' => 'nullable|exists:teams,id',
            'owner_user_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'weight' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|in:draft,active,completed,cancelled',
        ]);

        $orgObjective->update($validated);
        return back()->with('success', 'Objective updated.');
    }

    /**
     * POST /org-key-results/{keyResult}/check-in
     */
    public function checkIn(Request $request, OrgKeyResult $keyResult): RedirectResponse
    {
        $validated = $request->validate([
            'value' => 'required|numeric',
            'note' => 'nullable|string',
        ]);

        $keyResult->updateProgress($validated['value'], $validated['note'] ?? null);

        return back()->with('success', 'Key result updated.');
    }

    /**
     * POST /org-key-results — Add key result to objective
     */
    public function storeKeyResult(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'org_objective_id' => 'required|exists:org_objectives,id',
            'title' => 'required|string|max:255',
            'metric_type' => 'required|in:number,percentage,currency,boolean',
            'start_value' => 'nullable|numeric',
            'target_value' => 'required|numeric',
            'unit' => 'nullable|string|max:30',
            'weight' => 'nullable|integer',
            'owner_user_id' => 'nullable|exists:users,id',
        ]);

        OrgKeyResult::create($validated);
        return back()->with('success', 'Key result added.');
    }

    /**
     * DELETE /org-objectives/{objective}
     */
    public function destroy(OrgObjective $orgObjective): RedirectResponse
    {
        $orgObjective->delete();
        return back()->with('success', 'Objective deleted.');
    }
}
