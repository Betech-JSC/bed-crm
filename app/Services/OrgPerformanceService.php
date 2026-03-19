<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Team;
use App\Models\EmployeeProfile;
use App\Models\OrgCostEntry;
use App\Models\Deal;
use Illuminate\Support\Facades\DB;

class OrgPerformanceService
{
    /**
     * Get revenue by department for a given period
     */
    public function revenueByDepartment(int $accountId, string $periodStart, string $periodEnd): array
    {
        $departments = Department::where('account_id', $accountId)->where('is_active', true)->get();

        return $departments->map(function ($dept) use ($periodStart, $periodEnd) {
            $employeeUserIds = EmployeeProfile::where('department_id', $dept->id)
                ->where('status', 'active')
                ->pluck('user_id');

            $revenue = Deal::whereIn('user_id', $employeeUserIds)
                ->where('status', 'won')
                ->whereBetween('updated_at', [$periodStart, $periodEnd])
                ->sum('value');

            return [
                'department_id' => $dept->id,
                'department_name' => $dept->name,
                'department_code' => $dept->code,
                'color' => $dept->color,
                'revenue' => (float) $revenue,
                'headcount' => $employeeUserIds->count(),
                'revenue_per_head' => $employeeUserIds->count() > 0
                    ? round($revenue / $employeeUserIds->count(), 2) : 0,
            ];
        })->sortByDesc('revenue')->values()->toArray();
    }

    /**
     * Get revenue by team
     */
    public function revenueByTeam(int $accountId, string $periodStart, string $periodEnd): array
    {
        $teams = Team::where('account_id', $accountId)->where('is_active', true)->with('department')->get();

        return $teams->map(function ($team) use ($periodStart, $periodEnd) {
            $memberIds = EmployeeProfile::where('team_id', $team->id)
                ->where('status', 'active')
                ->pluck('user_id');

            $revenue = Deal::whereIn('user_id', $memberIds)
                ->where('status', 'won')
                ->whereBetween('updated_at', [$periodStart, $periodEnd])
                ->sum('value');

            return [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'department_name' => $team->department?->name,
                'color' => $team->color,
                'revenue' => (float) $revenue,
                'headcount' => $memberIds->count(),
                'revenue_per_head' => $memberIds->count() > 0
                    ? round($revenue / $memberIds->count(), 2) : 0,
            ];
        })->sortByDesc('revenue')->values()->toArray();
    }

    /**
     * Get cost breakdown by department
     */
    public function costByDepartment(int $accountId, string $periodLabel): array
    {
        return OrgCostEntry::where('account_id', $accountId)
            ->where('period_label', $periodLabel)
            ->whereNotNull('department_id')
            ->select('department_id', DB::raw('SUM(amount) as total_cost'))
            ->with('department:id,name,code,color')
            ->groupBy('department_id')
            ->get()
            ->map(fn ($row) => [
                'department_id' => $row->department_id,
                'department_name' => $row->department?->name,
                'department_code' => $row->department?->code,
                'color' => $row->department?->color,
                'total_cost' => (float) $row->total_cost,
            ])
            ->sortByDesc('total_cost')
            ->values()
            ->toArray();
    }

    /**
     * Get cost breakdown by category
     */
    public function costByCategory(int $accountId, string $periodLabel): array
    {
        return OrgCostEntry::where('account_id', $accountId)
            ->where('period_label', $periodLabel)
            ->select('category', DB::raw('SUM(amount) as total_cost'))
            ->groupBy('category')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'category_label' => OrgCostEntry::CATEGORIES[$row->category] ?? $row->category,
                'total_cost' => (float) $row->total_cost,
            ])
            ->sortByDesc('total_cost')
            ->values()
            ->toArray();
    }

    /**
     * Calculate profit per team (Revenue - Cost)
     */
    public function profitByTeam(int $accountId, string $periodStart, string $periodEnd, string $periodLabel): array
    {
        $teamRevenues = collect($this->revenueByTeam($accountId, $periodStart, $periodEnd))
            ->keyBy('team_id');

        $teamCosts = OrgCostEntry::where('account_id', $accountId)
            ->where('period_label', $periodLabel)
            ->whereNotNull('team_id')
            ->select('team_id', DB::raw('SUM(amount) as total_cost'))
            ->groupBy('team_id')
            ->pluck('total_cost', 'team_id');

        $teams = Team::where('account_id', $accountId)->where('is_active', true)->with('department')->get();

        return $teams->map(function ($team) use ($teamRevenues, $teamCosts) {
            $revenue = $teamRevenues->get($team->id)['revenue'] ?? 0;
            $cost = (float) ($teamCosts->get($team->id) ?? 0);
            $profit = $revenue - $cost;
            $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;

            return [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'department_name' => $team->department?->name,
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $profit,
                'margin' => $margin,
                'headcount' => $team->activeMembers()->count(),
            ];
        })->sortByDesc('profit')->values()->toArray();
    }

    /**
     * Get individual performance ranking
     */
    public function employeePerformanceRanking(int $accountId, string $periodStart, string $periodEnd, ?int $departmentId = null): array
    {
        $query = EmployeeProfile::where('account_id', $accountId)
            ->where('status', 'active')
            ->with(['user', 'departmentRelation', 'team']);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->get();

        return $employees->map(function ($emp) use ($periodStart, $periodEnd) {
            $deals = Deal::where('user_id', $emp->user_id)
                ->where('status', 'won')
                ->whereBetween('updated_at', [$periodStart, $periodEnd]);

            $revenue = $deals->sum('value');
            $dealCount = $deals->count();

            // Get latest KPI achievement
            $latestKpi = $emp->kpiValues()
                ->where('period_start', '>=', $periodStart)
                ->where('period_end', '<=', $periodEnd)
                ->avg(DB::raw('CASE WHEN target > 0 THEN (value / target * 100) ELSE 0 END'));

            return [
                'employee_id' => $emp->id,
                'user_id' => $emp->user_id,
                'name' => $emp->user?->name,
                'position' => $emp->position,
                'department' => $emp->departmentRelation?->name ?? $emp->department,
                'team' => $emp->team?->name,
                'revenue' => (float) $revenue,
                'deals_closed' => $dealCount,
                'kpi_achievement' => round($latestKpi ?? 0, 1),
                'performance_score' => $this->calculatePerformanceScore($revenue, $dealCount, $latestKpi),
            ];
        })->sortByDesc('performance_score')->values()->toArray();
    }

    private function calculatePerformanceScore(float $revenue, int $deals, ?float $kpiAchievement): int
    {
        $score = 0;
        // Revenue weight: 40%
        if ($revenue > 0) $score += min(40, ($revenue / 100000000) * 40); // Normalize to 100M
        // Deals weight: 30%
        $score += min(30, $deals * 3);
        // KPI weight: 30%
        $score += min(30, ($kpiAchievement ?? 0) * 0.3);
        return (int) min(100, round($score));
    }
}
