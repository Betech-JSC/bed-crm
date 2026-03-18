<?php

namespace App\Listeners;

use App\Events\DealStageChanged;
use Illuminate\Support\Facades\Log;

class LogDealStageTransition
{
    public function handle(DealStageChanged $event): void
    {
        Log::info('Deal stage changed', [
            'deal_id' => $event->deal->id,
            'deal_title' => $event->deal->title,
            'old_stage' => $event->oldStage,
            'new_stage' => $event->newStage,
            'account_id' => $event->deal->account_id,
        ]);
    }
}
