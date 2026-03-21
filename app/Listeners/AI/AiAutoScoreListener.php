<?php

namespace App\Listeners\AI;

use App\Events\LeadCreated;
use App\Services\AI\AiOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * AiAutoScoreListener
 * ───────────────────
 * Automatically scores leads when created.
 * Runs as a queued job to not block the request.
 */
class AiAutoScoreListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 2;
    public int $timeout = 30;

    public function handle(LeadCreated $event): void
    {
        try {
            $lead = $event->lead;

            // Skip if already scored
            if ($lead->score > 0 && $lead->last_scored_at) return;

            $orchestrator = app(AiOrchestrator::class);
            $result = $orchestrator->suggest('lead', $lead->id, 'score');

            if ($result['success'] && isset($result['suggestion']['score'])) {
                $lead->update([
                    'score' => $result['suggestion']['score'],
                    'scoring_details' => json_encode($result['suggestion']),
                    'last_scored_at' => now(),
                ]);

                Log::info('AI Auto-Score: Lead scored', [
                    'lead_id' => $lead->id,
                    'score' => $result['suggestion']['score'],
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('AI Auto-Score failed', ['lead_id' => $event->lead->id, 'error' => $e->getMessage()]);
        }
    }
}
