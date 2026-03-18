<?php

namespace App\Listeners;

use App\Events\ActivityLogged;
use App\Models\Lead;
use App\Services\SLATrackingService;

class RecordLeadFirstResponse
{
    public function __construct(
        private SLATrackingService $slaService
    ) {}

    public function handle(ActivityLogged $event): void
    {
        $activity = $event->activity;

        if ($activity->subject_type === Lead::class) {
            $lead = Lead::find($activity->subject_id);
            if ($lead && !$lead->first_response_at) {
                $this->slaService->recordFirstResponse($lead);
            }
        }
    }
}
