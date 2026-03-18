<?php

namespace App\Listeners;

use App\Events\DealWon;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class HandleDealWon
{
    public function handle(DealWon $event): void
    {
        $deal = $event->deal;

        // Update lead status to won
        if ($deal->lead_id) {
            $lead = Lead::find($deal->lead_id);
            if ($lead && $lead->status !== Lead::STATUS_WON) {
                $lead->update(['status' => Lead::STATUS_WON]);
            }
        }

        Log::info('Deal won', [
            'deal_id' => $deal->id,
            'deal_title' => $deal->title,
            'value' => $deal->value,
            'account_id' => $deal->account_id,
        ]);
    }
}
