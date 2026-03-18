<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\LeadAssignmentRule;
use App\Models\SalesTarget;
use App\Models\SalesVelocitySnapshot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * SalesIntelligenceService
 * ─────────────────────────
 * Core engine for the redesigned Sales Module:
 *   - Win probability calculation
 *   - Deal health scoring
 *   - Sales velocity & forecasting
 *   - Revenue forecasting (3 categories)
 *   - Lead auto-assignment
 *   - AI-driven risk detection & action recommendations
 *   - Sales rep KPI tracking
 */
class SalesIntelligenceService
{
    // ═══════════════════════════════════════════════════════
    //  STAGE WIN PROBABILITIES (configurable baseline)
    // ═══════════════════════════════════════════════════════

    public static array $stageProbabilities = [
        Deal::STAGE_PROSPECTING => 10,
        Deal::STAGE_QUALIFICATION => 25,
        Deal::STAGE_PROPOSAL => 50,
        Deal::STAGE_NEGOTIATION => 75,
        Deal::STAGE_CLOSING => 90,
    ];

    // ═══════════════════════════════════════════════════════
    //  WIN PROBABILITY
    // ═══════════════════════════════════════════════════════

    /**
     * Calculate dynamic win probability for a deal.
     * Factors: stage baseline + historical win rate + recency/engagement + overdue penalty.
     */
    public function calculateWinProbability(Deal $deal): int
    {
        $base = self::$stageProbabilities[$deal->stage] ?? 10;

        // Historical win rate for this rep at this stage
        $historicalWinRate = $this->getRepWinRateAtStage($deal->account_id, $deal->assigned_to, $deal->stage);
        $historical = $historicalWinRate > 0 ? ($historicalWinRate * 100) : $base;

        // Blend base + historical (60/40)
        $probability = round($base * 0.6 + $historical * 0.4);

        // Engagement boost: recent activity in last 7 days
        $lastActivity = $deal->last_activity_at;
        if ($lastActivity) {
            $daysSince = now()->diffInDays($lastActivity);
            if ($daysSince <= 3) $probability += 8;
            elseif ($daysSince <= 7) $probability += 4;
            elseif ($daysSince > 21) $probability -= 10; // stale deal
            elseif ($daysSince > 14) $probability -= 5;
        } else {
            $probability -= 5; // no activity logged
        }

        // Close date pressure
        if ($deal->expected_close_date) {
            $daysToClose = now()->diffInDays($deal->expected_close_date, false);
            if ($daysToClose < 0) $probability -= 15;       // overdue
            elseif ($daysToClose <= 7) $probability += 5;   // closing soon
        }

        // Deal size factor (larger deals historically lower close rate)
        if ($deal->value > 500_000_000) $probability -= 5;   // > 500M VND
        elseif ($deal->value > 100_000_000) $probability -= 2;

        return max(1, min(99, $probability));
    }

    /**
     * Get historical win rate for a rep at a given stage.
     */
    private function getRepWinRateAtStage(int $accountId, ?int $userId, string $stage): float
    {
        if (!$userId) return 0;

        $cacheKey = "win_rate:{$accountId}:{$userId}:{$stage}";
        return Cache::remember($cacheKey, 3600, function () use ($accountId, $userId, $stage) {
            $deals = Deal::where('account_id', $accountId)
                ->where('assigned_to', $userId)
                ->whereIn('status', [Deal::STATUS_WON, Deal::STATUS_LOST])
                ->get();

            // Filter deals that went through this stage (check history)
            $throughStage = $deals->filter(function ($d) use ($stage) {
                $history = $d->stage_history ?? [];
                return collect($history)->contains(fn ($h) => ($h['stage'] ?? '') === $stage)
                    || $d->stage === $stage;
            });

            if ($throughStage->isEmpty()) return 0;
            $won = $throughStage->where('status', Deal::STATUS_WON)->count();
            return $won / $throughStage->count();
        });
    }

    // ═══════════════════════════════════════════════════════
    //  DEAL HEALTH SCORE (0–100)
    // ═══════════════════════════════════════════════════════

    /**
     * Calculate deal health score.
     * Considers: recency of activity, days in stage, close date, value completeness, next steps defined.
     */
    public function calculateDealHealth(Deal $deal): array
    {
        $score = 50; // start neutral
        $issues = [];
        $positives = [];

        // Activity recency (max +30 / -30)
        $lastActivity = $deal->last_activity_at;
        if ($lastActivity) {
            $days = now()->diffInDays($lastActivity);
            if ($days == 0) { $score += 20; $positives[] = 'Hoạt động hôm nay / Activity today'; }
            elseif ($days <= 3) { $score += 15; $positives[] = "Hoạt động {$days} ngày trước / {$days}d ago"; }
            elseif ($days <= 7) { $score += 8; }
            elseif ($days <= 14) { $score -= 10; $issues[] = 'Không hoạt động 14+ ngày / No activity 14+ days'; }
            elseif ($days <= 30) { $score -= 20; $issues[] = 'Không hoạt động 30+ ngày / No activity 30+ days'; }
            else { $score -= 30; $issues[] = 'Không hoạt động 30+ ngày / Stale deal'; }
        } else {
            $score -= 20;
            $issues[] = 'Chưa có hoạt động nào / No activities logged';
        }

        // Days in current stage (stagnation)
        $daysInStage = $deal->days_in_stage ?? 0;
        $stageThresholds = [Deal::STAGE_PROSPECTING => 14, Deal::STAGE_QUALIFICATION => 10, Deal::STAGE_PROPOSAL => 14, Deal::STAGE_NEGOTIATION => 21, Deal::STAGE_CLOSING => 14];
        $threshold = $stageThresholds[$deal->stage] ?? 14;
        if ($daysInStage > $threshold * 2) { $score -= 20; $issues[] = "Đình trệ {$daysInStage} ngày / Stagnated {$daysInStage}d"; }
        elseif ($daysInStage > $threshold) { $score -= 10; $issues[] = "Chậm tiến trình / Slow progression"; }

        // Close date
        if ($deal->expected_close_date) {
            $daysToClose = now()->diffInDays($deal->expected_close_date, false);
            if ($daysToClose < 0) { $score -= 20; $issues[] = 'Quá hạn đóng / Overdue close date'; }
            elseif ($daysToClose <= 7 && in_array($deal->stage, [Deal::STAGE_PROSPECTING, Deal::STAGE_QUALIFICATION])) {
                $score -= 15; $issues[] = 'Giai đoạn quá sớm so với ngày đóng / Too early stage for close date';
            }
        } else {
            $score -= 10;
            $issues[] = 'Chưa có ngày đóng dự kiến / No expected close date';
        }

        // Completeness bonuses
        if ($deal->value) { $score += 5; $positives[] = 'Có giá trị deal / Deal value set'; }
        if (!empty($deal->next_steps)) { $score += 8; $positives[] = 'Có bước tiếp theo / Next steps defined'; }
        if (!empty($deal->pain_points)) { $score += 5; $positives[] = 'Đã xác định pain points'; }

        // Activity count bonus
        if ($deal->activity_count >= 5) { $score += 7; $positives[] = "{$deal->activity_count} hoạt động / activities"; }

        $health = max(0, min(100, $score));
        $atRisk = $health < 35 || !empty(array_filter($issues, fn ($i) => str_contains($i, 'Quá hạn') || str_contains($i, 'Stale')));

        return [
            'score' => $health,
            'at_risk' => $atRisk,
            'issues' => $issues,
            'positives' => $positives,
            'label' => $health >= 70 ? 'healthy' : ($health >= 40 ? 'at_risk' : 'critical'),
        ];
    }

    // ═══════════════════════════════════════════════════════
    //  REVENUE FORECASTING
    // ═══════════════════════════════════════════════════════

    /**
     * Generate 3-bucket revenue forecast for the current period.
     * - commit: deals in closing + negotiation with high probability
     * - best_case: all open deals weighted by probability
     * - pipeline: raw total value of all open deals
     */
    public function getRevenueForecast(int $accountId, ?int $userId = null, ?string $period = null): array
    {
        $period = $period ?? now()->format('Y-m');
        [$year, $month] = explode('-', $period);

        $query = Deal::where('account_id', $accountId)
            ->where('status', Deal::STATUS_OPEN)
            ->whereYear('expected_close_date', $year)
            ->whereMonth('expected_close_date', $month);

        if ($userId) $query->where('assigned_to', $userId);

        $openDeals = $query->get();

        // Commit: high-confidence deals (probability >= 75%)
        $commit = $openDeals->where('win_probability', '>=', 75)->sum('value');

        // Best Case: all open deals × probability
        $bestCase = $openDeals->sum(fn ($d) => ($d->value ?? 0) * ($d->win_probability ?? 10) / 100);

        // Pipeline: raw total
        $pipeline = $openDeals->sum('value');

        // Won this period (actual)
        $wonQuery = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_WON)
            ->whereYear('updated_at', $year)->whereMonth('updated_at', $month);
        if ($userId) $wonQuery->where('assigned_to', $userId);
        $wonActual = $wonQuery->sum('value');

        // Target
        $target = SalesTarget::where('account_id', $accountId)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->where('year', $year)->where('month', $month)
            ->where('period_type', 'month')->value('revenue_target') ?? 0;

        $attainment = $target > 0 ? round(($wonActual / $target) * 100, 1) : null;

        return [
            'period' => $period,
            'commit' => round($commit),
            'best_case' => round($bestCase),
            'pipeline' => round($pipeline),
            'won_actual' => round($wonActual),
            'target' => round($target),
            'attainment_pct' => $attainment,
            'gap_to_commit' => max(0, $target - $wonActual - $commit),
            'deal_count' => $openDeals->count(),
        ];
    }

    // ═══════════════════════════════════════════════════════
    //  SALES VELOCITY
    // ═══════════════════════════════════════════════════════

    /**
     * Sales Velocity = (Open Deals × Avg Deal Value × Win Rate) / Avg Sales Cycle Days
     * Unit: revenue per day
     */
    public function calculateSalesVelocity(int $accountId, ?int $userId = null): array
    {
        $since = now()->subDays(90);

        $query = Deal::where('account_id', $accountId);
        if ($userId) $query->where('assigned_to', $userId);

        $openDeals = (clone $query)->where('status', Deal::STATUS_OPEN)->count();
        $avgDealValue = (clone $query)->where('status', Deal::STATUS_WON)->where('updated_at', '>=', $since)->avg('value') ?? 0;

        $closedDeals = (clone $query)->whereIn('status', [Deal::STATUS_WON, Deal::STATUS_LOST])->where('updated_at', '>=', $since)->count();
        $wonDeals = (clone $query)->where('status', Deal::STATUS_WON)->where('updated_at', '>=', $since)->count();
        $winRate = $closedDeals > 0 ? $wonDeals / $closedDeals : 0;

        // Avg sales cycle from closed won deals with days_to_close
        $avgCycle = (clone $query)->where('status', Deal::STATUS_WON)->whereNotNull('days_to_close')->avg('days_to_close') ?? 30;
        $avgCycle = max($avgCycle, 1);

        $velocity = ($openDeals * $avgDealValue * $winRate) / $avgCycle;

        return [
            'open_deals' => $openDeals,
            'avg_deal_value' => round($avgDealValue),
            'win_rate' => round($winRate * 100, 1),
            'avg_sales_cycle_days' => round($avgCycle, 1),
            'daily_velocity' => round($velocity),
            'monthly_projection' => round($velocity * 30),
        ];
    }

    // ═══════════════════════════════════════════════════════
    //  AI RISK DETECTION & RECOMMENDATIONS
    // ═══════════════════════════════════════════════════════

    /**
     * Analyze deals for risks and generate recommended actions.
     */
    public function detectRisksAndRecommendations(int $accountId): array
    {
        $riskyDeals = Deal::where('account_id', $accountId)
            ->where('status', Deal::STATUS_OPEN)
            ->where(function ($q) {
                $q->where('at_risk', true)
                  ->orWhere('health_score', '<', 35)
                  ->orWhere(fn ($q2) => $q2->where('expected_close_date', '<', now())->where('status', Deal::STATUS_OPEN))
                  ->orWhere(fn ($q2) => $q2->whereNull('last_activity_at')->orWhere('last_activity_at', '<', now()->subDays(21)));
            })
            ->with(['assignedUser:id,first_name,last_name', 'lead:id,name,company'])
            ->limit(20)->get();

        $risks = $riskyDeals->map(function ($deal) {
            $health = $this->calculateDealHealth($deal);
            return [
                'deal_id' => $deal->id,
                'deal_title' => $deal->title,
                'company' => $deal->lead?->company,
                'value' => (float) $deal->value,
                'stage' => $deal->stage,
                'owner' => $deal->assignedUser?->name,
                'health_score' => $health['score'],
                'issues' => $health['issues'],
                'recommended_actions' => $this->generateActions($deal, $health),
            ];
        });

        // Account-level insights
        $openDeals = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_OPEN)->get();
        $overdueCount = $openDeals->filter(fn ($d) => $d->expected_close_date && Carbon::parse($d->expected_close_date)->isPast())->count();
        $staleCount = $openDeals->filter(fn ($d) => !$d->last_activity_at || Carbon::parse($d->last_activity_at)->lt(now()->subDays(14)))->count();
        $noValueCount = $openDeals->filter(fn ($d) => !$d->value || $d->value == 0)->count();

        return [
            'at_risk_deals' => $risks->toArray(),
            'insights' => [
                [
                    'type' => 'overdue_deals',
                    'severity' => $overdueCount > 5 ? 'high' : ($overdueCount > 0 ? 'medium' : 'low'),
                    'message_vi' => "{$overdueCount} deals quá hạn đóng",
                    'message_en' => "{$overdueCount} deals past close date",
                    'count' => $overdueCount,
                ],
                [
                    'type' => 'stale_deals',
                    'severity' => $staleCount > 10 ? 'high' : ($staleCount > 3 ? 'medium' : 'low'),
                    'message_vi' => "{$staleCount} deals không hoạt động 14+ ngày",
                    'message_en' => "{$staleCount} deals with no activity in 14+ days",
                    'count' => $staleCount,
                ],
                [
                    'type' => 'no_value_deals',
                    'severity' => $noValueCount > 0 ? 'medium' : 'low',
                    'message_vi' => "{$noValueCount} deals chưa có giá trị",
                    'message_en' => "{$noValueCount} deals missing value",
                    'count' => $noValueCount,
                ],
            ],
        ];
    }

    private function generateActions(Deal $deal, array $health): array
    {
        $actions = [];

        foreach ($health['issues'] as $issue) {
            if (str_contains($issue, 'hoạt động') || str_contains($issue, 'activity')) {
                $actions[] = ['vi' => 'Gọi điện hoặc gửi email check-in ngay hôm nay', 'en' => 'Schedule a call or send a check-in email today', 'priority' => 'high', 'type' => 'activity'];
            }
            if (str_contains($issue, 'Đình trệ') || str_contains($issue, 'Stagnated')) {
                $actions[] = ['vi' => 'Xem xét điều chỉnh chiến lược hoặc leo thang', 'en' => 'Consider strategy adjustment or escalation', 'priority' => 'high', 'type' => 'strategy'];
            }
            if (str_contains($issue, 'Quá hạn') || str_contains($issue, 'Overdue')) {
                $actions[] = ['vi' => 'Cập nhật ngày đóng dự kiến hoặc đánh dấu lost', 'en' => 'Update expected close date or mark as lost', 'priority' => 'medium', 'type' => 'admin'];
            }
            if (str_contains($issue, 'ngày đóng') || str_contains($issue, 'close date')) {
                $actions[] = ['vi' => 'Thêm ngày đóng dự kiến vào deal', 'en' => 'Add expected close date to this deal', 'priority' => 'medium', 'type' => 'admin'];
            }
        }

        // Stage-specific recommendations
        if ($deal->stage === Deal::STAGE_PROPOSAL) {
            $actions[] = ['vi' => 'Theo dõi đề xuất: liên hệ xác nhận khách hàng đã xem xét', 'en' => 'Follow up on proposal: confirm prospect has reviewed', 'priority' => 'high', 'type' => 'followup'];
        }
        if ($deal->stage === Deal::STAGE_NEGOTIATION) {
            $actions[] = ['vi' => 'Chuẩn bị phương án nhượng bộ và điều kiện cuối cùng', 'en' => 'Prepare concession strategy and final terms', 'priority' => 'medium', 'type' => 'strategy'];
        }

        return array_slice($actions, 0, 3); // Max 3 actions
    }

    // ═══════════════════════════════════════════════════════
    //  LEAD AUTO-ASSIGNMENT
    // ═══════════════════════════════════════════════════════

    /**
     * Auto-assign a lead based on active assignment rules.
     * Priority: rule priority desc → round-robin within matching rule.
     */
    public function autoAssignLead(Lead $lead): ?int
    {
        $rules = LeadAssignmentRule::where('account_id', $lead->account_id)
            ->where('is_active', true)
            ->orderByDesc('priority')->get();

        foreach ($rules as $rule) {
            if (!$this->leadMatchesRule($lead, $rule)) continue;

            $assigneeId = $this->pickAssignee($rule);
            if ($assigneeId) {
                $lead->update(['assigned_to' => $assigneeId]);
                return $assigneeId;
            }
        }

        return null;
    }

    private function leadMatchesRule(Lead $lead, LeadAssignmentRule $rule): bool
    {
        $conditions = $rule->conditions ?? [];
        if (empty($conditions)) return true;

        foreach ($conditions as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $value = $condition['value'];
            $leadValue = $lead->{$field} ?? null;

            $matches = match ($operator) {
                'equals' => $leadValue == $value,
                'contains' => str_contains(strtolower((string) $leadValue), strtolower($value)),
                'starts_with' => str_starts_with(strtolower((string) $leadValue), strtolower($value)),
                'greater_than' => (float) $leadValue > (float) $value,
                'less_than' => (float) $leadValue < (float) $value,
                'in' => in_array($leadValue, (array) $value),
                default => true,
            };

            if (!$matches) return false;
        }

        return true;
    }

    private function pickAssignee(LeadAssignmentRule $rule): ?int
    {
        $assignees = $rule->assignees ?? [];
        if (empty($assignees)) return null;

        if ($rule->assignment_type === 'round_robin') {
            $idx = ($rule->last_assigned_index + 1) % count($assignees);
            $rule->update(['last_assigned_index' => $idx]);
            return $assignees[$idx];
        }

        if ($rule->assignment_type === 'load_balance') {
            // Pick assignee with fewest open leads this week
            return collect($assignees)->sortBy(function ($userId) use ($rule) {
                return Lead::where('account_id', $rule->account_id)
                    ->where('assigned_to', $userId)
                    ->where('status', Lead::STATUS_NEW)
                    ->count();
            })->first();
        }

        if ($rule->assignment_type === 'score_based') {
            // Highest score leads → best performing rep (most wins last 30 days)
            return collect($assignees)->sortByDesc(function ($userId) use ($rule) {
                return Deal::where('account_id', $rule->account_id)
                    ->where('assigned_to', $userId)
                    ->where('status', Deal::STATUS_WON)
                    ->where('updated_at', '>=', now()->subDays(30))->count();
            })->first();
        }

        return $assignees[0] ?? null;
    }

    // ═══════════════════════════════════════════════════════
    //  REP KPI TRACKING
    // ═══════════════════════════════════════════════════════

    /**
     * Get sales rep KPI dashboard for current period.
     */
    public function getRepKPIs(int $accountId, int $userId, string $period = 'month'): array
    {
        $now = now();
        $start = match ($period) {
            'week' => $now->copy()->startOfWeek(),
            'quarter' => $now->copy()->startOfQuarter(),
            default => $now->copy()->startOfMonth(),
        };

        $target = SalesTarget::where('account_id', $accountId)
            ->where('user_id', $userId)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->where('period_type', 'month')
            ->first();

        $wonRevenue = Deal::where('account_id', $accountId)->where('assigned_to', $userId)
            ->where('status', Deal::STATUS_WON)->where('updated_at', '>=', $start)->sum('value');
        $wonDeals = Deal::where('account_id', $accountId)->where('assigned_to', $userId)
            ->where('status', Deal::STATUS_WON)->where('updated_at', '>=', $start)->count();
        $newLeads = Lead::where('account_id', $accountId)->where('assigned_to', $userId)
            ->where('created_at', '>=', $start)->count();
        $activities = Activity::where('account_id', $accountId)->where('user_id', $userId)
            ->where('date', '>=', $start)->count();

        $attainment = $target?->revenue_target > 0
            ? round(($wonRevenue / $target->revenue_target) * 100, 1)
            : null;

        return [
            'period' => $period,
            'revenue' => ['actual' => (float) $wonRevenue, 'target' => (float) ($target?->revenue_target ?? 0), 'attainment' => $attainment],
            'deals_won' => ['actual' => $wonDeals, 'target' => $target?->deals_target ?? 0],
            'new_leads' => ['actual' => $newLeads, 'target' => $target?->leads_target ?? 0],
            'activities' => ['actual' => $activities, 'target' => $target?->activities_target ?? 0],
            'open_deals' => Deal::where('account_id', $accountId)->where('assigned_to', $userId)->where('status', Deal::STATUS_OPEN)->count(),
            'pipeline_value' => (float) Deal::where('account_id', $accountId)->where('assigned_to', $userId)->where('status', Deal::STATUS_OPEN)->sum('value'),
            'velocity' => $this->calculateSalesVelocity($accountId, $userId),
        ];
    }

    // ═══════════════════════════════════════════════════════
    //  BATCH UPDATE DEAL METRICS
    // ═══════════════════════════════════════════════════════

    /**
     * Recalculate and persist health + probability for all open deals.
     * Called by artisan schedule.
     */
    public function refreshAllDeals(int $accountId): int
    {
        $deals = Deal::where('account_id', $accountId)->where('status', Deal::STATUS_OPEN)->get();
        $updated = 0;

        foreach ($deals as $deal) {
            $prob = $this->calculateWinProbability($deal);
            $health = $this->calculateDealHealth($deal);

            $deal->update([
                'win_probability' => $prob,
                'weighted_value' => round(($deal->value ?? 0) * $prob / 100),
                'health_score' => $health['score'],
                'at_risk' => $health['at_risk'],
            ]);
            $updated++;
        }

        return $updated;
    }

    /**
     * Update stage history when deal moves to a new stage.
     */
    public function recordStageChange(Deal $deal, string $newStage): void
    {
        $history = $deal->stage_history ?? [];
        $now = now()->toISOString();

        // Close previous stage entry
        if (!empty($history)) {
            $last = &$history[count($history) - 1];
            if (!isset($last['exited_at'])) {
                $last['exited_at'] = $now;
            }
        }

        // Open new entry
        $history[] = ['stage' => $newStage, 'entered_at' => $now, 'exited_at' => null];

        $deal->update([
            'stage_history' => $history,
            'stage_changed_at' => now(),
            'days_in_stage' => 0,
        ]);
    }
}
