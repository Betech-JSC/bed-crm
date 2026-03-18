<?php

namespace App\Observers;

use App\Models\Deal;
use App\Services\DataIntegrationService;

class DealObserver
{
    public function __construct(private DataIntegrationService $integration) {}

    /**
     * When a Deal is updated and status changes to 'won',
     * auto-create Customer and record revenue.
     */
    public function updated(Deal $deal): void
    {
        if ($deal->isDirty('status') && $deal->status === Deal::STATUS_WON) {
            $this->integration->onDealWon($deal);
        }
    }
}
