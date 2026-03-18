<?php

namespace App\Http\Controllers;

use App\Models\Initiative;
use App\Models\InitiativeTask;
use App\Models\KeyResult;
use App\Models\Objective;
use App\Models\StrategicPlan;
use App\Models\StrategicTheme;
use App\Services\StrategyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StrategyController extends Controller
{
    public function __construct(private StrategyService $strategy) {}

    // ════════════════════════════════════════════
    //  STRATEGY DASHBOARD
    // ════════════════════════════════════════════

    /**
     * Strategy cockpit page.
     */
    public function dashboard(): Response
    {
        $accountId = Auth::user()->account_id;
        $locale = app()->getLocale();

        $health = $this->strategy->getStrategyHealth($accountId);
        $stats = $this->strategy->getOKRStats($accountId);
        $alignment = $this->strategy->getAlignmentScore($accountId);
        $periods = $this->strategy->getAvailablePeriods($accountId);
        $dataSources = KeyResult::getDataSources();

        return Inertia::render('Strategy/Dashboard', [
            'health' => $health,
            'stats' => $stats,
            'alignment' => $alignment,
            'periods' => $periods,
            'data_sources' => $dataSources,
            'locale' => $locale,
        ]);
    }

    // ════════════════════════════════════════════
    //  STRATEGIC PLAN CRUD
    // ════════════════════════════════════════════

    public function storePlan(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'vision' => 'nullable|string',
            'mission' => 'nullable|string',
            'values' => 'nullable|array',
            'time_horizon_start' => 'nullable|date',
            'time_horizon_end' => 'nullable|date',
        ]);

        $plan = StrategicPlan::create([
            'account_id' => Auth::user()->account_id,
            'created_by' => Auth::id(),
            ...$request->only('title', 'vision', 'mission', 'values', 'time_horizon_start', 'time_horizon_end'),
        ]);

        return response()->json(['plan' => $plan], 201);
    }

    public function updatePlan(Request $request, StrategicPlan $plan): JsonResponse
    {
        $plan->update($request->only('title', 'vision', 'mission', 'values', 'status', 'time_horizon_start', 'time_horizon_end'));
        return response()->json(['plan' => $plan]);
    }

    // ════════════════════════════════════════════
    //  STRATEGIC THEMES
    // ════════════════════════════════════════════

    public function storeTheme(Request $request): JsonResponse
    {
        $request->validate([
            'strategic_plan_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
            'weight' => 'nullable|integer|min:0|max:100',
        ]);

        $theme = StrategicTheme::create([
            'account_id' => Auth::user()->account_id,
            ...$request->only('strategic_plan_id', 'name', 'description', 'color', 'icon', 'weight'),
        ]);

        return response()->json(['theme' => $theme], 201);
    }

    public function updateTheme(Request $request, StrategicTheme $theme): JsonResponse
    {
        $theme->update($request->only('name', 'description', 'color', 'icon', 'weight', 'status', 'sort_order'));
        return response()->json(['theme' => $theme]);
    }

    // ════════════════════════════════════════════
    //  OBJECTIVES (OKR)
    // ════════════════════════════════════════════

    /**
     * OKR tree page.
     */
    public function okrs(Request $request): Response
    {
        $accountId = Auth::user()->account_id;
        $period = $request->get('period');

        $tree = $this->strategy->getOKRTree($accountId, $period, Auth::user());
        $periods = $this->strategy->getAvailablePeriods($accountId);
        $stats = $this->strategy->getOKRStats($accountId, $period);

        $themes = StrategicTheme::where('account_id', $accountId)
            ->where('status', 'active')
            ->select('id', 'name', 'color')
            ->get();

        $users = \App\Models\User::where('account_id', $accountId)
            ->select('id', 'first_name', 'last_name', 'email')
            ->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);

        return Inertia::render('Strategy/OKRs', [
            'tree' => $tree,
            'periods' => $periods,
            'stats' => $stats,
            'themes' => $themes,
            'users' => $users,
            'current_period' => $period,
            'data_sources' => KeyResult::getDataSources(),
        ]);
    }

    public function storeObjective(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'level' => 'required|in:company,team,individual',
            'period' => 'nullable|string|max:20',
            'owner_id' => 'nullable|integer',
        ]);

        $objective = Objective::create([
            'account_id' => Auth::user()->account_id,
            'status' => 'active',
            ...$request->only('title', 'description', 'level', 'strategic_theme_id', 'parent_id', 'owner_id', 'team', 'period', 'period_start', 'period_end'),
        ]);

        return response()->json(['objective' => $objective], 201);
    }

    public function updateObjective(Request $request, Objective $objective): JsonResponse
    {
        $objective->update($request->only('title', 'description', 'level', 'strategic_theme_id', 'owner_id', 'team', 'status', 'confidence', 'period', 'period_start', 'period_end'));
        return response()->json(['objective' => $objective->fresh()]);
    }

    public function deleteObjective(Objective $objective): JsonResponse
    {
        $objective->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Cascade an objective to a child.
     */
    public function cascadeObjective(Request $request, Objective $objective): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'level' => 'required|in:team,individual',
            'owner_id' => 'nullable|integer',
        ]);

        $child = Objective::create([
            'account_id' => $objective->account_id,
            'parent_id' => $objective->id,
            'strategic_theme_id' => $objective->strategic_theme_id,
            'period' => $objective->period,
            'period_start' => $objective->period_start,
            'period_end' => $objective->period_end,
            'status' => 'active',
            ...$request->only('title', 'description', 'level', 'owner_id', 'team'),
        ]);

        return response()->json(['objective' => $child], 201);
    }

    // ════════════════════════════════════════════
    //  KEY RESULTS
    // ════════════════════════════════════════════

    public function storeKeyResult(Request $request, Objective $objective): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'target_value' => 'required|numeric',
            'metric_type' => 'sometimes|in:number,currency,percentage,boolean',
        ]);

        $kr = KeyResult::create([
            'account_id' => $objective->account_id,
            'objective_id' => $objective->id,
            'owner_id' => $request->owner_id ?? $objective->owner_id,
            ...$request->only('title', 'description', 'metric_type', 'start_value', 'target_value', 'unit', 'data_source', 'data_source_config', 'kpi_definition_id', 'weight'),
        ]);

        return response()->json(['key_result' => $kr], 201);
    }

    /**
     * Check-in: update KR current value.
     */
    public function checkIn(Request $request, KeyResult $keyResult): JsonResponse
    {
        $request->validate([
            'current_value' => 'required|numeric',
            'confidence' => 'nullable|integer|min:0|max:100',
        ]);

        $keyResult->checkIn(
            (float) $request->current_value,
            $request->confidence
        );

        return response()->json([
            'key_result' => $keyResult->fresh(),
            'objective' => $keyResult->objective->fresh(),
        ]);
    }

    // ════════════════════════════════════════════
    //  INITIATIVES
    // ════════════════════════════════════════════

    public function storeInitiative(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'objective_id' => 'nullable|integer',
        ]);

        $initiative = Initiative::create([
            'account_id' => Auth::user()->account_id,
            'owner_id' => $request->owner_id ?? Auth::id(),
            ...$request->only('title', 'description', 'objective_id', 'project_id', 'priority', 'start_date', 'due_date', 'budget'),
        ]);

        return response()->json(['initiative' => $initiative], 201);
    }

    public function updateInitiative(Request $request, Initiative $initiative): JsonResponse
    {
        $initiative->update($request->only('title', 'description', 'status', 'priority', 'start_date', 'due_date', 'budget', 'actual_cost', 'project_id'));
        return response()->json(['initiative' => $initiative->fresh()]);
    }

    // ════════════════════════════════════════════
    //  API: Real-time data
    // ════════════════════════════════════════════

    /**
     * API: Get OKR tree as JSON.
     */
    public function apiTree(Request $request): JsonResponse
    {
        $tree = $this->strategy->getOKRTree(
            Auth::user()->account_id,
            $request->get('period'),
            Auth::user()
        );
        return response()->json(['tree' => $tree]);
    }

    /**
     * API: Get strategy health data.
     */
    public function apiHealth(): JsonResponse
    {
        return response()->json($this->strategy->getStrategyHealth(Auth::user()->account_id));
    }

    /**
     * API: Refresh auto-tracked KRs.
     */
    public function apiRefresh(): JsonResponse
    {
        $updated = $this->strategy->refreshAutoTrackedKRs(Auth::user()->account_id);
        return response()->json(['updated' => $updated]);
    }

    /**
     * API: CRM-to-Goals alignment.
     */
    public function apiAlignment(): JsonResponse
    {
        return response()->json($this->strategy->getAlignmentScore(Auth::user()->account_id));
    }
}
