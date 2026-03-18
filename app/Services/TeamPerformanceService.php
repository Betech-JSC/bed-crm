<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\EmployeeProfile;
use App\Models\KpiDefinition;
use App\Models\KpiValue;
use App\Models\PerformanceReview;
use App\Models\ProjectResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TeamPerformanceService
{
    /**
     * Get team performance dashboard analytics.
     */
    public function getDashboardAnalytics(int $accountId, ?string $periodLabel = null): array
    {
        $periodLabel = $periodLabel ?: now()->format('Y-m');

        $employees = EmployeeProfile::where('account_id', $accountId)
            ->with(['user:id,first_name,last_name,email', 'kpiValues', 'reviews'])
            ->get();

        $kpiDefinitions = KpiDefinition::where('account_id', $accountId)
            ->where('is_active', true)
            ->get();

        return [
            'summary' => $this->getTeamSummary($employees, $periodLabel, $accountId),
            'employee_cards' => $this->getEmployeeCards($employees, $periodLabel, $accountId),
            'department_breakdown' => $this->getDepartmentBreakdown($employees, $periodLabel, $accountId),
            'kpi_overview' => $this->getKpiOverview($kpiDefinitions, $employees, $periodLabel),
            'top_performers' => $this->getTopPerformers($employees, $periodLabel, $accountId),
            'revenue_per_employee' => $this->getRevenuePerEmployee($employees, $accountId),
            'period' => $periodLabel,
        ];
    }

    /**
     * High-level team summary card data.
     */
    public function getTeamSummary(Collection $employees, string $periodLabel, int $accountId): array
    {
        $totalEmployees = $employees->count();

        // Overall KPI achievement for the period
        $kpiValues = KpiValue::whereIn('employee_profile_id', $employees->pluck('id'))
            ->where('period_label', $periodLabel)
            ->get();

        $avgAchievement = $kpiValues->count() > 0
            ? round($kpiValues->avg(fn ($kv) => $kv->getAchievement()), 1)
            : 0;

        // Revenue data from won deals
        $totalRevenue = $this->getPeriodRevenue($accountId, $periodLabel);
        $revenuePerEmployee = $totalEmployees > 0 ? round($totalRevenue / $totalEmployees, 0) : 0;

        // Hours utilization
        $totalTargetHours = $employees->sum('target_hours_monthly');
        $totalLoggedHours = $this->getPeriodLoggedHours($employees, $periodLabel);
        $utilization = $totalTargetHours > 0
            ? round(($totalLoggedHours / $totalTargetHours) * 100, 1)
            : 0;

        // Reviews this period
        $reviewsCount = PerformanceReview::whereIn('employee_profile_id', $employees->pluck('id'))
            ->where('period_label', $periodLabel)
            ->count();

        $avgScore = PerformanceReview::whereIn('employee_profile_id', $employees->pluck('id'))
            ->where('period_label', $periodLabel)
            ->avg('overall_score') ?? 0;

        return [
            'total_employees' => $totalEmployees,
            'avg_kpi_achievement' => $avgAchievement,
            'total_revenue' => $totalRevenue,
            'revenue_per_employee' => $revenuePerEmployee,
            'utilization' => $utilization,
            'total_target_hours' => $totalTargetHours,
            'total_logged_hours' => $totalLoggedHours,
            'reviews_count' => $reviewsCount,
            'avg_review_score' => round($avgScore, 1),
        ];
    }

    /**
     * Per-employee card data for the dashboard grid.
     */
    public function getEmployeeCards(Collection $employees, string $periodLabel, int $accountId): array
    {
        return $employees->map(function ($emp) use ($periodLabel, $accountId) {
            $kpiValues = $emp->kpiValues->where('period_label', $periodLabel);
            $avgAchievement = $kpiValues->count() > 0
                ? round($kpiValues->avg(fn ($kv) => $kv->getAchievement()), 1)
                : 0;

            $latestReview = $emp->reviews
                ->where('period_label', $periodLabel)
                ->first();

            // Revenue attributed to this employee (deals they closed)
            $employeeRevenue = Deal::where('account_id', $accountId)
                ->where('assigned_to', $emp->user_id)
                ->where('status', Deal::STATUS_WON)
                ->sum('value');

            return [
                'id' => $emp->id,
                'user_id' => $emp->user_id,
                'name' => $emp->user?->name ?? 'Unknown',
                'email' => $emp->user?->email ?? '',
                'department' => $emp->department,
                'position' => $emp->position,
                'hire_date' => $emp->hire_date?->format('Y-m-d'),
                'tenure_months' => $emp->getTenureMonths(),
                'base_salary' => (float) $emp->base_salary,
                'avg_kpi_achievement' => $avgAchievement,
                'kpi_values_count' => $kpiValues->count(),
                'latest_review_score' => $latestReview?->overall_score ?? null,
                'latest_review_rating' => $latestReview?->rating ?? null,
                'revenue_generated' => (float) $employeeRevenue,
            ];
        })->sortByDesc('avg_kpi_achievement')->values()->toArray();
    }

    /**
     * Department-level breakdown.
     */
    public function getDepartmentBreakdown(Collection $employees, string $periodLabel, int $accountId): array
    {
        $departments = [];

        foreach (EmployeeProfile::DEPARTMENTS as $key => $label) {
            $deptEmployees = $employees->where('department', $key);
            if ($deptEmployees->isEmpty()) continue;

            $deptKpis = KpiValue::whereIn('employee_profile_id', $deptEmployees->pluck('id'))
                ->where('period_label', $periodLabel)
                ->get();

            $avgAchievement = $deptKpis->count() > 0
                ? round($deptKpis->avg(fn ($kv) => $kv->getAchievement()), 1)
                : 0;

            $deptRevenue = Deal::where('account_id', $accountId)
                ->whereIn('assigned_to', $deptEmployees->pluck('user_id'))
                ->where('status', Deal::STATUS_WON)
                ->sum('value');

            $departments[] = [
                'key' => $key,
                'label' => $label,
                'employee_count' => $deptEmployees->count(),
                'avg_achievement' => $avgAchievement,
                'total_revenue' => (float) $deptRevenue,
                'avg_salary' => round($deptEmployees->avg('base_salary'), 0),
            ];
        }

        return $departments;
    }

    /**
     * KPI overview: for each active KPI definition, show aggregate stats.
     */
    public function getKpiOverview(Collection $kpiDefinitions, Collection $employees, string $periodLabel): array
    {
        return $kpiDefinitions->map(function ($kpi) use ($employees, $periodLabel) {
            $values = KpiValue::where('kpi_definition_id', $kpi->id)
                ->whereIn('employee_profile_id', $employees->pluck('id'))
                ->where('period_label', $periodLabel)
                ->get();

            return [
                'id' => $kpi->id,
                'name' => $kpi->name,
                'unit' => $kpi->unit,
                'category' => $kpi->category,
                'target_value' => (float) $kpi->target_value,
                'period' => $kpi->period,
                'higher_is_better' => $kpi->higher_is_better,
                'employees_tracked' => $values->count(),
                'avg_value' => $values->count() > 0 ? round($values->avg('value'), 2) : 0,
                'avg_achievement' => $values->count() > 0
                    ? round($values->avg(fn ($v) => $v->getAchievement()), 1)
                    : 0,
                'max_value' => $values->count() > 0 ? (float) $values->max('value') : 0,
                'min_value' => $values->count() > 0 ? (float) $values->min('value') : 0,
            ];
        })->values()->toArray();
    }

    /**
     * Top N performers by KPI achievement.
     */
    public function getTopPerformers(Collection $employees, string $periodLabel, int $accountId, int $limit = 5): array
    {
        return $employees->map(function ($emp) use ($periodLabel, $accountId) {
            $kpiValues = $emp->kpiValues->where('period_label', $periodLabel);
            $avgAchievement = $kpiValues->count() > 0
                ? round($kpiValues->avg(fn ($kv) => $kv->getAchievement()), 1)
                : 0;

            $revenue = Deal::where('account_id', $accountId)
                ->where('assigned_to', $emp->user_id)
                ->where('status', Deal::STATUS_WON)
                ->sum('value');

            return [
                'id' => $emp->id,
                'name' => $emp->user?->name ?? 'Unknown',
                'department' => $emp->department,
                'position' => $emp->position,
                'achievement' => $avgAchievement,
                'revenue' => (float) $revenue,
            ];
        })
        ->sortByDesc('achievement')
        ->take($limit)
        ->values()
        ->toArray();
    }

    /**
     * Revenue per employee breakdown.
     */
    public function getRevenuePerEmployee(Collection $employees, int $accountId): array
    {
        return $employees->map(function ($emp) use ($accountId) {
            $dealsWon = Deal::where('account_id', $accountId)
                ->where('assigned_to', $emp->user_id)
                ->where('status', Deal::STATUS_WON);

            $totalRevenue = (float) $dealsWon->sum('value');
            $dealsCount = $dealsWon->count();
            $avgDealSize = $dealsCount > 0 ? round($totalRevenue / $dealsCount, 0) : 0;

            // Revenue-to-salary ratio
            $salary = (float) $emp->base_salary;
            $revenueRatio = $salary > 0 ? round($totalRevenue / ($salary * 12), 2) : 0;

            return [
                'id' => $emp->id,
                'name' => $emp->user?->name ?? 'Unknown',
                'department' => $emp->department,
                'total_revenue' => $totalRevenue,
                'deals_count' => $dealsCount,
                'avg_deal_size' => $avgDealSize,
                'base_salary' => $salary,
                'revenue_ratio' => $revenueRatio,
            ];
        })
        ->sortByDesc('total_revenue')
        ->values()
        ->toArray();
    }

    /**
     * Calculate individual employee performance detail.
     */
    public function getEmployeePerformanceDetail(EmployeeProfile $employee, int $accountId): array
    {
        $employee->load(['user:id,first_name,last_name,email', 'kpiValues.definition', 'reviews.reviewer:id,first_name,last_name']);

        // Group KPI values by period
        $kpiByPeriod = $employee->kpiValues
            ->sortByDesc('period_start')
            ->groupBy('period_label')
            ->map(function ($periodValues, $label) {
                return [
                    'period' => $label,
                    'kpis' => $periodValues->map(fn ($kv) => [
                        'id' => $kv->id,
                        'kpi_name' => $kv->definition?->name ?? 'Unknown',
                        'unit' => $kv->definition?->unit ?? 'number',
                        'category' => $kv->definition?->category ?? 'custom',
                        'value' => (float) $kv->value,
                        'target' => (float) $kv->target,
                        'achievement' => $kv->getAchievement(),
                        'notes' => $kv->notes,
                    ])->values()->toArray(),
                    'avg_achievement' => $periodValues->count() > 0
                        ? round($periodValues->avg(fn ($kv) => $kv->getAchievement()), 1)
                        : 0,
                ];
            })->values()->toArray();

        // Revenue history
        $deals = Deal::where('account_id', $accountId)
            ->where('assigned_to', $employee->user_id)
            ->where('status', Deal::STATUS_WON)
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'value', 'updated_at']);

        // Reviews
        $reviews = $employee->reviews->sortByDesc('period_start')->map(fn ($r) => [
            'id' => $r->id,
            'period_label' => $r->period_label,
            'period_start' => $r->period_start?->format('Y-m-d'),
            'period_end' => $r->period_end?->format('Y-m-d'),
            'overall_score' => $r->overall_score,
            'rating' => $r->rating,
            'score_breakdown' => $r->score_breakdown,
            'revenue_generated' => (float) $r->revenue_generated,
            'deals_closed_value' => (float) $r->deals_closed_value,
            'deals_closed_count' => $r->deals_closed_count,
            'hours_logged' => (float) $r->hours_logged,
            'strengths' => $r->strengths,
            'improvements' => $r->improvements,
            'notes' => $r->notes,
            'reviewed_by' => $r->reviewer?->name ?? null,
        ])->values()->toArray();

        return [
            'employee' => [
                'id' => $employee->id,
                'user_id' => $employee->user_id,
                'name' => $employee->user?->name ?? 'Unknown',
                'email' => $employee->user?->email ?? '',
                'department' => $employee->department,
                'position' => $employee->position,
                'hire_date' => $employee->hire_date?->format('Y-m-d'),
                'tenure_months' => $employee->getTenureMonths(),
                'base_salary' => (float) $employee->base_salary,
                'hourly_rate' => (float) $employee->hourly_rate,
                'target_hours_monthly' => $employee->target_hours_monthly,
            ],
            'kpi_history' => $kpiByPeriod,
            'deals' => $deals->map(fn ($d) => [
                'id' => $d->id,
                'title' => $d->title,
                'value' => (float) $d->value,
                'closed_at' => $d->updated_at?->format('Y-m-d'),
            ])->toArray(),
            'reviews' => $reviews,
            'total_revenue' => (float) $deals->sum('value'),
            'total_deals' => $deals->count(),
        ];
    }

    /**
     * Aggregate performance scores for generating a review.
     */
    public function calculatePerformanceScore(EmployeeProfile $employee, string $periodLabel, int $accountId): array
    {
        $kpiValues = KpiValue::where('employee_profile_id', $employee->id)
            ->where('period_label', $periodLabel)
            ->get();

        $kpiAchievement = $kpiValues->count() > 0
            ? round($kpiValues->avg(fn ($kv) => $kv->getAchievement()), 1)
            : 0;

        // Revenue contribution
        $periodDates = $this->parsePeriodDates($periodLabel);
        $revenueGenerated = Deal::where('account_id', $accountId)
            ->where('assigned_to', $employee->user_id)
            ->where('status', Deal::STATUS_WON)
            ->whereBetween('updated_at', [$periodDates['start'], $periodDates['end']])
            ->sum('value');

        $dealsClosedCount = Deal::where('account_id', $accountId)
            ->where('assigned_to', $employee->user_id)
            ->where('status', Deal::STATUS_WON)
            ->whereBetween('updated_at', [$periodDates['start'], $periodDates['end']])
            ->count();

        // Hours logged via project resources
        $hoursLogged = (float) ProjectResource::where('user_id', $employee->user_id)
            ->sum('logged_hours');

        // Utilization
        $targetHours = $employee->target_hours_monthly ?: 160;
        $utilizationScore = min(100, round(($hoursLogged / $targetHours) * 100, 1));

        // Weighted overall score
        $weights = [
            'kpi_achievement' => 0.40,
            'revenue_contribution' => 0.30,
            'utilization' => 0.20,
            'quality' => 0.10,
        ];

        // Normalize revenue to a 0-100 score (based on company avg)
        $allEmployeeRevenue = Deal::where('account_id', $accountId)
            ->where('status', Deal::STATUS_WON)
            ->whereBetween('updated_at', [$periodDates['start'], $periodDates['end']])
            ->avg('value') ?: 1;
        $revenueScore = min(100, round(((float) $revenueGenerated / max($allEmployeeRevenue, 1)) * 50, 1));

        $overallScore = (int) round(
            $kpiAchievement * $weights['kpi_achievement']
            + $revenueScore * $weights['revenue_contribution']
            + $utilizationScore * $weights['utilization']
            + 70 * $weights['quality'] // default quality placeholder
        );

        $overallScore = min(100, max(0, $overallScore));

        $rating = match (true) {
            $overallScore >= 90 => PerformanceReview::RATING_EXCEPTIONAL,
            $overallScore >= 75 => PerformanceReview::RATING_EXCEEDS,
            $overallScore >= 55 => PerformanceReview::RATING_MEETS,
            $overallScore >= 35 => PerformanceReview::RATING_BELOW,
            default => PerformanceReview::RATING_UNSATISFACTORY,
        };

        return [
            'overall_score' => $overallScore,
            'score_breakdown' => [
                'kpi_achievement' => $kpiAchievement,
                'revenue_contribution' => $revenueScore,
                'utilization' => $utilizationScore,
                'quality' => 70,
            ],
            'rating' => $rating,
            'revenue_generated' => (float) $revenueGenerated,
            'deals_closed_value' => (float) $revenueGenerated,
            'deals_closed_count' => $dealsClosedCount,
            'hours_logged' => $hoursLogged,
        ];
    }

    // ------ Private helpers ------

    private function getPeriodRevenue(int $accountId, string $periodLabel): float
    {
        $dates = $this->parsePeriodDates($periodLabel);

        return (float) Deal::where('account_id', $accountId)
            ->where('status', Deal::STATUS_WON)
            ->whereBetween('updated_at', [$dates['start'], $dates['end']])
            ->sum('value');
    }

    private function getPeriodLoggedHours(Collection $employees, string $periodLabel): float
    {
        return (float) ProjectResource::whereIn('user_id', $employees->pluck('user_id'))
            ->sum('logged_hours');
    }

    private function parsePeriodDates(string $periodLabel): array
    {
        // Supports "2026-03" (monthly) and "2026-Q1" (quarterly)
        if (preg_match('/^\d{4}-Q(\d)$/', $periodLabel, $m)) {
            $year = (int) substr($periodLabel, 0, 4);
            $quarter = (int) $m[1];
            $startMonth = ($quarter - 1) * 3 + 1;
            return [
                'start' => Carbon::create($year, $startMonth, 1)->startOfDay(),
                'end' => Carbon::create($year, $startMonth, 1)->addMonths(3)->subDay()->endOfDay(),
            ];
        }

        // Default: monthly
        $date = Carbon::createFromFormat('Y-m', $periodLabel);
        return [
            'start' => $date->copy()->startOfMonth()->startOfDay(),
            'end' => $date->copy()->endOfMonth()->endOfDay(),
        ];
    }
}
