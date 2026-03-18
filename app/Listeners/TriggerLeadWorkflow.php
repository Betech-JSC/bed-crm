<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Models\Workflow;
use App\Services\WorkflowService;
use Illuminate\Contracts\Queue\ShouldQueue;

class TriggerLeadWorkflow implements ShouldQueue
{
    public function __construct(
        private WorkflowService $workflowService
    ) {}

    public function handle(LeadCreated $event): void
    {
        $this->workflowService->trigger(Workflow::TRIGGER_LEAD_CREATED, $event->lead);
    }
}
