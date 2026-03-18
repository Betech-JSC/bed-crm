<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\Lead;
use App\Services\ExecutiveDashboardService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private ExecutiveDashboardService $dashboardService
    ) {}

    public function index(): Response
    {
        $account = Auth::user()->account;
        $accountId = $account->id;

        // ── Executive metrics (Revenue, Pipeline, Finance, Strategy, HR, Ops) ──
        $metrics = $this->dashboardService->getMetrics($accountId);

        // ── CRM Operational Data ──
        $totalLeads = $account->leads()->count();
        $openDeals = $account->deals()->where('status', Deal::STATUS_OPEN)->count();
        $wonDeals = $account->deals()->where('status', Deal::STATUS_WON)->count();
        $totalPipelineValue = (float) ($account->deals()
            ->where('status', Deal::STATUS_OPEN)
            ->whereNotNull('value')
            ->sum('value') ?? 0);

        $tasksOverdue = $account->activities()
            ->whereIn('type', [Activity::TYPE_MEETING, Activity::TYPE_CALL])
            ->whereDate('date', '<', now()->toDateString())
            ->count();

        // Lead Quality Metrics
        $highQualityLeads = $account->leads()->where('score', '>=', 80)->count();
        $mediumQualityLeads = $account->leads()->whereBetween('score', [60, 79])->count();
        $lowQualityLeads = $account->leads()->whereBetween('score', [40, 59])->count();
        $averageLeadScore = $account->leads()->whereNotNull('score')->avg('score');
        $icpMatchedLeads = $account->leads()->whereNotNull('icp_id')->count();

        // Chart Data: Leads by Status (Last 30 days)
        $leadsByStatus = $account->leads()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Chart Data: Deals by Stage
        $dealsByStage = $account->deals()
            ->where('status', Deal::STATUS_OPEN)
            ->selectRaw('stage, COUNT(*) as count, SUM(value) as total_value')
            ->groupBy('stage')
            ->get()
            ->map(fn ($deal) => [
                'stage' => $deal->stage,
                'count' => $deal->count,
                'value' => (float) ($deal->total_value ?? 0),
            ])
            ->toArray();

        // Chart Data: Leads Created Over Time (Last 30 days)
        $leadsOverTime = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = $account->leads()->whereDate('created_at', $date)->count();
            $leadsOverTime[] = [
                'date' => now()->subDays($i)->format('M d'),
                'count' => $count,
            ];
        }

        // Recent Leads
        $recentLeads = $account->leads()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'company' => $lead->company,
                'status' => $lead->status,
            ]);

        return Inertia::render('Dashboard/Index', [
            'metrics' => $metrics,
            'crmStats' => [
                'totalLeads' => $totalLeads,
                'openDeals' => $openDeals,
                'wonDeals' => $wonDeals,
                'totalPipelineValue' => $totalPipelineValue,
                'tasksOverdue' => $tasksOverdue,
                'highQualityLeads' => $highQualityLeads,
                'mediumQualityLeads' => $mediumQualityLeads,
                'lowQualityLeads' => $lowQualityLeads,
                'averageLeadScore' => $averageLeadScore ? round($averageLeadScore, 1) : 0,
                'icpMatchRate' => $totalLeads > 0 ? round(($icpMatchedLeads / $totalLeads) * 100, 1) : 0,
            ],
            'chartData' => [
                'leadsByStatus' => $leadsByStatus,
                'dealsByStage' => $dealsByStage,
                'leadsOverTime' => $leadsOverTime,
            ],
            'recentLeads' => $recentLeads,
            'statuses' => Lead::getStatuses(),
        ]);
    }

    /**
     * API endpoint for real-time refresh.
     */
    public function refresh(): \Illuminate\Http\JsonResponse
    {
        $accountId = Auth::user()->account_id;

        $this->dashboardService->invalidateCache($accountId);
        $metrics = $this->dashboardService->getMetrics($accountId);

        return response()->json($metrics);
    }
}
