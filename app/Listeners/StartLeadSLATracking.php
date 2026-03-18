<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Services\SLATrackingService;
use Illuminate\Contracts\Queue\ShouldQueue;

class StartLeadSLATracking implements ShouldQueue
{
    public function __construct(
        private SLATrackingService $slaService
    ) {}

    public function handle(LeadCreated $event): void
    {
        $this->slaService->startTracking($event->lead);
    }
}
