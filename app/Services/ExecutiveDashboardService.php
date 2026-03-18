<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\EmployeeProfile;
use App\Models\FinancialTransaction;
use App\Models\Initiative;
use App\Models\KeyResult;
use App\Models\KpiValue;
use App\Models\Lead;
use App\Models\Objective;
use App\Models\Project;
use App\Models\StrategicPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExecutiveDashboardService
{
    private const CACHE_TTL = 300; // 5 minutes

    public function getMetrics(int $accountId): array
    {
        return Cache::remember("exec_dashboard:{$accountId}", self::CACHE_TTL, function () use ($accountId) {
            return [
                // ── CRM ──
                'revenue' => $this->getRevenueMetrics($accountId),
                'pipeline' => $this->getPipelineMetrics($accountId),
                'conversion' => $this->getConversionMetrics($accountId),
                'cac' => $this->getCACMetrics($accountId),
                'ltv' => $this->getLTVMetrics($accountId),
                'churn' => $this->getChurnMetrics($accountId),
                'trends' => $this->getTrendData($accountId),
                'top_deals' => $this->getTopDeals($accountId),
                'stage_distribution' => $this->getStageDistribution($accountId),
                'monthly_comparison' => $this->getMonthlyComparison($accountId),
                // ── Finance ──
                'finance' => $this->getFinanceMetrics($accountId),
                // ── HR ──
                'hr' => $this->getHRMetrics($accountId),
                // ── Strategy / OKR ──
                'strategy' => $this->getStrategyMetrics($accountId),
                // ── Operations ──
                'operations' => $this->getOperationsMetrics($accountId),
            ];
        });
    }

    public function invalidateCache(int $accountId): void
    {
        Cache::forget("exec_dashboard:{$accountId}");
    }

    // ════════════════════════════════════════════
    //  CRM METRICS (existing, unchanged)
    // ════════════════════════════════════════════

    private function getRevenueMetrics(int $accountId): array
    {
        $now = Carbon::now();
        $monthly = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_WON)
            ->whereMonth('updated_at', $now->month)->whereYear('updated_at', $now->year)->sum('value');
        $lastMonth = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_WON)
            ->whereMonth('updated_at', $now->copy()->subMonth()->month)
            ->whereYear('updated_at', $now->copy()->subMonth()->year)->sum('value');
        $quarterly = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_WON)
            ->whereBetween('updated_at', [$now->copy()->firstOfQuarter(), $now])->sum('value');
        $yearly = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_WON)
            ->whereYear('updated_at', $now->year)->sum('value');

        $growth = $lastMonth > 0 ? round((($monthly - $lastMonth) / $lastMonth) * 100, 1) : ($monthly > 0 ? 100 : 0);
        return ['monthly' => (float) $monthly, 'quarterly' => (float) $quarterly, 'yearly' => (float) $yearly, 'monthly_growth' => $growth, 'last_month' => (float) $lastMonth];
    }

    private function getPipelineMetrics(int $accountId): array
    {
        $openDeals = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_OPEN)->get();
        $totalValue = $openDeals->sum('value');
        $count = $openDeals->count();
        $stageWeights = [Deal::STAGE_PROSPECTING => 0.10, Deal::STAGE_QUALIFICATION => 0.25, Deal::STAGE_PROPOSAL => 0.50, Deal::STAGE_NEGOTIATION => 0.75, Deal::STAGE_CLOSING => 0.90];
        $weighted = $openDeals->sum(fn ($d) => ($d->value ?? 0) * ($stageWeights[$d->stage] ?? 0.1));
        return ['total_value' => (float) $totalValue, 'weighted_value' => round($weighted), 'deal_count' => $count, 'avg_deal_size' => $count > 0 ? round($totalValue / $count) : 0];
    }

    private function getConversionMetrics(int $accountId): array
    {
        $since = Carbon::now()->subDays(90);
        $totalLeads = Lead::where('account_id', $accountId)->where('created_at', '>=', $since)->count();
        $converted = Lead::where('account_id', $accountId)->where('created_at', '>=', $since)->whereHas('deal')->count();
        $totalDeals = Deal::where('account_id', $accountId)->where('created_at', '>=', $since)->count();
        $wonDeals = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_WON)->where('updated_at', '>=', $since)->count();
        return [
            'lead_to_deal' => $totalLeads > 0 ? round(($converted / $totalLeads) * 100, 1) : 0,
            'deal_to_won' => $totalDeals > 0 ? round(($wonDeals / $totalDeals) * 100, 1) : 0,
            'total_leads' => $totalLeads, 'converted_leads' => $converted,
            'total_deals' => $totalDeals, 'won_deals' => $wonDeals, 'period_days' => 90,
        ];
    }

    private function getCACMetrics(int $accountId): array
    {
        $now = Carbon::now();
        $wonThisMonth = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_WON)->whereMonth('updated_at', $now->month)->whereYear('updated_at', $now->year)->count();
        $acts = DB::table('activities')->where('account_id', $accountId)->whereMonth('date', $now->month)->whereYear('date', $now->year)->count();
        $spend = $acts * 50000;
        $cac = $wonThisMonth > 0 ? round($spend / $wonThisMonth) : 0;
        $lastMonth = $now->copy()->subMonth();
        $wonLast = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_WON)->whereMonth('updated_at', $lastMonth->month)->whereYear('updated_at', $lastMonth->year)->count();
        $actsLast = DB::table('activities')->where('account_id', $accountId)->whereMonth('date', $lastMonth->month)->whereYear('date', $lastMonth->year)->count();
        $cacLast = $wonLast > 0 ? round(($actsLast * 50000) / $wonLast) : 0;
        $change = $cacLast > 0 ? round((($cac - $cacLast) / $cacLast) * 100, 1) : 0;
        return ['current' => (float) $cac, 'last_month' => (float) $cacLast, 'change_percent' => $change, 'new_customers' => $wonThisMonth, 'estimated_spend' => (float) $spend];
    }

    private function getLTVMetrics(int $accountId): array
    {
        $won = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_WON)->get();
        $total = $won->sum('value');
        $customers = $won->pluck('lead_id')->unique()->filter()->count();
        $avgDeal = $won->count() > 0 ? round($total / $won->count()) : 0;
        $dealsPerCustomer = $customers > 0 ? round($won->count() / $customers, 1) : 0;
        $ltv = $avgDeal * max($dealsPerCustomer, 1);
        $cacCurrent = $this->getCACMetrics($accountId)['current'];
        return ['ltv' => (float) $ltv, 'avg_deal_value' => (float) $avgDeal, 'avg_deals_per_customer' => $dealsPerCustomer, 'total_customers' => $customers, 'ltv_cac_ratio' => $cacCurrent > 0 ? round($ltv / $cacCurrent, 1) : 0];
    }

    private function getChurnMetrics(int $accountId): array
    {
        $since = Carbon::now()->subDays(90);
        $qualified = Lead::where('account_id', $accountId)->where('created_at', '>=', $since)->whereIn('status', [Lead::STATUS_QUALIFIED, Lead::STATUS_WON, Lead::STATUS_LOST])->count();
        $lost = Lead::where('account_id', $accountId)->where('status', Lead::STATUS_LOST)->where('updated_at', '>=', $since)->count();
        $lostDeals = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_LOST)->where('updated_at', '>=', $since)->count();
        return ['rate' => $qualified > 0 ? round(($lost / $qualified) * 100, 1) : 0, 'lost_leads' => $lost, 'lost_deals' => $lostDeals, 'period_days' => 90];
    }

    private function getTrendData(int $accountId): array
    {
        return collect(range(11, 0))->map(function ($i) use ($accountId) {
            $date = Carbon::now()->subMonths($i);
            return [
                'month' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
                'short_label' => $date->format('M'),
                'revenue' => (float) Deal::where('account_id', $accountId)->where('status', Deal::STATUS_WON)->whereMonth('updated_at', $date->month)->whereYear('updated_at', $date->year)->sum('value'),
                'new_leads' => Lead::where('account_id', $accountId)->whereMonth('created_at', $date->month)->whereYear('created_at', $date->year)->count(),
            ];
        })->toArray();
    }

    private function getTopDeals(int $accountId, int $limit = 5): array
    {
        return Deal::where('account_id', $accountId)->where('status', Deal::STATUS_OPEN)->whereNotNull('value')
            ->orderByDesc('value')->limit($limit)->with(['lead:id,name,company', 'assignedUser:id,first_name,last_name'])->get()
            ->map(fn ($d) => ['id' => $d->id, 'title' => $d->title, 'value' => (float) $d->value, 'stage' => $d->stage, 'expected_close_date' => $d->expected_close_date?->format('Y-m-d'), 'lead' => $d->lead ? ['name' => $d->lead->name, 'company' => $d->lead->company] : null, 'assigned_user' => $d->assignedUser ? ['name' => $d->assignedUser->name] : null])->toArray();
    }

    private function getStageDistribution(int $accountId): array
    {
        return collect(Deal::getStages())->map(function ($label, $key) use ($accountId) {
            $deals = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_OPEN)->where('stage', $key)->get();
            return ['stage' => $key, 'label' => $label, 'count' => $deals->count(), 'value' => (float) $deals->sum('value')];
        })->values()->toArray();
    }

    private function getMonthlyComparison(int $accountId): array
    {
        $now = Carbon::now();
        $last = $now->copy()->subMonth();
        $curDeals = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_WON)->whereMonth('updated_at', $now->month)->whereYear('updated_at', $now->year);
        $prevDeals = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_WON)->whereMonth('updated_at', $last->month)->whereYear('updated_at', $last->year);
        $curLeads = Lead::where('account_id', $accountId)->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
        $prevLeads = Lead::where('account_id', $accountId)->whereMonth('created_at', $last->month)->whereYear('created_at', $last->year);
        return [
            'current_month' => $now->format('M Y'), 'previous_month' => $last->format('M Y'),
            'deals_won' => ['current' => $curDeals->count(), 'previous' => $prevDeals->count()],
            'revenue' => ['current' => (float) $curDeals->sum('value'), 'previous' => (float) $prevDeals->sum('value')],
            'new_leads' => ['current' => $curLeads->count(), 'previous' => $prevLeads->count()],
        ];
    }

    // ════════════════════════════════════════════
    //  FINANCE METRICS (NEW)
    // ════════════════════════════════════════════

    private function getFinanceMetrics(int $accountId): array
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();
        $ytdStart = $now->copy()->startOfYear();

        // Check if FinancialTransaction exists
        if (!class_exists(\App\Models\FinancialTransaction::class) || !\Schema::hasTable('financial_transactions')) {
            return $this->emptyFinanceMetrics();
        }

        $income = fn ($start, $end) => (float) FinancialTransaction::where('account_id', $accountId)
            ->where('type', FinancialTransaction::TYPE_INCOME)
            ->whereBetween('transaction_date', [$start, $end])->sum('amount');

        $expense = fn ($start, $end) => (float) FinancialTransaction::where('account_id', $accountId)
            ->where('type', FinancialTransaction::TYPE_EXPENSE)
            ->whereBetween('transaction_date', [$start, $end])->sum('amount');

        $thisIncome = $income($monthStart, $monthEnd);
        $thisExpense = $expense($monthStart, $monthEnd);
        $lastIncome = $income($lastMonthStart, $lastMonthEnd);
        $lastExpense = $expense($lastMonthStart, $lastMonthEnd);
        $ytdIncome = $income($ytdStart, $now);
        $ytdExpense = $expense($ytdStart, $now);

        $profit = $thisIncome - $thisExpense;
        $margin = $thisIncome > 0 ? round(($profit / $thisIncome) * 100, 1) : 0;
        $burnRate = $thisExpense;
        $lastMargin = $lastIncome > 0 ? round((($lastIncome - $lastExpense) / $lastIncome) * 100, 1) : 0;

        // Cashflow trend (last 6 months)
        $cashflowTrend = collect(range(5, 0))->map(function ($i) use ($accountId, $income, $expense) {
            $date = Carbon::now()->subMonths($i);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();
            $i = $income($start, $end);
            $e = $expense($start, $end);
            return ['label' => $date->format('M'), 'income' => $i, 'expense' => $e, 'profit' => $i - $e];
        })->toArray();

        return [
            'monthly_income' => $thisIncome,
            'monthly_expense' => $thisExpense,
            'monthly_profit' => $profit,
            'profit_margin' => $margin,
            'margin_change' => $margin - $lastMargin,
            'burn_rate' => $burnRate,
            'ytd_income' => $ytdIncome,
            'ytd_expense' => $ytdExpense,
            'ytd_profit' => $ytdIncome - $ytdExpense,
            'cashflow_trend' => $cashflowTrend,
        ];
    }

    private function emptyFinanceMetrics(): array
    {
        return ['monthly_income' => 0, 'monthly_expense' => 0, 'monthly_profit' => 0, 'profit_margin' => 0, 'margin_change' => 0, 'burn_rate' => 0, 'ytd_income' => 0, 'ytd_expense' => 0, 'ytd_profit' => 0, 'cashflow_trend' => []];
    }

    // ════════════════════════════════════════════
    //  HR METRICS (NEW)
    // ════════════════════════════════════════════

    private function getHRMetrics(int $accountId): array
    {
        if (!class_exists(\App\Models\EmployeeProfile::class)) {
            return ['headcount' => 0, 'active_employees' => 0, 'revenue_per_employee' => 0, 'avg_kpi_achievement' => 0];
        }

        $employees = EmployeeProfile::where('account_id', $accountId)->get();
        $headcount = $employees->count();
        $active = $employees->where('status', 'active')->count();

        $monthlyRevenue = (float) Deal::where('account_id', $accountId)->where('status', Deal::STATUS_WON)
            ->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->sum('value');

        $revenuePerEmployee = $headcount > 0 ? round($monthlyRevenue / $headcount) : 0;

        // KPI achievement — scope via employee_profile_id, filter by period overlap
        $avgKpi = 0;
        $employeeIds = $employees->pluck('id');

        if ($employeeIds->isNotEmpty() && class_exists(\App\Models\KpiValue::class)) {
            $monthStart = now()->startOfMonth()->toDateString();
            $monthEnd   = now()->endOfMonth()->toDateString();

            $kpiValues = KpiValue::whereIn('employee_profile_id', $employeeIds)
                ->where('period_start', '<=', $monthEnd)
                ->where('period_end',   '>=', $monthStart)
                ->get();

            if ($kpiValues->count() > 0) {
                $achieved = $kpiValues->filter(function ($kv) {
                    $target = (float) ($kv->target ?? 0);
                    $value  = (float) ($kv->value  ?? 0);
                    return $target > 0 && $value >= $target;
                })->count();
                $avgKpi = round(($achieved / $kpiValues->count()) * 100);
            }
        }

        return [
            'headcount'             => $headcount,
            'active_employees'      => $active,
            'revenue_per_employee'  => $revenuePerEmployee,
            'avg_kpi_achievement'   => $avgKpi,
        ];
    }

    // ════════════════════════════════════════════
    //  STRATEGY / OKR METRICS (NEW)
    // ════════════════════════════════════════════

    private function getStrategyMetrics(int $accountId): array
    {
        if (!class_exists(Objective::class)) {
            return $this->emptyStrategyMetrics();
        }

        $plan = StrategicPlan::getActivePlan($accountId);
        $currentPeriod = 'Q' . now()->quarter . '-' . now()->year;

        $objectives = Objective::where('account_id', $accountId)->forPeriod($currentPeriod)->get();
        if ($objectives->isEmpty()) {
            $objectives = Objective::where('account_id', $accountId)->get();
        }

        $total = $objectives->count();
        $completed = $objectives->where('status', 'completed')->count();
        $atRisk = $objectives->where('status', 'at_risk')->count();
        $avgProgress = $total > 0 ? round($objectives->avg('progress'), 1) : 0;
        $avgConfidence = $total > 0 ? round($objectives->avg('confidence')) : 0;

        // KR auto-tracking status
        $autoKRs = KeyResult::where('account_id', $accountId)->where('data_source', '!=', 'manual')->count();
        $totalKRs = KeyResult::where('account_id', $accountId)->count();

        // Overdue initiatives
        $overdueInits = Initiative::where('account_id', $accountId)
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled'])->count();

        // Top 3 at-risk objectives for alerts
        $atRiskList = Objective::where('account_id', $accountId)
            ->where('status', 'at_risk')
            ->with('owner:id,first_name,last_name')
            ->limit(3)->get()
            ->map(fn ($o) => ['id' => $o->id, 'title' => $o->title, 'progress' => (float) $o->progress, 'owner' => $o->owner?->name])
            ->toArray();

        return [
            'plan_title' => $plan?->title,
            'current_period' => $currentPeriod,
            'total_objectives' => $total,
            'completed' => $completed,
            'at_risk' => $atRisk,
            'avg_progress' => $avgProgress,
            'avg_confidence' => $avgConfidence,
            'overall_health' => $avgProgress,
            'auto_tracked_krs' => $autoKRs,
            'total_krs' => $totalKRs,
            'overdue_initiatives' => $overdueInits,
            'at_risk_list' => $atRiskList,
        ];
    }

    private function emptyStrategyMetrics(): array
    {
        return ['plan_title' => null, 'current_period' => null, 'total_objectives' => 0, 'completed' => 0, 'at_risk' => 0, 'avg_progress' => 0, 'avg_confidence' => 0, 'overall_health' => 0, 'auto_tracked_krs' => 0, 'total_krs' => 0, 'overdue_initiatives' => 0, 'at_risk_list' => []];
    }

    // ════════════════════════════════════════════
    //  OPERATIONS METRICS (NEW)
    // ════════════════════════════════════════════

    private function getOperationsMetrics(int $accountId): array
    {
        $now = Carbon::now();

        $customers = Customer::where('account_id', $accountId)->count();
        $newCustomers = Customer::where('account_id', $accountId)->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count();

        $projects = Project::where('account_id', $accountId)->get();
        $activeProjects = $projects->whereIn('status', ['in_progress', 'active'])->count();
        $completedThisMonth = $projects->where('status', 'completed')
            ->filter(fn ($p) => Carbon::parse($p->updated_at)->month === $now->month && Carbon::parse($p->updated_at)->year === $now->year)->count();

        $overdueProjects = $projects->filter(fn ($p) => $p->due_date && Carbon::parse($p->due_date)->isPast() && !in_array($p->status, ['completed', 'cancelled']))->count();

        return [
            'total_customers' => $customers,
            'new_customers_this_month' => $newCustomers,
            'active_projects' => $activeProjects,
            'completed_projects_this_month' => $completedThisMonth,
            'overdue_projects' => $overdueProjects,
        ];
    }
}
