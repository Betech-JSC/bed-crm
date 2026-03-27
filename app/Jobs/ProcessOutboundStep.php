<?php

namespace App\Jobs;

use App\Models\OutboundCampaign;
use App\Services\OutboundSalesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOutboundStep implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public OutboundCampaign $campaign,
    ) {}

    public function handle(OutboundSalesService $service): void
    {
        $service->executeStep($this->campaign->fresh());
    }

    public function failed(\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('ProcessOutboundStep job failed', [
            'campaign_id' => $this->campaign->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
