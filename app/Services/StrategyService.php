<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\FinancialTransaction;
use App\Models\Initiative;
use App\Models\KeyResult;
use App\Models\KpiValue;
use App\Models\Lead;
use App\Models\Objective;
use App\Models\Project;
use App\Models\StrategicPlan;
use App\Models\StrategicTheme;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StrategyService
{
    // ════════════════════════════════════════════
    //  OKR CASCADE TREE
    // ════════════════════════════════════════════

    /**
     * Get full OKR cascade tree for an account.
     */
    public function getOKRTree(int $accountId, ?string $period = null, ?User $scopedUser = null): array
    {
        $query = Objective::where('account_id', $accountId)
            ->roots()
            ->with(['keyResults', 'owner', 'children.keyResults', 'children.owner', 'children.children.keyResults', 'children.children.owner']);

        if ($period) {
            $query->forPeriod($period);
        }

        if ($scopedUser) {
            $query->forUser($scopedUser);
        }

        return $query->orderBy('sort_order')->get()->map(fn ($o) => $o->getTree())->toArray();
    }

    /**
     * Get available periods from existing objectives.
     */
    public function getAvailablePeriods(int $accountId): array
    {
        return Objective::where('account_id', $accountId)
            ->distinct()
            ->whereNotNull('period')
            ->pluck('period')
            ->sort()
            ->values()
            ->toArray();
    }

    // ════════════════════════════════════════════
    //  AUTO-TRACKING: CRM → Key Results
    // ════════════════════════════════════════════

    /**
     * Refresh all auto-tracked key results for an account.
     */
    public function refreshAutoTrackedKRs(int $accountId): int
    {
        $keyResults = KeyResult::where('account_id', $accountId)
            ->where('data_source', '!=', KeyResult::SOURCE_MANUAL)
            ->get();

        $updated = 0;
        foreach ($keyResults as $kr) {
            $newValue = $this->fetchDataSourceValue($kr);
            if ($newValue !== null && $newValue != (float) $kr->current_value) {
                $kr->checkIn($newValue);
                $updated++;
            }
        }

        Log::info("[Strategy] Refreshed {$updated} auto-tracked KRs for account #{$accountId}");
        return $updated;
    }

    /**
     * Fetch current value from a data source.
     */
    public function fetchDataSourceValue(KeyResult $kr): ?float
    {
        $objective = $kr->objective;
        $start = $objective->period_start ?? now()->startOfQuarter();
        $end = $objective->period_end ?? now()->endOfQuarter();
        $accountId = $kr->account_id;
        $config = $kr->data_source_config ?? [];

        return match ($kr->data_source) {
            KeyResult::SOURCE_DEALS_COUNT => (float) Deal::where('account_id', $accountId)
                ->where('status', 'won')
                ->whereBetween('updated_at', [$start, $end])
                ->when($config['assigned_to'] ?? null, fn ($q, $u) => $q->where('assigned_to', $u))
                ->count(),

            KeyResult::SOURCE_DEALS_VALUE => (float) Deal::where('account_id', $accountId)
                ->where('status', 'won')
                ->whereBetween('updated_at', [$start, $end])
                ->when($config['assigned_to'] ?? null, fn ($q, $u) => $q->where('assigned_to', $u))
                ->sum('value'),

            KeyResult::SOURCE_LEADS_COUNT => (float) Lead::where('account_id', $accountId)
                ->whereBetween('created_at', [$start, $end])
                ->when($config['source'] ?? null, fn ($q, $s) => $q->where('source', $s))
                ->count(),

            KeyResult::SOURCE_LEADS_QUALIFIED => (float) Lead::where('account_id', $accountId)
                ->where('status', 'qualified')
                ->whereBetween('updated_at', [$start, $end])
                ->count(),

            KeyResult::SOURCE_REVENUE => (float) FinancialTransaction::where('account_id', $accountId)
                ->where('type', FinancialTransaction::TYPE_INCOME)
                ->whereBetween('transaction_date', [$start, $end])
                ->sum('amount'),

            KeyResult::SOURCE_NEW_CUSTOMERS => (float) Customer::where('account_id', $accountId)
                ->whereBetween('created_at', [$start, $end])
                ->count(),

            KeyResult::SOURCE_PROJECT_COMPLETION => (float) Project::where('account_id', $accountId)
                ->where('status', 'completed')
                ->whereBetween('updated_at', [$start, $end])
                ->count(),

            KeyResult::SOURCE_CUSTOM_KPI => $this->fetchCustomKpiValue($kr),

            default => null,
        };
    }

    private function fetchCustomKpiValue(KeyResult $kr): ?float
    {
        if (!$kr->kpi_definition_id) return null;

        $latest = KpiValue::where('kpi_definition_id', $kr->kpi_definition_id)
            ->latest('period_end')
            ->first();

        return $latest ? (float) $latest->value : null;
    }

    // ════════════════════════════════════════════
    //  STRATEGY HEALTH & INSIGHTS
    // ════════════════════════════════════════════

    /**
     * Compute strategy health dashboard data.
     */
    public function getStrategyHealth(int $accountId): array
    {
        $plan = StrategicPlan::getActivePlan($accountId);
        if (!$plan) {
            return ['plan' => null, 'themes' => [], 'overall_health' => 0, 'risk_alerts' => []];
        }

        $themes = $plan->themes()->with([
            'objectives' => fn ($q) => $q->where('status', '!=', 'cancelled'),
            'objectives.keyResults',
            'strategicKpis.kpiDefinition',
        ])->get();

        $themeData = [];
        $riskAlerts = [];

        foreach ($themes as $theme) {
            $health = $theme->getHealthScore();
            $objectives = $theme->objectives;

            // Detect risks
            foreach ($objectives as $obj) {
                if ($obj->status === Objective::STATUS_AT_RISK) {
                    $riskAlerts[] = [
                        'type' => 'objective_at_risk',
                        'severity' => 'warning',
                        'message_vi' => "Mục tiêu \"{$obj->title}\" đang có rủi ro (tiến độ: {$obj->progress}%)",
                        'message_en' => "Objective \"{$obj->title}\" is at risk (progress: {$obj->progress}%)",
                        'objective_id' => $obj->id,
                    ];
                }

                // Stale KR (no update in 14 days)
                foreach ($obj->keyResults as $kr) {
                    if ($kr->data_source === KeyResult::SOURCE_MANUAL && $kr->updated_at->diffInDays(now()) > 14) {
                        $riskAlerts[] = [
                            'type' => 'stale_kr',
                            'severity' => 'info',
                            'message_vi' => "KR \"{$kr->title}\" chưa cập nhật {$kr->updated_at->diffInDays(now())} ngày",
                            'message_en' => "KR \"{$kr->title}\" has not been updated in {$kr->updated_at->diffInDays(now())} days",
                            'key_result_id' => $kr->id,
                        ];
                    }
                }
            }

            // Overdue initiatives
            $overdueInitiatives = Initiative::where('account_id', $accountId)
                ->whereIn('objective_id', $objectives->pluck('id'))
                ->where('due_date', '<', now())
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();

            if ($overdueInitiatives > 0) {
                $riskAlerts[] = [
                    'type' => 'overdue_initiatives',
                    'severity' => 'warning',
                    'message_vi' => "{$overdueInitiatives} sáng kiến quá hạn trong \"{$theme->name}\"",
                    'message_en' => "{$overdueInitiatives} overdue initiatives in \"{$theme->name}\"",
                ];
            }

            $themeData[] = [
                'id' => $theme->id,
                'name' => $theme->name,
                'color' => $theme->color,
                'icon' => $theme->icon,
                'health' => $health,
                'weight' => $theme->weight,
                'objectives_count' => $objectives->count(),
                'completed_count' => $objectives->where('status', 'completed')->count(),
                'at_risk_count' => $objectives->where('status', 'at_risk')->count(),
            ];
        }

        // Calculate overall health
        $totalWeight = collect($themeData)->sum('weight');
        $overallHealth = $totalWeight > 0
            ? round(collect($themeData)->sum(fn ($t) => $t['health'] * $t['weight']) / $totalWeight, 1)
            : 0;

        return [
            'plan' => [
                'id' => $plan->id,
                'title' => $plan->title,
                'vision' => $plan->vision,
                'mission' => $plan->mission,
                'status' => $plan->status,
            ],
            'themes' => $themeData,
            'overall_health' => $overallHealth,
            'risk_alerts' => $riskAlerts,
        ];
    }

    /**
     * Get OKR summary stats for dashboard.
     */
    public function getOKRStats(int $accountId, ?string $period = null): array
    {
        $query = Objective::where('account_id', $accountId);
        if ($period) $query->forPeriod($period);

        $objectives = $query->get();

        return [
            'total_objectives' => $objectives->count(),
            'company_level' => $objectives->where('level', 'company')->count(),
            'team_level' => $objectives->where('level', 'team')->count(),
            'individual_level' => $objectives->where('level', 'individual')->count(),
            'avg_progress' => round($objectives->avg('progress') ?? 0, 1),
            'avg_confidence' => (int) round($objectives->avg('confidence') ?? 0),
            'completed' => $objectives->where('status', 'completed')->count(),
            'at_risk' => $objectives->where('status', 'at_risk')->count(),
            'active' => $objectives->where('status', 'active')->count(),
            'total_key_results' => KeyResult::where('account_id', $accountId)->count(),
            'total_initiatives' => Initiative::where('account_id', $accountId)
                ->where('status', '!=', 'cancelled')->count(),
        ];
    }

    /**
     * Get CRM-to-Goals alignment score.
     */
    public function getAlignmentScore(int $accountId): array
    {
        $autoTracked = KeyResult::where('account_id', $accountId)
            ->where('data_source', '!=', KeyResult::SOURCE_MANUAL)
            ->count();
        $totalKRs = KeyResult::where('account_id', $accountId)->count();

        $linkedInitiatives = Initiative::where('account_id', $accountId)
            ->whereNotNull('project_id')
            ->count();
        $totalInitiatives = Initiative::where('account_id', $accountId)->count();

        $trackingScore = $totalKRs > 0 ? round(($autoTracked / $totalKRs) * 100) : 0;
        $executionScore = $totalInitiatives > 0 ? round(($linkedInitiatives / $totalInitiatives) * 100) : 0;

        return [
            'tracking_alignment' => $trackingScore,
            'execution_alignment' => $executionScore,
            'overall_alignment' => round(($trackingScore + $executionScore) / 2),
            'auto_tracked_krs' => $autoTracked,
            'total_krs' => $totalKRs,
            'linked_initiatives' => $linkedInitiatives,
            'total_initiatives' => $totalInitiatives,
        ];
    }
}
