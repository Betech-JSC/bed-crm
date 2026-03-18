<?php

namespace App\Http\Controllers;

use App\Models\EmployeeProfile;
use App\Models\KpiDefinition;
use App\Models\KpiValue;
use App\Models\PerformanceReview;
use App\Models\User;
use App\Services\TeamPerformanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class TeamPerformanceController extends Controller
{
    public function __construct(private TeamPerformanceService $performanceService) {}

    /**
     * Team Performance Dashboard.
     */
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;
        $period = $request->get('period', now()->format('Y-m'));

        $analytics = $this->performanceService->getDashboardAnalytics($accountId, $period);

        return Inertia::render('HR/Dashboard', [
            'analytics' => $analytics,
            'filters' => ['period' => $period],
            'departments' => EmployeeProfile::DEPARTMENTS,
            'periods' => $this->getAvailablePeriods($accountId),
        ]);
    }

    /**
     * Employee list / manage employees.
     */
    public function employees(Request $request): Response
    {
        $accountId = Auth::user()->account_id;

        $employees = EmployeeProfile::where('account_id', $accountId)
            ->with(['user:id,first_name,last_name,email'])
            ->when($request->search, fn ($q, $s) => $q->whereHas('user', fn ($uq) => $uq->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")))
            ->when($request->department, fn ($q, $d) => $q->where('department', $d))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($emp) => [
                'id' => $emp->id,
                'user_id' => $emp->user_id,
                'name' => $emp->user?->name ?? 'Unknown',
                'email' => $emp->user?->email ?? '',
                'department' => $emp->department,
                'position' => $emp->position,
                'hire_date' => $emp->hire_date?->format('Y-m-d'),
                'tenure_months' => $emp->getTenureMonths(),
                'base_salary' => (float) $emp->base_salary,
                'hourly_rate' => (float) $emp->hourly_rate,
            ]);

        return Inertia::render('HR/Employees', [
            'employees' => $employees,
            'filters' => $request->only('search', 'department'),
            'departments' => EmployeeProfile::DEPARTMENTS,
            'availableUsers' => $this->getAvailableUsers($accountId),
        ]);
    }

    /**
     * Store a new employee profile.
     */
    public function storeEmployee(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'department' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:80',
            'hire_date' => 'nullable|date',
            'base_salary' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'target_hours_monthly' => 'nullable|integer|min:0',
        ]);

        Auth::user()->account->employeeProfiles()->create($v);

        return Redirect::back()->with('success', 'Employee profile created.');
    }

    /**
     * Update an employee profile.
     */
    public function updateEmployee(Request $request, EmployeeProfile $employee): RedirectResponse
    {
        $v = $request->validate([
            'department' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:80',
            'hire_date' => 'nullable|date',
            'base_salary' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'target_hours_monthly' => 'nullable|integer|min:0',
        ]);

        $employee->update($v);

        return Redirect::back()->with('success', 'Employee profile updated.');
    }

    /**
     * Delete an employee profile.
     */
    public function destroyEmployee(EmployeeProfile $employee): RedirectResponse
    {
        $employee->delete();
        return Redirect::back()->with('success', 'Employee profile deleted.');
    }

    /**
     * KPI Definitions management.
     */
    public function kpiDefinitions(Request $request): Response
    {
        $accountId = Auth::user()->account_id;

        $kpis = KpiDefinition::where('account_id', $accountId)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn ($kpi) => [
                'id' => $kpi->id,
                'name' => $kpi->name,
                'description' => $kpi->description,
                'unit' => $kpi->unit,
                'period' => $kpi->period,
                'category' => $kpi->category,
                'target_value' => (float) $kpi->target_value,
                'higher_is_better' => $kpi->higher_is_better,
                'is_active' => $kpi->is_active,
            ]);

        return Inertia::render('HR/KpiDefinitions', [
            'kpis' => $kpis,
            'units' => KpiDefinition::getUnits(),
            'periods' => KpiDefinition::getPeriods(),
            'categories' => KpiDefinition::CATEGORIES,
        ]);
    }

    /**
     * Store a KPI definition.
     */
    public function storeKpiDefinition(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'unit' => 'required|string|max:30',
            'period' => 'required|string|max:20',
            'category' => 'required|string|max:30',
            'target_value' => 'nullable|numeric|min:0',
            'higher_is_better' => 'boolean',
            'is_active' => 'boolean',
        ]);

        Auth::user()->account->kpiDefinitions()->create($v);

        return Redirect::back()->with('success', 'KPI definition created.');
    }

    /**
     * Update a KPI definition.
     */
    public function updateKpiDefinition(Request $request, KpiDefinition $kpiDefinition): RedirectResponse
    {
        $v = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'unit' => 'sometimes|string|max:30',
            'period' => 'sometimes|string|max:20',
            'category' => 'sometimes|string|max:30',
            'target_value' => 'nullable|numeric|min:0',
            'higher_is_better' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $kpiDefinition->update($v);

        return Redirect::back()->with('success', 'KPI definition updated.');
    }

    /**
     * Delete a KPI definition.
     */
    public function destroyKpiDefinition(KpiDefinition $kpiDefinition): RedirectResponse
    {
        $kpiDefinition->delete();
        return Redirect::back()->with('success', 'KPI definition deleted.');
    }

    /**
     * Employee detail / performance page.
     */
    public function employeeDetail(EmployeeProfile $employee): Response
    {
        $accountId = Auth::user()->account_id;

        $detail = $this->performanceService->getEmployeePerformanceDetail($employee, $accountId);

        $kpiDefinitions = KpiDefinition::where('account_id', $accountId)
            ->where('is_active', true)
            ->get(['id', 'name', 'unit', 'category', 'target_value', 'period']);

        return Inertia::render('HR/EmployeeDetail', [
            'detail' => $detail,
            'kpiDefinitions' => $kpiDefinitions,
            'ratings' => PerformanceReview::getRatings(),
            'allUsers' => $this->getAvailableUsers($accountId),
        ]);
    }

    /**
     * Store a KPI value for an employee.
     */
    public function storeKpiValue(Request $request, EmployeeProfile $employee): RedirectResponse
    {
        $v = $request->validate([
            'kpi_definition_id' => 'required|integer|exists:kpi_definitions,id',
            'value' => 'required|numeric',
            'target' => 'nullable|numeric',
            'period_label' => 'required|string|max:20',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'notes' => 'nullable|string|max:2000',
        ]);

        $employee->kpiValues()->create($v);

        return Redirect::back()->with('success', 'KPI value recorded.');
    }

    /**
     * Store a performance review for an employee.
     */
    public function storeReview(Request $request, EmployeeProfile $employee): RedirectResponse
    {
        $v = $request->validate([
            'period_label' => 'required|string|max:20',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'overall_score' => 'nullable|integer|min:0|max:100',
            'score_breakdown' => 'nullable|array',
            'rating' => 'nullable|string|max:20',
            'revenue_generated' => 'nullable|numeric|min:0',
            'deals_closed_value' => 'nullable|numeric|min:0',
            'deals_closed_count' => 'nullable|integer|min:0',
            'hours_logged' => 'nullable|numeric|min:0',
            'strengths' => 'nullable|string|max:5000',
            'improvements' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:5000',
        ]);

        $v['reviewed_by'] = Auth::id();

        $employee->reviews()->create($v);

        return Redirect::back()->with('success', 'Performance review saved.');
    }

    /**
     * Auto-calculate performance scores for an employee in a period.
     */
    public function calculateScore(Request $request, EmployeeProfile $employee)
    {
        $periodLabel = $request->get('period_label', now()->format('Y-m'));
        $accountId = Auth::user()->account_id;

        $scores = $this->performanceService->calculatePerformanceScore($employee, $periodLabel, $accountId);

        return response()->json($scores);
    }

    // -------- Private helpers --------

    private function getAvailableUsers(int $accountId): array
    {
        return User::where('account_id', $accountId)
            ->orderBy('first_name')
            ->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
            ->toArray();
    }

    private function getAvailablePeriods(int $accountId): array
    {
        $periods = [];
        $now = now();

        // Last 12 months
        for ($i = 0; $i < 12; $i++) {
            $date = $now->copy()->subMonths($i);
            $periods[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
            ];
        }

        // Last 4 quarters
        for ($i = 0; $i < 4; $i++) {
            $date = $now->copy()->subQuarters($i);
            $quarter = ceil($date->month / 3);
            $periods[] = [
                'value' => $date->format('Y') . '-Q' . $quarter,
                'label' => 'Q' . $quarter . ' ' . $date->format('Y'),
            ];
        }

        return $periods;
    }
}
