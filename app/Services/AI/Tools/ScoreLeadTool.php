<?php

namespace App\Services\AI\Tools;

use App\Contracts\AiToolInterface;
use App\Models\Lead;

class ScoreLeadTool implements AiToolInterface
{
    public function name(): string { return 'score_lead'; }

    public function description(): string
    {
        return 'Đánh giá chất lượng lead (lead scoring) từ 0-100. Sử dụng khi user muốn biết lead nào tiềm năng nhất.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'lead_id' => ['type' => 'integer', 'description' => 'ID của lead cần scoring'],
            ],
            'required' => ['lead_id'],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $lead = Lead::where('account_id', $context['account_id'])->find($params['lead_id']);

        if (!$lead) {
            return ['success' => false, 'message' => "Lead #{$params['lead_id']} không tồn tại.", 'data' => null];
        }

        // Calculate score based on data completeness + engagement
        $score = 0;
        $reasons = [];

        // Data completeness (max 30)
        if ($lead->email) { $score += 10; $reasons[] = 'Có email'; }
        if ($lead->phone) { $score += 10; $reasons[] = 'Có SĐT'; }
        if ($lead->company) { $score += 5; $reasons[] = 'Có công ty'; }
        if ($lead->industry) { $score += 5; $reasons[] = 'Có ngành nghề'; }

        // Source quality (max 20)
        $sourceScore = match ($lead->source) {
            'referral' => 20,
            'website' => 15,
            'email' => 12,
            'social' => 10,
            'phone' => 8,
            default => 5,
        };
        $score += $sourceScore;
        $reasons[] = "Nguồn: {$lead->source} (+{$sourceScore})";

        // Engagement (max 30)
        if ($lead->email_opens > 0) { $score += 10; $reasons[] = "Đã mở {$lead->email_opens} email"; }
        if ($lead->email_clicks > 0) { $score += 10; $reasons[] = "Đã click {$lead->email_clicks} link"; }
        if ($lead->website_visits > 0) { $score += 10; $reasons[] = "{$lead->website_visits} lượt visit website"; }

        // Status bonus (max 20)
        $statusScore = match ($lead->status) {
            'qualified' => 20,
            'contacted' => 10,
            'new' => 5,
            default => 0,
        };
        $score += $statusScore;

        $score = min($score, 100);

        // Update the lead score
        $lead->update(['score' => $score, 'last_scored_at' => now()]);

        return [
            'success' => true,
            'message' => "Lead \"{$lead->name}\" scored: {$score}/100",
            'data' => [
                'lead_id' => $lead->id,
                'name' => $lead->name,
                'score' => $score,
                'reasons' => $reasons,
                'priority' => $score >= 70 ? 'hot' : ($score >= 40 ? 'warm' : 'cold'),
            ],
        ];
    }

    public function requiresConfirmation(): bool { return false; }
}
