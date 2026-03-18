<?php

namespace App\Listeners;

use App\Events\DealLost;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class HandleDealLost
{
    public function handle(DealLost $event): void
    {
        $deal = $event->deal;

        // Update lead status to lost
        if ($deal->lead_id) {
            $lead = Lead::find($deal->lead_id);
            if ($lead && $lead->status !== Lead::STATUS_LOST) {
                $lead->update(['status' => Lead::STATUS_LOST]);
            }
        }

        Log::info('Deal lost', [
            'deal_id' => $deal->id,
            'deal_title' => $deal->title,
            'reason' => $event->reason,
            'account_id' => $deal->account_id,
        ]);
    }
}
