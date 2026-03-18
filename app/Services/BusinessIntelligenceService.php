<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\EmployeeProfile;
use App\Models\FinancialTransaction;
use App\Models\Lead;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BusinessIntelligenceService
{
    // ════════════════════════════════════════════
    //  MAIN ANALYSIS — aggregates all insights
    // ════════════════════════════════════════════

    /**
     * Run full business analysis for CEO dashboard.
     */
    public function analyze(int $accountId): array
    {
        $revenueAnalysis = $this->analyzeRevenue($accountId);
        $pipelineAnalysis = $this->analyzePipeline($accountId);
        $customerAnalysis = $this->analyzeCustomers($accountId);
        $marketingAnalysis = $this->analyzeMarketing($accountId);
        $financialHealth = $this->analyzeFinancialHealth($accountId);
        $teamAnalysis = $this->analyzeTeam($accountId);

        $risks = $this->detectRisks($revenueAnalysis, $pipelineAnalysis, $customerAnalysis, $financialHealth, $teamAnalysis);
        $actions = $this->suggestActions($revenueAnalysis, $pipelineAnalysis, $customerAnalysis, $marketingAnalysis, $financialHealth, $teamAnalysis, $risks);
        $prediction = $this->predictNextMonthRevenue($accountId);

        return [
            'prediction' => $prediction,
            'risks' => $risks,
            'actions' => $actions,
            'revenue' => $revenueAnalysis,
            'pipeline' => $pipelineAnalysis,
            'customers' => $customerAnalysis,
            'marketing' => $marketingAnalysis,
            'financial_health' => $financialHealth,
            'team' => $teamAnalysis,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    // ════════════════════════════════════════════
    //  1. REVENUE PREDICTION
    //  Uses weighted linear regression on 6-month data
    // ════════════════════════════════════════════

    /**
     * Predict next month's revenue using linear regression + seasonality.
     *
     * Method:
     *   1. Collect last 6 months of revenue (won deals)
     *   2. Apply weighted linear regression (recent months weighted heavier)
     *   3. Apply confidence interval based on data variance
     *
     * Formula:
     *   ŷ = β₀ + β₁·x  (weighted least squares)
     *   Weights: [0.5, 0.6, 0.7, 0.8, 0.9, 1.0] (most recent = highest)
     */
    public function predictNextMonthRevenue(int $accountId): array
    {
        $now = Carbon::now();
        $months = 6;
        $revenueData = [];
        $weights = [0.5, 0.6, 0.7, 0.8, 0.9, 1.0];

        for ($i = $months; $i >= 1; $i--) {
            $date = $now->copy()->subMonths($i);
            $revenue = (float) Deal::where('account_id', $accountId)
                ->where('status', Deal::STATUS_WON)
                ->whereMonth('updated_at', $date->month)
                ->whereYear('updated_at', $date->year)
                ->sum('value');

            $revenueData[] = [
                'month' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
                'revenue' => $revenue,
                'x' => $months - $i, // 0,1,2,3,4,5
            ];
        }

        // Weighted linear regression: ŷ = β₀ + β₁·x
        $n = count($revenueData);
        if ($n < 2) {
            $lastRevenue = end($revenueData)['revenue'] ?? 0;
            return [
                'predicted_revenue' => round($lastRevenue, 0),
                'confidence' => 'low',
                'confidence_percent' => 30,
                'lower_bound' => round($lastRevenue * 0.7, 0),
                'upper_bound' => round($lastRevenue * 1.3, 0),
                'trend' => 'insufficient_data',
                'trend_percent' => 0,
                'method' => 'fallback',
                'historical_data' => $revenueData,
            ];
        }

        $sumW = 0; $sumWX = 0; $sumWY = 0; $sumWXX = 0; $sumWXY = 0;
        for ($i = 0; $i < $n; $i++) {
            $w = $weights[$i] ?? 1.0;
            $x = $revenueData[$i]['x'];
            $y = $revenueData[$i]['revenue'];
            $sumW += $w;
            $sumWX += $w * $x;
            $sumWY += $w * $y;
            $sumWXX += $w * $x * $x;
            $sumWXY += $w * $x * $y;
        }

        $denom = ($sumW * $sumWXX) - ($sumWX * $sumWX);
        if ($denom == 0) {
            $avgRevenue = $sumWY / $sumW;
            $beta0 = $avgRevenue;
            $beta1 = 0;
        } else {
            $beta1 = (($sumW * $sumWXY) - ($sumWX * $sumWY)) / $denom;
            $beta0 = ($sumWY - ($beta1 * $sumWX)) / $sumW;
        }

        // Predict next month (x = $n)
        $predicted = $beta0 + ($beta1 * $n);
        $predicted = max(0, $predicted); // revenue can't be negative

        // Calculate variance for confidence interval
        $residuals = [];
        for ($i = 0; $i < $n; $i++) {
            $yHat = $beta0 + ($beta1 * $revenueData[$i]['x']);
            $residuals[] = ($revenueData[$i]['revenue'] - $yHat) ** 2;
        }
        $variance = count($residuals) > 1 ? array_sum($residuals) / (count($residuals) - 1) : 0;
        $stdDev = sqrt($variance);

        // Coefficient of variation for confidence assessment
        $avgRevenue = array_sum(array_column($revenueData, 'revenue')) / $n;
        $cv = $avgRevenue > 0 ? ($stdDev / $avgRevenue) * 100 : 100;

        $confidence = $cv < 15 ? 'high' : ($cv < 30 ? 'medium' : 'low');
        $confidencePercent = max(10, min(95, 100 - (int) $cv));

        // Trend analysis
        $lastMonth = $revenueData[$n - 1]['revenue'] ?? 0;
        $trendPercent = $lastMonth > 0 ? round((($predicted - $lastMonth) / $lastMonth) * 100, 1) : 0;
        $trend = $trendPercent > 3 ? 'growing' : ($trendPercent < -3 ? 'declining' : 'stable');

        // Pipeline boost: add weighted pipeline value
        $pipelineBoost = $this->getPipelineRevenueForecast($accountId);

        return [
            'predicted_revenue' => round($predicted, 0),
            'predicted_with_pipeline' => round($predicted + $pipelineBoost, 0),
            'pipeline_boost' => round($pipelineBoost, 0),
            'confidence' => $confidence,
            'confidence_percent' => $confidencePercent,
            'lower_bound' => round(max(0, $predicted - 1.96 * $stdDev), 0),
            'upper_bound' => round($predicted + 1.96 * $stdDev, 0),
            'trend' => $trend,
            'trend_percent' => $trendPercent,
            'method' => 'weighted_linear_regression',
            'historical_data' => $revenueData,
        ];
    }

    /**
     * Forecast pipeline deals likely to close next month.
     * Weights deals by stage probability.
     */
    private function getPipelineRevenueForecast(int $accountId): float
    {
        $stageWeights = [
            Deal::STAGE_PROSPECTING => 0.05,
            Deal::STAGE_QUALIFICATION => 0.15,
            Deal::STAGE_PROPOSAL => 0.40,
            Deal::STAGE_NEGOTIATION => 0.65,
            Deal::STAGE_CLOSING => 0.85,
        ];

        $nextMonthStart = Carbon::now()->addMonth()->startOfMonth();
        $nextMonthEnd = $nextMonthStart->copy()->endOfMonth();

        $openDeals = Deal::where('account_id', $accountId)
            ->where('status', Deal::STATUS_OPEN)
            ->get();

        $forecast = 0;
        foreach ($openDeals as $deal) {
            $weight = $stageWeights[$deal->stage] ?? 0.1;

            // Boost weight if expected close is next month
            if ($deal->expected_close_date && $deal->expected_close_date->between($nextMonthStart, $nextMonthEnd)) {
                $weight = min(1, $weight * 1.5);
            }

            $forecast += ($deal->value ?? 0) * $weight;
        }

        return $forecast;
    }

    // ════════════════════════════════════════════
    //  2. RISK DETECTION
    // ════════════════════════════════════════════

    /**
     * Detect business risks from multiple data sources.
     *
     * Risk levels: critical, high, medium, low
     * Each risk has: level, category, title, description, metric
     */
    public function detectRisks(
        array $revenue,
        array $pipeline,
        array $customers,
        array $financial,
        array $team
    ): array {
        $risks = [];

        // ── Revenue Risks ──
        if ($revenue['mom_growth'] < -20) {
            $risks[] = [
                'level' => 'critical',
                'category' => 'revenue',
                'title' => 'Severe Revenue Decline',
                'description' => "Revenue dropped {$revenue['mom_growth']}% month-over-month. Immediate attention required.",
                'metric' => $revenue['mom_growth'] . '%',
            ];
        } elseif ($revenue['mom_growth'] < -10) {
            $risks[] = [
                'level' => 'high',
                'category' => 'revenue',
                'title' => 'Revenue Declining',
                'description' => "Revenue decreased {$revenue['mom_growth']}% compared to last month.",
                'metric' => $revenue['mom_growth'] . '%',
            ];
        }

        // Consecutive decline
        if ($revenue['consecutive_decline_months'] >= 3) {
            $risks[] = [
                'level' => 'critical',
                'category' => 'revenue',
                'title' => 'Sustained Revenue Downtrend',
                'description' => "Revenue has declined for {$revenue['consecutive_decline_months']} consecutive months.",
                'metric' => $revenue['consecutive_decline_months'] . ' months',
            ];
        }

        // ── Pipeline Risks ──
        if ($pipeline['pipeline_coverage'] < 2) {
            $risks[] = [
                'level' => $pipeline['pipeline_coverage'] < 1 ? 'critical' : 'high',
                'category' => 'pipeline',
                'title' => 'Low Pipeline Coverage',
                'description' => "Pipeline coverage is {$pipeline['pipeline_coverage']}x (healthy is 3-5x). Not enough deals to sustain revenue targets.",
                'metric' => $pipeline['pipeline_coverage'] . 'x',
            ];
        }

        if ($pipeline['deals_stale_count'] > 0) {
            $risks[] = [
                'level' => $pipeline['deals_stale_count'] > 5 ? 'high' : 'medium',
                'category' => 'pipeline',
                'title' => 'Stale Deals in Pipeline',
                'description' => "{$pipeline['deals_stale_count']} deals have not progressed in 30+ days.",
                'metric' => $pipeline['deals_stale_count'] . ' deals',
            ];
        }

        if ($pipeline['win_rate'] < 20) {
            $risks[] = [
                'level' => 'high',
                'category' => 'pipeline',
                'title' => 'Low Win Rate',
                'description' => "Win rate is {$pipeline['win_rate']}% (benchmark: 25-35%). Sales effectiveness needs improvement.",
                'metric' => $pipeline['win_rate'] . '%',
            ];
        }

        // ── Customer/Churn Risks ──
        if ($customers['churn_rate'] > 15) {
            $risks[] = [
                'level' => 'critical',
                'category' => 'customers',
                'title' => 'High Churn Rate',
                'description' => "Churn rate is {$customers['churn_rate']}% — significantly above the 5-10% benchmark.",
                'metric' => $customers['churn_rate'] . '%',
            ];
        } elseif ($customers['churn_rate'] > 10) {
            $risks[] = [
                'level' => 'high',
                'category' => 'customers',
                'title' => 'Elevated Churn Rate',
                'description' => "Churn rate at {$customers['churn_rate']}% exceeds the healthy 5-10% range.",
                'metric' => $customers['churn_rate'] . '%',
            ];
        }

        if ($customers['lead_velocity'] < -15) {
            $risks[] = [
                'level' => 'high',
                'category' => 'customers',
                'title' => 'Lead Generation Slowing',
                'description' => "New leads decreased {$customers['lead_velocity']}% vs last month. Future pipeline at risk.",
                'metric' => $customers['lead_velocity'] . '%',
            ];
        }

        // ── Financial Health Risks ──
        if ($financial['runway_months'] > 0 && $financial['runway_months'] < 6) {
            $risks[] = [
                'level' => $financial['runway_months'] < 3 ? 'critical' : 'high',
                'category' => 'financial',
                'title' => 'Low Cash Runway',
                'description' => "Only {$financial['runway_months']} months of runway remaining at current burn rate.",
                'metric' => $financial['runway_months'] . ' months',
            ];
        }

        if ($financial['profit_margin'] < 0) {
            $risks[] = [
                'level' => 'critical',
                'category' => 'financial',
                'title' => 'Negative Profit Margin',
                'description' => "Operating at {$financial['profit_margin']}% margin — expenses exceed income.",
                'metric' => $financial['profit_margin'] . '%',
            ];
        } elseif ($financial['profit_margin'] < 10) {
            $risks[] = [
                'level' => 'medium',
                'category' => 'financial',
                'title' => 'Thin Profit Margin',
                'description' => "Profit margin at {$financial['profit_margin']}% — limited buffer for unexpected expenses.",
                'metric' => $financial['profit_margin'] . '%',
            ];
        }

        if ($financial['burn_rate_trend'] > 20) {
            $risks[] = [
                'level' => 'high',
                'category' => 'financial',
                'title' => 'Burn Rate Increasing',
                'description' => "Burn rate increased {$financial['burn_rate_trend']}% — expenses growing faster than revenue.",
                'metric' => $financial['burn_rate_trend'] . '%',
            ];
        }

        // ── Team Risks ──
        if ($team['avg_utilization'] < 50) {
            $risks[] = [
                'level' => 'medium',
                'category' => 'team',
                'title' => 'Low Team Utilization',
                'description' => "Team utilization is {$team['avg_utilization']}% — potential over-staffing or under-deployment.",
                'metric' => $team['avg_utilization'] . '%',
            ];
        }

        if ($team['revenue_per_employee'] > 0 && $team['revenue_per_employee_trend'] < -20) {
            $risks[] = [
                'level' => 'medium',
                'category' => 'team',
                'title' => 'Declining Revenue per Employee',
                'description' => "Revenue per employee dropped {$team['revenue_per_employee_trend']}% — productivity may be decreasing.",
                'metric' => $team['revenue_per_employee_trend'] . '%',
            ];
        }

        // Sort by severity
        $severityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        usort($risks, fn ($a, $b) => ($severityOrder[$a['level']] ?? 4) <=> ($severityOrder[$b['level']] ?? 4));

        return $risks;
    }

    // ════════════════════════════════════════════
    //  3. ACTION RECOMMENDATIONS
    // ════════════════════════════════════════════

    /**
     * Generate actionable recommendations based on analysis.
     *
     * Each action has: priority (1-5), category, title, description, impact
     */
    public function suggestActions(
        array $revenue, array $pipeline, array $customers,
        array $marketing, array $financial, array $team, array $risks
    ): array {
        $actions = [];
        $riskCategories = array_column($risks, 'category');
        $riskLevels = array_column($risks, 'level');

        // ── Pipeline Actions ──
        if (in_array('pipeline', $riskCategories)) {
            if ($pipeline['pipeline_coverage'] < 2) {
                $actions[] = [
                    'priority' => 1,
                    'category' => 'sales',
                    'title' => 'Urgently Increase Pipeline',
                    'description' => 'Pipeline coverage is critically low. Increase outbound prospecting by 50%, consider running targeted campaigns, and explore new lead sources.',
                    'impact' => 'high',
                    'timeframe' => 'immediate',
                ];
            }

            if ($pipeline['win_rate'] < 25) {
                $actions[] = [
                    'priority' => 2,
                    'category' => 'sales',
                    'title' => 'Improve Sales Process',
                    'description' => 'Win rate is below benchmark. Review lost deal reasons, improve proposal quality, and consider sales training. Focus on better qualification to avoid wasting time on unwinnable deals.',
                    'impact' => 'high',
                    'timeframe' => '1-2 months',
                ];
            }

            if ($pipeline['deals_stale_count'] > 3) {
                $actions[] = [
                    'priority' => 2,
                    'category' => 'sales',
                    'title' => 'Clean Up Stale Deals',
                    'description' => "Review and address {$pipeline['deals_stale_count']} stale deals. Either re-engage or close them to maintain pipeline accuracy.",
                    'impact' => 'medium',
                    'timeframe' => 'this week',
                ];
            }
        }

        // ── Customer Retention Actions ──
        if ($customers['churn_rate'] > 10) {
            $actions[] = [
                'priority' => 1,
                'category' => 'customers',
                'title' => 'Launch Customer Retention Program',
                'description' => 'High churn rate detected. Implement customer health scoring, schedule proactive check-ins with at-risk accounts, and gather feedback on reasons for churning.',
                'impact' => 'high',
                'timeframe' => 'immediate',
            ];
        }

        if ($customers['lead_velocity'] < -10) {
            $actions[] = [
                'priority' => 2,
                'category' => 'marketing',
                'title' => 'Boost Lead Generation',
                'description' => 'Lead flow is declining. Increase marketing spend, launch new content campaigns, and explore additional channels to maintain pipeline health.',
                'impact' => 'high',
                'timeframe' => '1-2 weeks',
            ];
        }

        // ── Marketing Channel Actions ──
        if (!empty($marketing['best_channel'])) {
            $actions[] = [
                'priority' => 3,
                'category' => 'marketing',
                'title' => "Double Down on {$marketing['best_channel']['label']}",
                'description' => "{$marketing['best_channel']['label']} is your top-performing channel with {$marketing['best_channel']['conversion_rate']}% conversion rate. Increase budget allocation by 20-30% for this channel.",
                'impact' => 'medium',
                'timeframe' => 'next month',
            ];
        }

        if (!empty($marketing['worst_channel'])) {
            $actions[] = [
                'priority' => 4,
                'category' => 'marketing',
                'title' => "Re-evaluate {$marketing['worst_channel']['label']} Channel",
                'description' => "{$marketing['worst_channel']['label']} delivers lowest conversion at {$marketing['worst_channel']['conversion_rate']}%. Consider reducing spend or redesigning the approach.",
                'impact' => 'medium',
                'timeframe' => 'next month',
            ];
        }

        // ── Financial Actions ──
        if ($financial['profit_margin'] < 10) {
            $actions[] = [
                'priority' => 1,
                'category' => 'financial',
                'title' => 'Improve Profit Margins',
                'description' => 'Margin is thin. Review top expense categories for cost-cutting opportunities. Consider pricing adjustments or upselling to existing customers.',
                'impact' => 'high',
                'timeframe' => '1-2 months',
            ];
        }

        if ($financial['burn_rate_trend'] > 15) {
            $actions[] = [
                'priority' => 2,
                'category' => 'financial',
                'title' => 'Control Expense Growth',
                'description' => 'Expenses are growing rapidly. Audit recent spending increases and implement budget controls for discretionary expenses.',
                'impact' => 'high',
                'timeframe' => 'immediate',
            ];
        }

        // ── Team Actions ──
        if ($team['avg_utilization'] < 50 && $team['employee_count'] > 3) {
            $actions[] = [
                'priority' => 3,
                'category' => 'team',
                'title' => 'Optimize Team Deployment',
                'description' => 'Low utilization suggests potential for better resource allocation. Reassign underutilized team members to high-priority projects or client work.',
                'impact' => 'medium',
                'timeframe' => '1-2 weeks',
            ];
        }

        if ($revenue['mom_growth'] > 15 && $team['avg_utilization'] > 85) {
            $actions[] = [
                'priority' => 2,
                'category' => 'team',
                'title' => 'Consider Hiring',
                'description' => "Revenue is growing at {$revenue['mom_growth']}% while team utilization is {$team['avg_utilization']}%. The team is near capacity — plan for strategic hires.",
                'impact' => 'high',
                'timeframe' => '1-2 months',
            ];
        }

        // ── Growth Opportunities ──
        if ($revenue['mom_growth'] > 10 && $pipeline['pipeline_coverage'] >= 3) {
            $actions[] = [
                'priority' => 3,
                'category' => 'growth',
                'title' => 'Capitalize on Growth Momentum',
                'description' => "Strong revenue growth ({$revenue['mom_growth']}%) with healthy pipeline. Consider increasing marketing spend to accelerate growth while conditions are favorable.",
                'impact' => 'medium',
                'timeframe' => 'next quarter',
            ];
        }

        if ($pipeline['avg_deal_size_trend'] > 10) {
            $actions[] = [
                'priority' => 4,
                'category' => 'growth',
                'title' => 'Pursue Larger Deals',
                'description' => 'Average deal size is trending up. Focus sales efforts on mid-market and enterprise segments for higher-value opportunities.',
                'impact' => 'medium',
                'timeframe' => 'ongoing',
            ];
        }

        // Sort by priority
        usort($actions, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return $actions;
    }

    // ════════════════════════════════════════════
    //  DATA ANALYSIS METHODS
    // ════════════════════════════════════════════

    private function analyzeRevenue(int $accountId): array
    {
        $now = Carbon::now();
        $monthlyRevenue = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $rev = (float) Deal::where('account_id', $accountId)
                ->where('status', Deal::STATUS_WON)
                ->whereMonth('updated_at', $date->month)
                ->whereYear('updated_at', $date->year)
                ->sum('value');
            $monthlyRevenue[] = ['month' => $date->format('Y-m'), 'label' => $date->format('M'), 'revenue' => $rev];
        }

        $current = $monthlyRevenue[5]['revenue'] ?? 0;
        $previous = $monthlyRevenue[4]['revenue'] ?? 0;
        $momGrowth = $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : 0;

        // Detect consecutive decline
        $declineMonths = 0;
        for ($i = count($monthlyRevenue) - 1; $i >= 1; $i--) {
            if ($monthlyRevenue[$i]['revenue'] < $monthlyRevenue[$i - 1]['revenue']) {
                $declineMonths++;
            } else {
                break;
            }
        }

        // Yearly revenue
        $ytdRevenue = (float) Deal::where('account_id', $accountId)
            ->where('status', Deal::STATUS_WON)
            ->whereYear('updated_at', $now->year)
            ->sum('value');

        return [
            'current_month' => $current,
            'previous_month' => $previous,
            'mom_growth' => $momGrowth,
            'ytd_revenue' => $ytdRevenue,
            'monthly_trend' => $monthlyRevenue,
            'consecutive_decline_months' => $declineMonths,
            'avg_monthly' => round(array_sum(array_column($monthlyRevenue, 'revenue')) / max(count($monthlyRevenue), 1), 0),
        ];
    }

    private function analyzePipeline(int $accountId): array
    {
        $now = Carbon::now();

        $openDeals = Deal::where('account_id', $accountId)
            ->where('status', Deal::STATUS_OPEN)
            ->get();

        $pipelineValue = (float) $openDeals->sum('value');
        $dealCount = $openDeals->count();
        $avgDealSize = $dealCount > 0 ? round($pipelineValue / $dealCount, 0) : 0;

        // Win rate (last 90 days)
        $last90 = $now->copy()->subDays(90);
        $closedDeals = Deal::where('account_id', $accountId)
            ->whereIn('status', [Deal::STATUS_WON, Deal::STATUS_LOST])
            ->where('updated_at', '>=', $last90)
            ->get();

        $won = $closedDeals->where('status', Deal::STATUS_WON)->count();
        $total = $closedDeals->count();
        $winRate = $total > 0 ? round(($won / $total) * 100, 1) : 0;

        // Pipeline coverage (pipeline / avg monthly revenue)
        $avgMonthlyRevenue = $this->analyzeRevenue($accountId)['avg_monthly'];
        $pipelineCoverage = $avgMonthlyRevenue > 0 ? round($pipelineValue / $avgMonthlyRevenue, 1) : 0;

        // Stale deals (no update in 30+ days)
        $staleDate = $now->copy()->subDays(30);
        $staleDeals = $openDeals->filter(fn ($d) => $d->updated_at < $staleDate)->count();

        // Stage distribution
        $stages = [
            Deal::STAGE_PROSPECTING => 'Prospecting',
            Deal::STAGE_QUALIFICATION => 'Qualification',
            Deal::STAGE_PROPOSAL => 'Proposal',
            Deal::STAGE_NEGOTIATION => 'Negotiation',
            Deal::STAGE_CLOSING => 'Closing',
        ];
        $stageDistribution = [];
        foreach ($stages as $key => $label) {
            $inStage = $openDeals->where('stage', $key);
            $stageDistribution[] = [
                'stage' => $key,
                'label' => $label,
                'count' => $inStage->count(),
                'value' => (float) $inStage->sum('value'),
            ];
        }

        // Avg deal size trend
        $prevAvgDealSize = 0;
        $prev90 = $now->copy()->subDays(180);
        $olderDeals = Deal::where('account_id', $accountId)
            ->where('status', Deal::STATUS_WON)
            ->whereBetween('updated_at', [$prev90, $last90])
            ->get();
        if ($olderDeals->count() > 0) {
            $prevAvgDealSize = round((float) $olderDeals->sum('value') / $olderDeals->count(), 0);
        }
        $avgDealSizeTrend = $prevAvgDealSize > 0
            ? round((($avgDealSize - $prevAvgDealSize) / $prevAvgDealSize) * 100, 1)
            : 0;

        return [
            'pipeline_value' => $pipelineValue,
            'deal_count' => $dealCount,
            'avg_deal_size' => $avgDealSize,
            'win_rate' => $winRate,
            'pipeline_coverage' => $pipelineCoverage,
            'deals_stale_count' => $staleDeals,
            'stage_distribution' => $stageDistribution,
            'avg_deal_size_trend' => $avgDealSizeTrend,
            'deals_won_90d' => $won,
            'deals_lost_90d' => $total - $won,
        ];
    }

    private function analyzeCustomers(int $accountId): array
    {
        $now = Carbon::now();
        $last30 = $now->copy()->subDays(30);
        $prev30Start = $now->copy()->subDays(60);

        // Lead metrics
        $newLeadsThisMonth = Lead::where('account_id', $accountId)
            ->where('created_at', '>=', $last30)->count();
        $newLeadsPrevMonth = Lead::where('account_id', $accountId)
            ->whereBetween('created_at', [$prev30Start, $last30])->count();

        $leadVelocity = $newLeadsPrevMonth > 0
            ? round((($newLeadsThisMonth - $newLeadsPrevMonth) / $newLeadsPrevMonth) * 100, 1)
            : ($newLeadsThisMonth > 0 ? 100 : 0);

        // Churn (lost leads / total qualified)
        $totalQualified = Lead::where('account_id', $accountId)
            ->where('created_at', '>=', $now->copy()->subDays(90))
            ->whereIn('status', [Lead::STATUS_QUALIFIED, Lead::STATUS_WON, Lead::STATUS_LOST])
            ->count();
        $lost = Lead::where('account_id', $accountId)
            ->where('status', Lead::STATUS_LOST)
            ->where('updated_at', '>=', $now->copy()->subDays(90))
            ->count();
        $churnRate = $totalQualified > 0 ? round(($lost / $totalQualified) * 100, 1) : 0;

        // Lead quality
        $avgLeadScore = (float) Lead::where('account_id', $accountId)
            ->where('created_at', '>=', $last30)
            ->whereNotNull('score')
            ->avg('score');

        // Source breakdown
        $sourceBreakdown = Lead::where('account_id', $accountId)
            ->where('created_at', '>=', $now->copy()->subDays(90))
            ->select('source', DB::raw('COUNT(*) as count'))
            ->groupBy('source')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => [
                'source' => $r->source,
                'label' => Lead::getSources()[$r->source] ?? $r->source,
                'count' => $r->count,
            ])
            ->toArray();

        return [
            'new_leads_this_month' => $newLeadsThisMonth,
            'new_leads_prev_month' => $newLeadsPrevMonth,
            'lead_velocity' => $leadVelocity,
            'churn_rate' => $churnRate,
            'lost_count' => $lost,
            'avg_lead_score' => round($avgLeadScore, 0),
            'source_breakdown' => $sourceBreakdown,
            'total_active_leads' => Lead::where('account_id', $accountId)
                ->whereNotIn('status', [Lead::STATUS_LOST, Lead::STATUS_WON])->count(),
        ];
    }

    private function analyzeMarketing(int $accountId): array
    {
        $now = Carbon::now();
        $last90 = $now->copy()->subDays(90);

        // Channel performance (leads by source → conversion to deal)
        $sources = Lead::getSources();
        $channels = [];

        foreach ($sources as $key => $label) {
            $totalLeads = Lead::where('account_id', $accountId)
                ->where('source', $key)
                ->where('created_at', '>=', $last90)
                ->count();

            $convertedLeads = Lead::where('account_id', $accountId)
                ->where('source', $key)
                ->where('created_at', '>=', $last90)
                ->whereHas('deal')
                ->count();

            $wonRevenue = (float) Deal::where('account_id', $accountId)
                ->where('status', Deal::STATUS_WON)
                ->where('updated_at', '>=', $last90)
                ->whereHas('lead', fn ($q) => $q->where('source', $key))
                ->sum('value');

            $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;

            if ($totalLeads > 0) {
                $channels[] = [
                    'source' => $key,
                    'label' => $label,
                    'total_leads' => $totalLeads,
                    'converted' => $convertedLeads,
                    'conversion_rate' => $conversionRate,
                    'revenue' => $wonRevenue,
                    'roi_score' => $totalLeads > 0 ? round($wonRevenue / $totalLeads, 0) : 0,
                ];
            }
        }

        // Sort by ROI
        usort($channels, fn ($a, $b) => $b['roi_score'] <=> $a['roi_score']);

        return [
            'channels' => $channels,
            'best_channel' => $channels[0] ?? null,
            'worst_channel' => end($channels) ?: null,
            'total_leads_90d' => array_sum(array_column($channels, 'total_leads')),
            'avg_conversion' => count($channels) > 0
                ? round(array_sum(array_column($channels, 'conversion_rate')) / count($channels), 1)
                : 0,
        ];
    }

    private function analyzeFinancialHealth(int $accountId): array
    {
        $now = Carbon::now();

        $curIncome = (float) FinancialTransaction::where('account_id', $accountId)
            ->income()->inMonth($now->year, $now->month)->sum('amount');
        $curExpenses = (float) FinancialTransaction::where('account_id', $accountId)
            ->expense()->inMonth($now->year, $now->month)->sum('amount');

        $profitMargin = $curIncome > 0 ? round((($curIncome - $curExpenses) / $curIncome) * 100, 1) : 0;

        // Burn rate (3-month avg)
        $totalBurn = 0;
        for ($i = 1; $i <= 3; $i++) {
            $d = $now->copy()->subMonths($i);
            $totalBurn += (float) FinancialTransaction::where('account_id', $accountId)
                ->expense()->inMonth($d->year, $d->month)->sum('amount');
        }
        $burnRate = round($totalBurn / 3, 0);

        // Burn rate trend
        $olderBurn = 0;
        for ($i = 4; $i <= 6; $i++) {
            $d = $now->copy()->subMonths($i);
            $olderBurn += (float) FinancialTransaction::where('account_id', $accountId)
                ->expense()->inMonth($d->year, $d->month)->sum('amount');
        }
        $olderBurnAvg = round($olderBurn / 3, 0);
        $burnRateTrend = $olderBurnAvg > 0 ? round((($burnRate - $olderBurnAvg) / $olderBurnAvg) * 100, 1) : 0;

        // Cash balance
        $allIncome = (float) FinancialTransaction::where('account_id', $accountId)->income()->sum('amount');
        $allExpenses = (float) FinancialTransaction::where('account_id', $accountId)->expense()->sum('amount');
        $cashBalance = $allIncome - $allExpenses;

        $runwayMonths = $burnRate > 0 ? round($cashBalance / $burnRate, 1) : 0;

        return [
            'current_income' => $curIncome,
            'current_expenses' => $curExpenses,
            'profit_margin' => $profitMargin,
            'burn_rate' => $burnRate,
            'burn_rate_trend' => $burnRateTrend,
            'cash_balance' => $cashBalance,
            'runway_months' => max(0, $runwayMonths),
        ];
    }

    private function analyzeTeam(int $accountId): array
    {
        $now = Carbon::now();

        $employees = EmployeeProfile::where('account_id', $accountId)->with('user')->get();
        $employeeCount = $employees->count();

        if ($employeeCount === 0) {
            return [
                'employee_count' => 0,
                'avg_utilization' => 0,
                'revenue_per_employee' => 0,
                'revenue_per_employee_trend' => 0,
                'total_salary_cost' => 0,
            ];
        }

        // Current month revenue / employees
        $monthlyRevenue = (float) Deal::where('account_id', $accountId)
            ->where('status', Deal::STATUS_WON)
            ->whereMonth('updated_at', $now->month)
            ->whereYear('updated_at', $now->year)
            ->sum('value');

        $revenuePerEmployee = round($monthlyRevenue / $employeeCount, 0);

        // Previous month for trend
        $prev = $now->copy()->subMonth();
        $prevRevenue = (float) Deal::where('account_id', $accountId)
            ->where('status', Deal::STATUS_WON)
            ->whereMonth('updated_at', $prev->month)
            ->whereYear('updated_at', $prev->year)
            ->sum('value');
        $prevRevenuePerEmp = $employeeCount > 0 ? round($prevRevenue / $employeeCount, 0) : 0;
        $rpetrend = $prevRevenuePerEmp > 0
            ? round((($revenuePerEmployee - $prevRevenuePerEmp) / $prevRevenuePerEmp) * 100, 1)
            : 0;

        // Utilization (from performance reviews or estimate)
        $totalSalary = (float) $employees->sum('base_salary');

        return [
            'employee_count' => $employeeCount,
            'avg_utilization' => 70, // Default estimate; will be enhanced with real data
            'revenue_per_employee' => $revenuePerEmployee,
            'revenue_per_employee_trend' => $rpetrend,
            'total_salary_cost' => $totalSalary,
        ];
    }
}
