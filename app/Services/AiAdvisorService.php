<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\FinancialTransaction;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AiAdvisorService
 * ────────────────
 * The "Brain" of the BOS. Generates proactive insights, alerts, and 
 * daily briefings for the CEO/Executives.
 */
class AiAdvisorService
{
    public function __construct(
        private SalesIntelligenceService $salesIntel,
        private ExecutiveDashboardService $dashboardSvc,
        private CapacityPlanningService $capacitySvc,
        private ApprovalWorkflowService $approvalSvc
    ) {}

    /**
     * Generate a comprehensive Executive Daily Briefing.
     */
    public function generateDailyBriefing(int $accountId): array
    {
        $risks = $this->salesIntel->detectRisksAndRecommendations($accountId);
        $forecast = $this->salesIntel->getRevenueForecast($accountId);
        $bottlenecks = $this->getBottlenecks($accountId);
        $hiringAlert = $this->capacitySvc->getHiringInsights($accountId);
        $approvals = $this->approvalSvc->getPendingRequests($accountId);
        
        return [
            'date' => now()->format('Y-m-d'),
            'summary' => $this->generatePulseSummary($accountId, $forecast),
            'critical_alerts' => $this->getCriticalAlerts($accountId, $risks, $hiringAlert),
            'opportunities' => $this->getTopOpportunities($accountId),
            'financial_health' => $this->getPredictiveCashflowStatus($accountId),
            'governance_items' => $approvals,
            'bottlenecks' => $bottlenecks,
            'recommended_actions' => $this->consolidateActions($risks, $bottlenecks),
        ];
    }

    /**
     * Pulse Summary: 1-sentence state of the business.
     */
    private function generatePulseSummary(int $accountId, array $forecast): array
    {
        $attainment = $forecast['attainment_pct'] ?? 0;
        
        $status = 'stable';
        if ($attainment < 50 && now()->day > 15) $status = 'behind';
        if ($attainment > 80) $status = 'excellent';

        return [
            'status' => $status,
            'vi' => $this->getPulseText($status, $attainment, 'vi'),
            'en' => $this->getPulseText($status, $attainment, 'en'),
        ];
    }

    private function getPulseText(string $status, float $attainment, string $lang): string
    {
        $texts = [
            'vi' => [
                'behind' => "Doanh thu đang chậm tiến độ ({$attainment}%). Cần tập trung chốt các deal trong nhóm Commit.",
                'excellent' => "Hiệu suất tuyệt vời! Đã đạt {$attainment}% kế hoạch. Có thể xem xét đẩy mạnh Marketing.",
                'stable' => "Kinh doanh ổn định. Đã đạt {$attainment}% mục tiêu tháng.",
            ],
            'en' => [
                'behind' => "Revenue is behind schedule ({$attainment}%). Focus on closing deals in the Commit bucket.",
                'excellent' => "Excellent performance! Reached {$attainment}% of target. Consider increasing Marketing spend.",
                'stable' => "Business is steady. Reached {$attainment}% of monthly target.",
            ]
        ];
        return $texts[$lang][$status];
    }

    /**
     * Critical Alerts: Real-time bottlenecks or risks.
     */
    private function getCriticalAlerts(int $accountId, array $risks, ?array $hiringAlert = null): array
    {
        $alerts = [];
        
        // 0. Hiring/Capacity Alert
        if ($hiringAlert) {
            $alerts[] = [
                'type' => 'capacity_overload',
                'severity' => $hiringAlert['severity'],
                'title_vi' => "CẢNH BÁO VẬN HÀNH: " . $hiringAlert['v_vi'],
                'title_en' => "OPERATIONAL ALERT: " . $hiringAlert['v_en'],
                'action_vi' => $hiringAlert['action_vi'],
                'action_en' => $hiringAlert['action_en'],
            ];
        }

        // 1. Stagnant high-value deals
        $stagnantDeals = collect($risks['at_risk_deals'])
            ->where('value', '>', 100_000_000)
            ->where('health_score', '<', 40)
            ->take(3);

        foreach ($stagnantDeals as $deal) {
            $alerts[] = [
                'type' => 'deal_stagnant',
                'severity' => 'high',
                'title_vi' => "Deal lớn đình trệ: {$deal['deal_title']}",
                'title_en' => "High-value deal stagnant: {$deal['deal_title']}",
                'meta' => ['value' => $deal['value'], 'owner' => $deal['owner']],
                'action_vi' => "CEO nên can thiệp hỗ trợ sales rep {$deal['owner']}",
                'action_en' => "CEO intervention recommended to support {$deal['owner']}",
            ];
        }

        // 2. Burn rate warning
        $finance = $this->dashboardSvc->getMetrics($accountId)['finance'];
        if ($finance['monthly_profit'] < 0 && abs($finance['monthly_profit']) > ($finance['monthly_income'] * 0.5)) {
            $alerts[] = [
                'type' => 'burn_rate_high',
                'severity' => 'critical',
                'title_vi' => "Cảnh báo Burn Rate cao!",
                'title_en' => "High Burn Rate Warning!",
                'meta' => ['loss' => abs($finance['monthly_profit'])],
                'action_vi' => "Rà soát lại danh sách chi phí vận hành ngay lập tức",
                'action_en' => "Review operational expenses list immediately",
            ];
        }

        return $alerts;
    }

    /**
     * Predictive Cashflow Statement.
     */
    public function getPredictiveCashflowStatus(int $accountId): array
    {
        $now = now();
        $metrics = $this->dashboardSvc->getMetrics($accountId);
        $avgMonthlyExpense = $metrics['finance']['burn_rate'] ?: 0;
        
        // Pipeline value weighted by win probability
        $incomingDeals = Deal::where('account_id', $accountId)
            ->where('status', Deal::STATUS_OPEN)
            ->where('expected_close_date', '>=', $now)
            ->where('expected_close_date', '<=', $now->copy()->addMonths(3))
            ->get();

        $weightedRevenue = $incomingDeals->sum(fn($d) => ($d->value * $d->win_probability / 100));

        // Simplified projection: Cash + Weighted Revenue - (Expenses * 3 months)
        // Assume starting cash is 0 for this demo logic if no actual balance tracked
        $projectedRunway = $avgMonthlyExpense > 0 ? floor($weightedRevenue / $avgMonthlyExpense) : 12;

        return [
            'weighted_pipeline_3m' => round($weightedRevenue),
            'projected_monthly_burn' => round($avgMonthlyExpense),
            'runway_months' => $projectedRunway,
            'confidence' => $incomingDeals->count() > 5 ? 'high' : 'low',
            'vi' => "Dự báo doanh thu chờ (weighted) trong 3 tháng tới: " . number_format($weightedRevenue) . "đ",
            'en' => "Weighted revenue forecast for next 3 months: " . number_format($weightedRevenue) . "đ",
        ];
    }

    private function getTopOpportunities(int $accountId): array
    {
        return Deal::where('account_id', $accountId)
            ->where('status', Deal::STATUS_OPEN)
            ->where('win_probability', '>', 70)
            ->orderByDesc('value')
            ->limit(3)
            ->get()
            ->map(fn($d) => [
                'title' => $d->title,
                'value' => (float)$d->value,
                'probability' => $d->win_probability,
                'owner' => $d->assignedUser?->name
            ])->toArray();
    }

    private function consolidateActions(array $risks, array $bottlenecks = []): array
    {
        $actions = [];
        foreach ($risks['at_risk_deals'] as $deal) {
            foreach ($deal['recommended_actions'] as $act) {
                $actions[] = $act;
            }
        }
        foreach ($bottlenecks as $bn) {
            $actions[] = [
                'vi' => "Xử lý tắc nghẽn tại {$bn['stage_vi']}: {$bn['action_vi']}",
                'en' => "Resolve friction at {$bn['stage_en']}: {$bn['action_en']}",
                'priority' => 'high',
                'type' => 'operational'
            ];
        }
        return collect($actions)->unique('vi')->take(6)->values()->toArray();
    }

    /**
     * Identify stages where deals are getting "stuck".
     */
    public function getBottlenecks(int $accountId): array
    {
        $deals = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_OPEN)->get();
        if ($deals->isEmpty()) return [];

        $stages = collect(Deal::getStages());
        $friction = [];

        foreach ($stages as $key => $label) {
            $stageDeals = $deals->where('stage', $key);
            if ($stageDeals->isEmpty()) continue;

            $avgAge = $stageDeals->avg('days_in_stage');
            $stuckCount = $stageDeals->where('days_in_stage', '>', 21)->count();
            
            // If > 40% of deals in this stage are >= 21 days
            if ($stuckCount > 0 && ($stuckCount / $stageDeals->count()) > 0.4) {
                $friction[] = [
                    'stage' => $key,
                    'stage_vi' => $label,
                    'stage_en' => $label,
                    'issue_vi' => "Có {$stuckCount} deal bị tắc nghẽn hơn 3 tuần",
                    'issue_en' => "{$stuckCount} deals stuck for 3+ weeks",
                    'avg_age' => round($avgAge, 1),
                    'severity' => 'medium',
                    'action_vi' => "Yêu cầu manager review các deal 'ngâm' quá lâu",
                    'action_en' => "Request manager review for long-stagnant deals",
                ];
            }
        }

        return $friction;
    }

    /**
     * What-if Simulator: Revenue projection models.
     */
    public function runWhatIfSimulation(int $accountId, array $params): array
    {
        $winRateBoost = ($params['win_rate_boost'] ?? 0) / 100;     // e.g., 5 for +5%
        $hiringCount = $params['hiring_count'] ?? 0;              // additional sales reps
        
        $current = $this->salesIntel->calculateSalesVelocity($accountId);
        
        // Base variables
        $dealsPerRep = 15; // assumption for capacity
        $avgValue = $current['avg_deal_value'];
        $winRate = ($current['win_rate'] / 100) + $winRateBoost;
        $cycleDays = $current['avg_sales_cycle_days'];
        
        // New calculation
        $newOpenDeals = $current['open_deals'] + ($hiringCount * $dealsPerRep);
        $newVelocity = ($newOpenDeals * $avgValue * $winRate) / $cycleDays;
        
        return [
            'original_monthly' => $current['monthly_projection'],
            'simulated_monthly' => round($newVelocity * 30),
            'lift_value' => round(($newVelocity * 30) - $current['monthly_projection']),
            'lift_percent' => $current['monthly_projection'] > 0 
                ? round((($newVelocity * 30) / $current['monthly_projection'] - 1) * 100, 1)
                : 0,
            'details' => [
                'win_rate_used' => round($winRate * 100, 1),
                'deals_used' => $newOpenDeals,
            ]
        ];
    }
}
