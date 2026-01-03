<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\SLASetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SLATrackingService
{
    /**
     * Start SLA tracking for a lead
     */
    public function startTracking(Lead $lead, ?SLASetting $slaSetting = null): void
    {
        // Get SLA setting (use provided, default, or create default)
        if (!$slaSetting) {
            $slaSetting = $lead->account->slaSettings()
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();

            // If no default exists, create one
            if (!$slaSetting) {
                $slaSetting = $this->createDefaultSLA($lead->account_id);
            }
        }

        // Only start tracking if lead is new and not already tracked
        if ($lead->status === Lead::STATUS_NEW && !$lead->sla_started_at) {
            $lead->update([
                'sla_setting_id' => $slaSetting->id,
                'sla_started_at' => now(),
                'sla_status' => 'pending',
            ]);
        }
    }

    /**
     * Check and update SLA status for a lead
     */
    public function checkSLA(Lead $lead): array
    {
        if (!$lead->sla_setting_id || !$lead->sla_started_at) {
            return ['status' => 'not_tracked', 'message' => 'SLA tracking not started'];
        }

        // If already responded, mark as resolved
        if ($lead->first_response_at) {
            if ($lead->sla_status !== 'resolved') {
                $lead->update([
                    'sla_status' => 'resolved',
                    'sla_resolved_at' => now(),
                ]);
            }
            return [
                'status' => 'resolved',
                'response_time' => $lead->response_time_minutes,
                'message' => 'SLA resolved - first response made',
            ];
        }

        $slaSetting = $lead->slaSetting;
        if (!$slaSetting) {
            return ['status' => 'error', 'message' => 'SLA setting not found'];
        }

        $elapsedMinutes = $this->calculateElapsedMinutes($lead->sla_started_at);
        $threshold = $slaSetting->first_response_threshold;
        $warningThreshold = $slaSetting->warning_threshold;

        $status = 'pending';
        $message = 'SLA pending';

        // Check if breached
        if ($elapsedMinutes >= $threshold) {
            $status = 'breached';
            $message = "SLA breached - {$elapsedMinutes} minutes elapsed (threshold: {$threshold} minutes)";
            
            // Send breach notification if not already sent
            if (!$lead->sla_breach_sent_at) {
                $this->sendBreachNotification($lead, $elapsedMinutes);
                $lead->update(['sla_breach_sent_at' => now()]);
            }
        }
        // Check if warning threshold reached
        elseif ($elapsedMinutes >= $warningThreshold) {
            $status = 'warning';
            $message = "SLA warning - {$elapsedMinutes} minutes elapsed (threshold: {$threshold} minutes)";
            
            // Send warning notification if not already sent
            if (!$lead->sla_warning_sent_at) {
                $this->sendWarningNotification($lead, $elapsedMinutes);
                $lead->update(['sla_warning_sent_at' => now()]);
            }
        }

        // Update lead SLA status
        if ($lead->sla_status !== $status) {
            $lead->update(['sla_status' => $status]);
        }

        return [
            'status' => $status,
            'elapsed_minutes' => $elapsedMinutes,
            'threshold' => $threshold,
            'warning_threshold' => $warningThreshold,
            'message' => $message,
        ];
    }

    /**
     * Record first response for a lead
     */
    public function recordFirstResponse(Lead $lead): void
    {
        if (!$lead->sla_started_at || $lead->first_response_at) {
            return; // Already responded or not tracking
        }

        $responseTimeMinutes = $this->calculateElapsedMinutes($lead->sla_started_at);
        $slaStatus = $responseTimeMinutes <= ($lead->slaSetting->first_response_threshold ?? 15)
            ? 'on_time'
            : 'breached';

        $lead->update([
            'first_response_at' => now(),
            'response_time_minutes' => $responseTimeMinutes,
            'sla_status' => $slaStatus,
            'sla_resolved_at' => now(),
        ]);
    }

    /**
     * Check all pending SLAs and send notifications
     */
    public function checkAllPendingSLAs(): array
    {
        $results = [
            'checked' => 0,
            'warnings' => 0,
            'breaches' => 0,
        ];

        // Get all leads with pending SLA
        $leads = Lead::whereNotNull('sla_setting_id')
            ->whereNotNull('sla_started_at')
            ->whereNull('first_response_at')
            ->whereIn('sla_status', ['pending', 'warning'])
            ->where('status', Lead::STATUS_NEW)
            ->with(['slaSetting', 'assignedUser', 'account'])
            ->get();

        foreach ($leads as $lead) {
            $results['checked']++;
            $checkResult = $this->checkSLA($lead);
            
            if ($checkResult['status'] === 'warning') {
                $results['warnings']++;
            } elseif ($checkResult['status'] === 'breached') {
                $results['breaches']++;
            }
        }

        return $results;
    }

    /**
     * Calculate elapsed minutes (considering business hours if configured)
     */
    private function calculateElapsedMinutes(Carbon $startTime): int
    {
        // For now, simple calculation - can be enhanced with business hours
        return now()->diffInMinutes($startTime);
    }

    /**
     * Send warning notification
     */
    private function sendWarningNotification(Lead $lead, int $elapsedMinutes): void
    {
        $slaSetting = $lead->slaSetting;
        $threshold = $slaSetting->first_response_threshold;
        $remainingMinutes = $threshold - $elapsedMinutes;

        $message = "⚠️ SLA Warning: Lead '{$lead->name}' is approaching SLA breach. "
            . "{$remainingMinutes} minutes remaining before breach (threshold: {$threshold} minutes).";

        $this->sendNotification($lead, $message, 'warning');
    }

    /**
     * Send breach notification
     */
    private function sendBreachNotification(Lead $lead, int $elapsedMinutes): void
    {
        $slaSetting = $lead->slaSetting;
        $threshold = $slaSetting->first_response_threshold;

        $message = "🚨 SLA BREACHED: Lead '{$lead->name}' has exceeded response time threshold. "
            . "Elapsed: {$elapsedMinutes} minutes (threshold: {$threshold} minutes). "
            . "Immediate action required!";

        $this->sendNotification($lead, $message, 'breach');
    }

    /**
     * Send notification to relevant users
     */
    private function sendNotification(Lead $lead, string $message, string $type): void
    {
        $slaSetting = $lead->slaSetting;
        $recipients = [];

        // Add assigned user
        if ($slaSetting->notify_assigned_user && $lead->assigned_to) {
            $recipients[] = $lead->assignedUser;
        }

        // Add managers
        if ($slaSetting->notify_managers) {
            $managers = $lead->account->users()
                ->whereIn('role', [User::ROLE_ADMIN])
                ->get();
            $recipients = array_merge($recipients, $managers->all());
        }

        // Add additional users
        if ($slaSetting->notify_user_ids) {
            $additionalUsers = User::whereIn('id', $slaSetting->notify_user_ids)->get();
            $recipients = array_merge($recipients, $additionalUsers->all());
        }

        // Remove duplicates
        $recipients = collect($recipients)->unique('id');

        // For now, log the notification (can be enhanced with email/real-time notifications)
        foreach ($recipients as $user) {
            Log::info("SLA Notification [{$type}]", [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'lead_id' => $lead->id,
                'lead_name' => $lead->name,
                'message' => $message,
            ]);

            // TODO: Send email notification
            // TODO: Send real-time notification (websocket/pusher)
            // TODO: Create notification record in database
        }
    }

    /**
     * Create default SLA setting for an account
     */
    private function createDefaultSLA(int $accountId): SLASetting
    {
        return SLASetting::create([
            'account_id' => $accountId,
            'name' => 'Default Response SLA',
            'description' => 'Standard 15-minute response time SLA',
            'first_response_threshold' => 15,
            'warning_threshold' => 10,
            'notify_assigned_user' => true,
            'notify_managers' => true,
            'is_active' => true,
            'is_default' => true,
        ]);
    }
}

