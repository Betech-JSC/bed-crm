<?php

namespace App\Listeners\AI;

use App\Events\DealStageChanged;
use App\Services\AI\AiOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * AiDealInsightListener
 * ─────────────────────
 * Generates AI insights when a deal changes stage.
 * Provides next-action suggestions and risk assessment.
 */
class AiDealInsightListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 2;
    public int $timeout = 30;

    public function handle(DealStageChanged $event): void
    {
        try {
            $deal = $event->deal;

            $orchestrator = app(AiOrchestrator::class);
            $result = $orchestrator->suggest('deal', $deal->id, 'next_action');

            if ($result['success'] && $result['suggestion']) {
                $suggestion = $result['suggestion'];

                $deal->update([
                    'ai_summary' => is_array($suggestion) ? json_encode($suggestion) : $suggestion,
                    'next_steps' => $suggestion['actions'] ?? $deal->next_steps,
                ]);

                Log::info('AI Deal Insight: Generated for deal', [
                    'deal_id' => $deal->id,
                    'stage' => $deal->stage,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('AI Deal Insight failed', ['deal_id' => $event->deal->id, 'error' => $e->getMessage()]);
        }
    }
}
