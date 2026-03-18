<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Collection;

class ProjectProfitService
{
    /**
     * Calculate detailed profit breakdown for a project.
     */
    public function calculateProfit(Project $project): array
    {
        $project->load(['tasks', 'resources', 'expenses']);

        $laborCost = $project->resources->sum(fn ($r) => (float) $r->logged_hours * (float) $r->hourly_rate);
        $taskCost = $project->tasks->sum(fn ($t) => (float) $t->actual_hours * (float) $t->hourly_cost);
        $expensesCost = (float) $project->expenses->sum('amount');
        $totalCost = $laborCost + $taskCost + $expensesCost;

        $revenue = (float) $project->revenue;
        $profit = $revenue - $totalCost;
        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;
        $budget = (float) $project->budget;
        $budgetUsed = $budget > 0 ? round(($totalCost / $budget) * 100, 1) : 0;

        // Update project total_cost
        $project->update(['total_cost' => $totalCost]);

        return [
            'revenue' => $revenue,
            'total_cost' => $totalCost,
            'labor_cost' => $laborCost,
            'task_cost' => $taskCost,
            'expenses_cost' => $expensesCost,
            'profit' => $profit,
            'margin' => $margin,
            'budget' => $budget,
            'budget_used' => $budgetUsed,
            'is_profitable' => $profit > 0,
            'is_over_budget' => $totalCost > $budget && $budget > 0,
        ];
    }

    /**
     * Get portfolio analytics across all projects.
     */
    public function getPortfolioAnalytics(int $accountId): array
    {
        $projects = Project::where('account_id', $accountId)
            ->with(['tasks', 'resources', 'expenses'])
            ->get();

        $active = $projects->filter(fn ($p) => in_array($p->status, [Project::STATUS_IN_PROGRESS, Project::STATUS_PLANNING, Project::STATUS_ON_HOLD, Project::STATUS_DELAYED]));
        $completed = $projects->where('status', Project::STATUS_COMPLETED);
        $delayed = $projects->where('status', Project::STATUS_DELAYED);
        $overdue = $projects->filter(fn ($p) => $p->isOverdue());

        $totalRevenue = (float) $projects->sum('revenue');
        $totalCost = $projects->sum(fn ($p) => $p->calculateTotalCost());
        $totalProfit = $totalRevenue - $totalCost;
        $avgMargin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0;

        // Resource utilization
        $allResources = $projects->flatMap->resources;
        $totalAllocated = $allResources->sum('allocated_hours');
        $totalLogged = (float) $allResources->sum('logged_hours');
        $utilization = $totalAllocated > 0 ? round(($totalLogged / $totalAllocated) * 100, 1) : 0;

        // Status distribution
        $statusCounts = [];
        foreach (Project::getStatuses() as $key => $label) {
            $statusCounts[$key] = $projects->where('status', $key)->count();
        }

        return [
            'total_projects' => $projects->count(),
            'active_count' => $active->count(),
            'completed_count' => $completed->count(),
            'delayed_count' => $delayed->count() + $overdue->count(),
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'total_profit' => $totalProfit,
            'avg_margin' => $avgMargin,
            'utilization' => $utilization,
            'total_budget' => (float) $projects->sum('budget'),
            'status_counts' => $statusCounts,
            'avg_progress' => (int) round($active->avg('progress')),
        ];
    }

    /**
     * Get per-project profit ranking.
     */
    public function getProfitRanking(int $accountId, int $limit = 10): array
    {
        return Project::where('account_id', $accountId)
            ->with(['tasks', 'resources', 'expenses'])
            ->get()
            ->map(function ($p) {
                $cost = $p->calculateTotalCost();
                $profit = (float) $p->revenue - $cost;
                $margin = (float) $p->revenue > 0 ? round(($profit / (float) $p->revenue) * 100, 1) : 0;
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'status' => $p->status,
                    'revenue' => (float) $p->revenue,
                    'cost' => $cost,
                    'profit' => $profit,
                    'margin' => $margin,
                ];
            })
            ->sortByDesc('profit')
            ->take($limit)
            ->values()
            ->toArray();
    }
}
