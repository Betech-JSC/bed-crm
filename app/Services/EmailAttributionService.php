<?php

namespace App\Services;

use App\Models\EmailContactBehavior;
use App\Models\EmailContactScore;
use App\Models\EmailRevenueAttribution;
use App\Models\EmailSend;
use App\Models\Deal;
use Illuminate\Support\Facades\DB;

class EmailAttributionService
{
    /**
     * Track a behavior event for a contact.
     */
    public function trackBehavior(
        int $accountId,
        string $contactType,
        int $contactId,
        string $email,
        string $eventType,
        ?string $eventSource = null,
        array $eventData = [],
        ?string $ip = null,
        ?string $userAgent = null
    ): EmailContactBehavior {
        $behavior = EmailContactBehavior::create([
            'account_id' => $accountId,
            'contact_type' => $contactType,
            'contact_id' => $contactId,
            'email' => $email,
            'event_type' => $eventType,
            'event_source' => $eventSource,
            'event_data' => $eventData,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_type' => $this->detectDevice($userAgent),
            'occurred_at' => now(),
        ]);

        // Update engagement score
        $this->updateEngagementScore($accountId, $contactType, $contactId, $email, $eventType);

        return $behavior;
    }

    /**
     * Update engagement score after a behavior event
     */
    public function updateEngagementScore(
        int $accountId,
        string $contactType,
        int $contactId,
        string $email,
        string $eventType
    ): void {
        $score = EmailContactScore::firstOrCreate(
            ['account_id' => $accountId, 'contact_type' => $contactType, 'contact_id' => $contactId],
            ['email' => $email, 'engagement_score' => 0]
        );

        // Increment counters based on event type
        match ($eventType) {
            'email_open' => $score->increment('emails_opened'),
            'email_click' => $score->increment('emails_clicked'),
            'page_view' => $score->increment('pages_viewed'),
            default => null,
        };

        if (in_array($eventType, ['email_open', 'email_click', 'page_view', 'form_submit'])) {
            $score->update(['last_engaged_at' => now()]);
        }

        $score->recalculate();
    }

    /**
     * Attribute revenue from a deal won event to email touchpoints.
     * Supports multiple attribution models.
     */
    public function attributeRevenue(
        int $accountId,
        string $contactType,
        int $contactId,
        Deal $deal,
        string $model = 'last_touch'
    ): void {
        // Find all email sends to this contact in the last 90 days
        $touchpoints = EmailSend::where('account_id', $accountId)
            ->where('contact_type', $contactType)
            ->where('contact_id', $contactId)
            ->where('status', 'delivered')
            ->where('sent_at', '>=', now()->subDays(90))
            ->orderBy('sent_at')
            ->get();

        if ($touchpoints->isEmpty()) return;

        $dealValue = $deal->value ?? 0;
        $count = $touchpoints->count();

        foreach ($touchpoints as $index => $send) {
            $weight = match ($model) {
                'first_touch' => $index === 0 ? 1.0 : 0.0,
                'last_touch' => $index === $count - 1 ? 1.0 : 0.0,
                'linear' => 1.0 / $count,
                'time_decay' => $this->timeDecayWeight($index, $count),
                default => $index === $count - 1 ? 1.0 : 0.0,
            };

            if ($weight <= 0) continue;

            EmailRevenueAttribution::create([
                'account_id' => $accountId,
                'email_campaign_id' => $send->sendable_type === 'campaign' ? $send->sendable_id : null,
                'email_automation_id' => $send->sendable_type === 'automation' ? $send->sendable_id : null,
                'email_send_id' => $send->id,
                'contact_type' => $contactType,
                'contact_id' => $contactId,
                'deal_type' => 'deal',
                'deal_id' => $deal->id,
                'deal_value' => $dealValue,
                'attribution_model' => $model,
                'attributed_value' => $dealValue * $weight,
                'attribution_weight' => $weight,
                'touchpoints_count' => $count,
                'days_to_conversion' => $send->sent_at->diffInDays($deal->closed_at ?? now()),
                'conversion_date' => $deal->closed_at ?? now(),
            ]);

            // Update campaign/automation revenue
            if ($send->sendable_type === 'campaign') {
                DB::table('email_campaigns')
                    ->where('id', $send->sendable_id)
                    ->increment('revenue_generated', $dealValue * $weight);
            }
        }
    }

    /**
     * Time decay weight calculation (more recent = more weight)
     */
    private function timeDecayWeight(int $index, int $total): float
    {
        if ($total <= 1) return 1.0;
        $halfLife = $total / 2;
        $rawWeight = pow(2, ($index - $total + 1) / $halfLife);
        $totalWeight = array_sum(array_map(fn ($i) => pow(2, ($i - $total + 1) / $halfLife), range(0, $total - 1)));
        return $rawWeight / $totalWeight;
    }

    private function detectDevice(?string $userAgent): ?string
    {
        if (!$userAgent) return null;
        $ua = strtolower($userAgent);
        if (str_contains($ua, 'mobile') || str_contains($ua, 'iphone') || str_contains($ua, 'android')) return 'mobile';
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) return 'tablet';
        return 'desktop';
    }
}
