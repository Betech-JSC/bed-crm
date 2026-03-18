<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\DataIntegrationService;

class ProjectObserver
{
    public function __construct(private DataIntegrationService $integration) {}

    /**
     * When a Project is updated and status changes to 'completed',
     * auto-record project revenue as a financial transaction.
     */
    public function updated(Project $project): void
    {
        if ($project->isDirty('status') && $project->status === Project::STATUS_COMPLETED) {
            $this->integration->onProjectCompleted($project);
        }
    }
}
