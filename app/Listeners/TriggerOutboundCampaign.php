<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Services\OutboundSalesService;
use Illuminate\Contracts\Queue\ShouldQueue;

class TriggerOutboundCampaign implements ShouldQueue
{
    public function __construct(
        private OutboundSalesService $outboundService,
    ) {}

    public function handle(LeadCreated $event): void
    {
        $lead = $event->lead;

        // Only trigger if lead has email
        if (!$lead->email) {
            return;
        }

        $this->outboundService->initiateCampaign($lead);
    }
}
