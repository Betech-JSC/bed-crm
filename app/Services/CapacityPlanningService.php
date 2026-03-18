<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\EmployeeProfile;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * CapacityPlanningService
 * ───────────────────────
 * Analysis of workload vs workforce capacity.
 * Links Sales Pipeline (Pre-sales) to Service Delivery (Post-sales).
 */
class CapacityPlanningService
{
    /**
     * Calculate current capacity vs workload.
     * Efficiency factor = active projects total estimated hours / (total employees * avg hours per month)
     */
    public function getCapacityMetrics(int $accountId): array
    {
        // 1. Workforce Capacity
        $activeEmployees = EmployeeProfile::where('account_id', $accountId)->where('status', 'active')->count();
        $totalManHoursPerMonth = $activeEmployees * 160; // Standard 160h/month

        // 2. Current Workload (Projects) - Assuming a Project model exists with estimated_hours
        $currentWorkload = 0;
        if (class_exists(\App\Models\Project::class)) {
            $currentWorkload = Project::where('account_id', $accountId)
                ->where('status', 'in_progress')
                ->sum('remaining_hours') ?: 0;
        }

        // 3. Pipeline Workload (Weighted)
        $pipelineDeals = Deal::where('account_id', $accountId)
            ->where('status', Deal::STATUS_OPEN)
            ->where('win_probability', '>=', 50)
            ->get();

        // Assumption: avg deal requires 80 man-hours
        $pipelineWorkload = $pipelineDeals->sum(fn($d) => 80 * ($d->win_probability / 100));

        $totalProjectedWorkload = $currentWorkload + $pipelineWorkload;
        $utilizationRate = $totalManHoursPerMonth > 0 ? round(($totalProjectedWorkload / $totalManHoursPerMonth) * 100, 1) : 0;

        return [
            'total_capacity_hours' => $totalManHoursPerMonth,
            'current_workload_hours' => round($currentWorkload),
            'pipeline_workload_hours' => round($pipelineWorkload),
            'utilization_rate' => $utilizationRate,
            'status' => $utilizationRate > 100 ? 'overloaded' : ($utilizationRate > 75 ? 'busy' : 'under_utilized'),
            'hiring_recommendation' => $utilizationRate > 90 ? ceil(($totalProjectedWorkload - $totalManHoursPerMonth) / 160) : 0
        ];
    }

    /**
     * Predictive hiring alert for the AI Advisor.
     */
    public function getHiringInsights(int $accountId): ?array
    {
        $metrics = $this->getCapacityMetrics($accountId);
        if ($metrics['utilization_rate'] > 85) {
            return [
                'type' => 'capacity_warning',
                'severity' => $metrics['utilization_rate'] > 110 ? 'high' : 'medium',
                'v_vi' => "Dự báo quá tải vận hành: Tỷ lệ sử dụng nhân sự đạt {$metrics['utilization_rate']}% trong 30-60 ngày tới.",
                'v_en' => "Capacity alert: Staff utilization projected at {$metrics['utilization_rate']}% in next 30-60 days.",
                'action_vi' => $metrics['hiring_recommendation'] > 0 
                    ? "Cân nhắc tuyển thêm {$metrics['hiring_recommendation']} nhân sự để đáp ứng Pipeline."
                    : "Tối ưu hoá nguồn lực hiện tại hoặc giảm tốc độ Sales.",
                'action_en' => $metrics['hiring_recommendation'] > 0
                    ? "Consider hiring {$metrics['hiring_recommendation']} new staff member(s) to meet pipeline demand."
                    : "Optimize current resources or slow down sales velocity.",
            ];
        }
        return null;
    }
}
