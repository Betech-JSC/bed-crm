<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerHealthLog;
use App\Models\SupportTicket;
use Illuminate\Support\Carbon;

class CustomerHealthService
{
    /**
     * Calculate and update health score for a customer.
     * Score: 0-100 based on weighted factors.
     */
    public function calculateHealthScore(Customer $customer, string $trigger = 'manual'): int
    {
        $factors = [
            'engagement' => $this->calculateEngagement($customer),
            'support' => $this->calculateSupportScore($customer),
            'payment' => $this->calculatePaymentScore($customer),
            'renewal' => $this->calculateRenewalScore($customer),
            'relationship' => $this->calculateRelationshipScore($customer),
        ];

        // Weighted average
        $weights = [
            'engagement' => 0.25,
            'support' => 0.20,
            'payment' => 0.20,
            'renewal' => 0.20,
            'relationship' => 0.15,
        ];

        $score = 0;
        foreach ($factors as $key => $value) {
            $score += $value * ($weights[$key] ?? 0);
        }

        $finalScore = (int) round(min(100, max(0, $score)));

        // Update customer
        $customer->update([
            'health_score' => $finalScore,
            'health_factors' => $factors,
            'health_calculated_at' => now(),
        ]);

        // Log history
        CustomerHealthLog::create([
            'customer_id' => $customer->id,
            'score' => $finalScore,
            'factors' => $factors,
            'trigger' => $trigger,
        ]);

        // Auto-update lifecycle status based on health
        $this->updateLifecycleFromHealth($customer, $finalScore);

        return $finalScore;
    }

    /**
     * Detect customers at risk of churning.
     */
    public function detectChurnRisk(int $accountId): array
    {
        $atRiskCustomers = [];

        $customers = Customer::where('account_id', $accountId)
            ->whereIn('lifecycle_status', [Customer::STATUS_ACTIVE, Customer::STATUS_ONBOARDING])
            ->get();

        foreach ($customers as $customer) {
            $riskFactors = [];
            $riskLevel = 'low';

            // Health score < 40
            if ($customer->health_score < 40) {
                $riskFactors[] = 'Low health score: ' . $customer->health_score;
                $riskLevel = 'high';
            } elseif ($customer->health_score < 60) {
                $riskFactors[] = 'Medium health score: ' . $customer->health_score;
                $riskLevel = max($riskLevel, 'medium');
            }

            // Contract ending within 30 days and not renewed
            if ($customer->contract_end && $customer->contract_end->diffInDays(now()) <= 30
                && $customer->renewal_status !== Customer::RENEWAL_RENEWED) {
                $riskFactors[] = 'Contract ending soon: ' . $customer->contract_end->format('Y-m-d');
                $riskLevel = 'high';
            }

            // Many open support tickets
            $openTickets = $customer->tickets()->open()->count();
            if ($openTickets >= 3) {
                $riskFactors[] = "Many open tickets: {$openTickets}";
                $riskLevel = $riskLevel === 'low' ? 'medium' : 'high';
            }

            // Health score declining trend
            $recentLogs = $customer->healthLogs()
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->pluck('score')
                ->toArray();

            if (count($recentLogs) >= 2 && $recentLogs[0] < $recentLogs[count($recentLogs) - 1] - 15) {
                $riskFactors[] = 'Health score declining';
                $riskLevel = $riskLevel === 'low' ? 'medium' : $riskLevel;
            }

            if (!empty($riskFactors)) {
                $atRiskCustomers[] = [
                    'customer' => $customer,
                    'risk_level' => $riskLevel,
                    'risk_factors' => $riskFactors,
                ];
            }
        }

        // Sort by risk level
        usort($atRiskCustomers, function ($a, $b) {
            $order = ['high' => 0, 'medium' => 1, 'low' => 2];
            return ($order[$a['risk_level']] ?? 3) <=> ($order[$b['risk_level']] ?? 3);
        });

        return $atRiskCustomers;
    }

    /**
     * Get lifecycle analytics for an account.
     */
    public function getLifecycleAnalytics(int $accountId): array
    {
        $customers = Customer::where('account_id', $accountId);

        $statusCounts = [];
        foreach (Customer::getLifecycleStatuses() as $key => $label) {
            $statusCounts[$key] = (clone $customers)->where('lifecycle_status', $key)->count();
        }

        $totalMrr = (clone $customers)->active()->sum('mrr');
        $totalArr = (clone $customers)->active()->sum('arr');
        $avgHealth = (float) (clone $customers)->active()->avg('health_score');
        $renewingSoon = (clone $customers)->renewingSoon(30)->count();
        $churnedLast30 = (clone $customers)->churned()
            ->where('updated_at', '>=', now()->subDays(30))->count();

        $activeCount = $statusCounts[Customer::STATUS_ACTIVE] ?? 0;
        $totalCount = array_sum($statusCounts);
        $churnRate = $totalCount > 0 ? round(($statusCounts[Customer::STATUS_CHURNED] ?? 0) / $totalCount * 100, 1) : 0;

        return [
            'total_customers' => $totalCount,
            'status_counts' => $statusCounts,
            'total_mrr' => $totalMrr,
            'total_arr' => $totalArr,
            'avg_health_score' => round($avgHealth),
            'renewing_soon' => $renewingSoon,
            'churned_last_30' => $churnedLast30,
            'churn_rate' => $churnRate,
            'net_retention_rate' => $activeCount > 0 ? round(($activeCount / max(1, $totalCount - ($statusCounts[Customer::STATUS_ONBOARDING] ?? 0))) * 100, 1) : 0,
        ];
    }

    // ===== Score Calculation Helpers =====

    private function calculateEngagement(Customer $customer): int
    {
        $score = 50; // Base

        // Tenure bonus
        if ($customer->start_date) {
            $months = $customer->start_date->diffInMonths(now());
            $score += min(20, $months * 2);
        }

        // Has activities recently (via deal or organization)
        if ($customer->deal_id) {
            $recentActivities = $customer->deal->activities()
                ->where('date', '>=', now()->subDays(30))
                ->count();
            $score += min(30, $recentActivities * 10);
        }

        return min(100, $score);
    }

    private function calculateSupportScore(Customer $customer): int
    {
        $score = 100; // Start perfect

        $openTickets = $customer->tickets()->open()->count();
        $urgentTickets = $customer->tickets()->open()->where('priority', SupportTicket::PRIORITY_URGENT)->count();
        $totalTickets = $customer->tickets()->count();

        // Deductions
        $score -= $openTickets * 10; // -10 per open ticket
        $score -= $urgentTickets * 15; // -15 extra per urgent
        if ($totalTickets > 10) $score -= 10; // High ticket volume

        // Resolved tickets ratio bonus
        $resolvedCount = $customer->tickets()->resolved()->count();
        if ($totalTickets > 0) {
            $resolutionRate = $resolvedCount / $totalTickets;
            $score += (int) ($resolutionRate * 20);
        }

        return max(0, min(100, $score));
    }

    private function calculatePaymentScore(Customer $customer): int
    {
        $score = 70;
        if ($customer->mrr > 0) $score += 15;
        if ($customer->arr > 0) $score += 15;
        return min(100, $score);
    }

    private function calculateRenewalScore(Customer $customer): int
    {
        if (!$customer->contract_end) return 60;

        $daysToRenewal = now()->diffInDays($customer->contract_end, false);

        if ($customer->renewal_status === Customer::RENEWAL_RENEWED) return 100;
        if ($customer->renewal_status === Customer::RENEWAL_LOST) return 0;
        if ($daysToRenewal < 0) return 20; // Past due
        if ($daysToRenewal <= 15) return 40;
        if ($daysToRenewal <= 30) return 60;
        if ($daysToRenewal <= 60) return 75;
        return 90;
    }

    private function calculateRelationshipScore(Customer $customer): int
    {
        $score = 50;
        if ($customer->assigned_to) $score += 20;
        if ($customer->organization_id) $score += 15;
        if ($customer->contact_id) $score += 15;
        return min(100, $score);
    }

    private function updateLifecycleFromHealth(Customer $customer, int $score): void
    {
        if ($customer->lifecycle_status === Customer::STATUS_CHURNED) return;

        if ($score < 30 && $customer->lifecycle_status !== Customer::STATUS_AT_RISK) {
            $customer->update(['lifecycle_status' => Customer::STATUS_AT_RISK]);
        } elseif ($score >= 60 && $customer->lifecycle_status === Customer::STATUS_AT_RISK) {
            $customer->update(['lifecycle_status' => Customer::STATUS_ACTIVE]);
        }
    }
}
