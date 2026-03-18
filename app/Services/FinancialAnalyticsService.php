<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\EmployeeProfile;
use App\Models\FinancialSnapshot;
use App\Models\FinancialTransaction;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialAnalyticsService
{
    // ════════════════════════════════════════════
    //  DASHBOARD ANALYTICS
    // ════════════════════════════════════════════

    /**
     * Get complete financial dashboard data.
     */
    public function getDashboardAnalytics(int $accountId): array
    {
        $now = Carbon::now();

        return [
            'summary' => $this->getMonthlySummary($accountId, $now->year, $now->month),
            'cashflow_trend' => $this->getCashflowTrend($accountId, 12),
            'expense_breakdown' => $this->getExpenseBreakdown($accountId, $now->year, $now->month),
            'income_breakdown' => $this->getIncomeBreakdown($accountId, $now->year, $now->month),
            'burn_rate' => $this->getBurnRateAnalysis($accountId),
            'profit_trend' => $this->getProfitTrend($accountId, 12),
            'recent_transactions' => $this->getRecentTransactions($accountId, 10),
            'monthly_comparison' => $this->getMonthlyComparison($accountId),
            'top_expenses' => $this->getTopExpenseCategories($accountId, $now->year, $now->month),
        ];
    }

    // ════════════════════════════════════════════
    //  MONTHLY SUMMARY
    // ════════════════════════════════════════════

    /**
     * Calculate monthly financial summary.
     *
     * Formulas:
     *   Net Cashflow     = Total Income − Total Expenses
     *   Profit Margin    = (Net Cashflow / Total Income) × 100
     *   Burn Rate        = Avg monthly expenses (last 3 months)
     *   Runway           = Cash Balance / Burn Rate
     */
    public function getMonthlySummary(int $accountId, int $year, int $month): array
    {
        $transactions = FinancialTransaction::where('account_id', $accountId)
            ->inMonth($year, $month)
            ->get();

        $totalIncome = (float) $transactions->where('type', 'income')->sum('amount');
        $totalExpenses = (float) $transactions->where('type', 'expense')->sum('amount');
        $netCashflow = $totalIncome - $totalExpenses;
        $profitMargin = $totalIncome > 0 ? round(($netCashflow / $totalIncome) * 100, 1) : 0;

        // Include deal revenue from CRM
        $dealRevenue = $this->getMonthlyDealRevenue($accountId, $year, $month);
        $projectRevenue = $this->getMonthlyProjectRevenue($accountId, $year, $month);

        // Burn rate: average monthly expenses over last 3 months
        $burnRate = $this->calculateBurnRate($accountId, 3);

        // Cumulative cash balance (sum of all net cashflows)
        $cashBalance = $this->getCashBalance($accountId);

        // Runway in months
        $runway = $burnRate > 0 ? round($cashBalance / $burnRate, 1) : 0;

        // MoM growth
        $prevMonth = Carbon::create($year, $month, 1)->subMonth();
        $prevIncome = (float) FinancialTransaction::where('account_id', $accountId)
            ->income()->inMonth($prevMonth->year, $prevMonth->month)->sum('amount');
        $prevExpenses = (float) FinancialTransaction::where('account_id', $accountId)
            ->expense()->inMonth($prevMonth->year, $prevMonth->month)->sum('amount');
        $prevNet = $prevIncome - $prevExpenses;

        $incomeGrowth = $prevIncome > 0 ? round((($totalIncome - $prevIncome) / $prevIncome) * 100, 1) : 0;
        $expenseGrowth = $prevExpenses > 0 ? round((($totalExpenses - $prevExpenses) / $prevExpenses) * 100, 1) : 0;

        return [
            'period' => sprintf('%04d-%02d', $year, $month),
            'period_label' => Carbon::create($year, $month, 1)->format('F Y'),
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_cashflow' => $netCashflow,
            'profit_margin' => $profitMargin,
            'burn_rate' => $burnRate,
            'cash_balance' => $cashBalance,
            'runway_months' => max(0, $runway),
            'deal_revenue' => $dealRevenue,
            'project_revenue' => $projectRevenue,
            'transaction_count' => $transactions->count(),
            'income_growth' => $incomeGrowth,
            'expense_growth' => $expenseGrowth,
            'prev_net_cashflow' => $prevNet,
        ];
    }

    // ════════════════════════════════════════════
    //  CASHFLOW TREND (last N months)
    // ════════════════════════════════════════════

    /**
     * Monthly cashflow in/out trend for charting.
     */
    public function getCashflowTrend(int $accountId, int $months = 12): array
    {
        $trend = [];
        $now = Carbon::now();

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $y = $date->year;
            $m = $date->month;

            $income = (float) FinancialTransaction::where('account_id', $accountId)
                ->income()->inMonth($y, $m)->sum('amount');
            $expenses = (float) FinancialTransaction::where('account_id', $accountId)
                ->expense()->inMonth($y, $m)->sum('amount');

            $trend[] = [
                'month' => $date->format('Y-m'),
                'label' => $date->format('M'),
                'full_label' => $date->format('M Y'),
                'income' => $income,
                'expenses' => $expenses,
                'net' => $income - $expenses,
            ];
        }

        return $trend;
    }

    // ════════════════════════════════════════════
    //  EXPENSE / INCOME BREAKDOWN
    // ════════════════════════════════════════════

    /**
     * Group expenses by category for a given month.
     */
    public function getExpenseBreakdown(int $accountId, int $year, int $month): array
    {
        return FinancialTransaction::where('account_id', $accountId)
            ->expense()
            ->inMonth($year, $month)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'label' => FinancialTransaction::getExpenseCategories()[$row->category] ?? $row->category,
                'total' => (float) $row->total,
            ])
            ->toArray();
    }

    /**
     * Group income by category for a given month.
     */
    public function getIncomeBreakdown(int $accountId, int $year, int $month): array
    {
        return FinancialTransaction::where('account_id', $accountId)
            ->income()
            ->inMonth($year, $month)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'label' => FinancialTransaction::getIncomeCategories()[$row->category] ?? $row->category,
                'total' => (float) $row->total,
            ])
            ->toArray();
    }

    // ════════════════════════════════════════════
    //  BURN RATE ANALYSIS
    // ════════════════════════════════════════════

    /**
     * Burn rate = average monthly expenses over N trailing months.
     *
     * Formula:
     *   Burn Rate  = Σ(monthly expenses for last N months) / N
     *   Runway     = Cash Balance / Burn Rate
     *
     * Also a "Net Burn Rate" = Burn Rate − Average Monthly Income
     */
    public function getBurnRateAnalysis(int $accountId, int $lookbackMonths = 6): array
    {
        $now = Carbon::now();
        $monthlyData = [];

        for ($i = 1; $i <= $lookbackMonths; $i++) {
            $date = $now->copy()->subMonths($i);
            $y = $date->year;
            $m = $date->month;

            $expenses = (float) FinancialTransaction::where('account_id', $accountId)
                ->expense()->inMonth($y, $m)->sum('amount');
            $income = (float) FinancialTransaction::where('account_id', $accountId)
                ->income()->inMonth($y, $m)->sum('amount');

            $monthlyData[] = [
                'month' => $date->format('Y-m'),
                'expenses' => $expenses,
                'income' => $income,
            ];
        }

        $totalExpenses = array_sum(array_column($monthlyData, 'expenses'));
        $totalIncome = array_sum(array_column($monthlyData, 'income'));
        $avgExpenses = count($monthlyData) > 0 ? round($totalExpenses / count($monthlyData), 0) : 0;
        $avgIncome = count($monthlyData) > 0 ? round($totalIncome / count($monthlyData), 0) : 0;

        $grossBurnRate = $avgExpenses;
        $netBurnRate = max(0, $avgExpenses - $avgIncome);
        $cashBalance = $this->getCashBalance($accountId);

        $grossRunway = $grossBurnRate > 0 ? round($cashBalance / $grossBurnRate, 1) : 0;
        $netRunway = $netBurnRate > 0 ? round($cashBalance / $netBurnRate, 1) : 0;

        // Burn rate trend (is it increasing or decreasing?)
        $recentExpenses = array_slice(array_column($monthlyData, 'expenses'), 0, 3);
        $olderExpenses = array_slice(array_column($monthlyData, 'expenses'), 3, 3);
        $recentAvg = count($recentExpenses) > 0 ? array_sum($recentExpenses) / count($recentExpenses) : 0;
        $olderAvg = count($olderExpenses) > 0 ? array_sum($olderExpenses) / count($olderExpenses) : 0;
        $burnTrend = $olderAvg > 0 ? round((($recentAvg - $olderAvg) / $olderAvg) * 100, 1) : 0;

        return [
            'gross_burn_rate' => $grossBurnRate,
            'net_burn_rate' => $netBurnRate,
            'avg_monthly_income' => $avgIncome,
            'avg_monthly_expenses' => $avgExpenses,
            'cash_balance' => $cashBalance,
            'gross_runway_months' => max(0, $grossRunway),
            'net_runway_months' => max(0, $netRunway),
            'burn_trend_percent' => $burnTrend,
            'lookback_months' => $lookbackMonths,
            'monthly_breakdown' => $monthlyData,
        ];
    }

    // ════════════════════════════════════════════
    //  PROFIT TREND
    // ════════════════════════════════════════════

    /**
     * Monthly profit margin trend.
     *
     * Formula:
     *   Profit Margin = ((Income − Expenses) / Income) × 100
     */
    public function getProfitTrend(int $accountId, int $months = 12): array
    {
        $trend = [];
        $now = Carbon::now();

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $y = $date->year;
            $m = $date->month;

            $income = (float) FinancialTransaction::where('account_id', $accountId)
                ->income()->inMonth($y, $m)->sum('amount');
            $expenses = (float) FinancialTransaction::where('account_id', $accountId)
                ->expense()->inMonth($y, $m)->sum('amount');

            $profit = $income - $expenses;
            $margin = $income > 0 ? round(($profit / $income) * 100, 1) : 0;

            $trend[] = [
                'month' => $date->format('Y-m'),
                'label' => $date->format('M'),
                'income' => $income,
                'expenses' => $expenses,
                'profit' => $profit,
                'margin' => $margin,
            ];
        }

        return $trend;
    }

    // ════════════════════════════════════════════
    //  MONTHLY COMPARISON
    // ════════════════════════════════════════════

    /**
     * Current month vs previous month comparison.
     */
    public function getMonthlyComparison(int $accountId): array
    {
        $now = Carbon::now();
        $prev = $now->copy()->subMonth();

        $curIncome = (float) FinancialTransaction::where('account_id', $accountId)
            ->income()->inMonth($now->year, $now->month)->sum('amount');
        $curExpenses = (float) FinancialTransaction::where('account_id', $accountId)
            ->expense()->inMonth($now->year, $now->month)->sum('amount');

        $prevIncome = (float) FinancialTransaction::where('account_id', $accountId)
            ->income()->inMonth($prev->year, $prev->month)->sum('amount');
        $prevExpenses = (float) FinancialTransaction::where('account_id', $accountId)
            ->expense()->inMonth($prev->year, $prev->month)->sum('amount');

        return [
            'current_month' => $now->format('M Y'),
            'previous_month' => $prev->format('M Y'),
            'income' => [
                'current' => $curIncome,
                'previous' => $prevIncome,
                'change' => $prevIncome > 0 ? round((($curIncome - $prevIncome) / $prevIncome) * 100, 1) : 0,
            ],
            'expenses' => [
                'current' => $curExpenses,
                'previous' => $prevExpenses,
                'change' => $prevExpenses > 0 ? round((($curExpenses - $prevExpenses) / $prevExpenses) * 100, 1) : 0,
            ],
            'profit' => [
                'current' => $curIncome - $curExpenses,
                'previous' => $prevIncome - $prevExpenses,
            ],
        ];
    }

    // ════════════════════════════════════════════
    //  TOP EXPENSE CATEGORIES
    // ════════════════════════════════════════════

    public function getTopExpenseCategories(int $accountId, int $year, int $month, int $limit = 5): array
    {
        $total = (float) FinancialTransaction::where('account_id', $accountId)
            ->expense()->inMonth($year, $month)->sum('amount');

        return FinancialTransaction::where('account_id', $accountId)
            ->expense()
            ->inMonth($year, $month)
            ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'label' => FinancialTransaction::getExpenseCategories()[$row->category] ?? $row->category,
                'total' => (float) $row->total,
                'count' => $row->count,
                'percentage' => $total > 0 ? round(((float) $row->total / $total) * 100, 1) : 0,
            ])
            ->toArray();
    }

    // ════════════════════════════════════════════
    //  RECENT TRANSACTIONS
    // ════════════════════════════════════════════

    public function getRecentTransactions(int $accountId, int $limit = 10): array
    {
        return FinancialTransaction::where('account_id', $accountId)
            ->with('recorder:id,first_name,last_name')
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'category' => $t->category,
                'category_label' => FinancialTransaction::getAllCategories()[$t->category] ?? $t->category,
                'description' => $t->description,
                'amount' => (float) $t->amount,
                'signed_amount' => $t->getSignedAmount(),
                'transaction_date' => $t->transaction_date->format('Y-m-d'),
                'reference' => $t->reference,
                'is_recurring' => $t->is_recurring,
                'recorder' => $t->recorder?->name ?? null,
            ])
            ->toArray();
    }

    // ════════════════════════════════════════════
    //  SNAPSHOT GENERATION
    // ════════════════════════════════════════════

    /**
     * Generate or update a monthly financial snapshot.
     */
    public function generateSnapshot(int $accountId, int $year, int $month): FinancialSnapshot
    {
        $periodLabel = sprintf('%04d-%02d', $year, $month);
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = $periodStart->copy()->endOfMonth()->endOfDay();

        $income = (float) FinancialTransaction::where('account_id', $accountId)
            ->income()->inMonth($year, $month)->sum('amount');
        $expenses = (float) FinancialTransaction::where('account_id', $accountId)
            ->expense()->inMonth($year, $month)->sum('amount');
        $net = $income - $expenses;
        $margin = $income > 0 ? round(($net / $income) * 100, 2) : 0;
        $burnRate = $this->calculateBurnRate($accountId, 3);
        $cashBalance = $this->getCashBalance($accountId);
        $runway = $burnRate > 0 ? round($cashBalance / $burnRate, 2) : 0;

        $incomeBreakdown = FinancialTransaction::where('account_id', $accountId)
            ->income()->inMonth($year, $month)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')->pluck('total', 'category')->toArray();

        $expenseBreakdown = FinancialTransaction::where('account_id', $accountId)
            ->expense()->inMonth($year, $month)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')->pluck('total', 'category')->toArray();

        return FinancialSnapshot::updateOrCreate(
            ['account_id' => $accountId, 'period_label' => $periodLabel],
            [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'total_income' => $income,
                'total_expenses' => $expenses,
                'net_cashflow' => $net,
                'profit_margin' => $margin,
                'burn_rate' => $burnRate,
                'runway_months' => max(0, $runway),
                'cash_balance' => $cashBalance,
                'income_breakdown' => $incomeBreakdown,
                'expense_breakdown' => $expenseBreakdown,
            ]
        );
    }

    // ════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════

    /**
     * Calculate burn rate = average monthly expenses over N trailing months.
     */
    private function calculateBurnRate(int $accountId, int $months = 3): float
    {
        $now = Carbon::now();
        $total = 0;

        for ($i = 1; $i <= $months; $i++) {
            $date = $now->copy()->subMonths($i);
            $total += (float) FinancialTransaction::where('account_id', $accountId)
                ->expense()->inMonth($date->year, $date->month)->sum('amount');
        }

        return $months > 0 ? round($total / $months, 0) : 0;
    }

    /**
     * Get cumulative cash balance = sum of all income − sum of all expenses.
     */
    private function getCashBalance(int $accountId): float
    {
        $totalIncome = (float) FinancialTransaction::where('account_id', $accountId)
            ->income()->sum('amount');
        $totalExpenses = (float) FinancialTransaction::where('account_id', $accountId)
            ->expense()->sum('amount');

        return $totalIncome - $totalExpenses;
    }

    /**
     * Get deal revenue for a specific month from the CRM.
     */
    private function getMonthlyDealRevenue(int $accountId, int $year, int $month): float
    {
        return (float) Deal::where('account_id', $accountId)
            ->where('status', Deal::STATUS_WON)
            ->whereMonth('updated_at', $month)
            ->whereYear('updated_at', $year)
            ->sum('value');
    }

    /**
     * Get project revenue for a specific month.
     */
    private function getMonthlyProjectRevenue(int $accountId, int $year, int $month): float
    {
        return (float) Project::where('account_id', $accountId)
            ->where('status', Project::STATUS_COMPLETED)
            ->whereMonth('completed_at', $month)
            ->whereYear('completed_at', $year)
            ->sum('revenue');
    }
}
