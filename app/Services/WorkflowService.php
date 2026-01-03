<?php

namespace App\Services;

use App\Models\Workflow;
use Illuminate\Support\Facades\Log;

class WorkflowService
{
    /**
     * Trigger workflows for an event
     */
    public function trigger(string $event, $subject, array $data = []): void
    {
        $accountId = $this->getAccountId($subject);
        if (!$accountId) {
            return;
        }

        $workflows = Workflow::where('account_id', $accountId)
            ->where('is_active', true)
            ->get();

        foreach ($workflows as $workflow) {
            if ($workflow->shouldTrigger($event, $subject, $data)) {
                try {
                    $workflow->execute($subject, $data);
                    Log::info("Workflow executed", [
                        'workflow_id' => $workflow->id,
                        'event' => $event,
                        'subject_type' => get_class($subject),
                        'subject_id' => $subject->id ?? null,
                    ]);
                } catch (\Exception $e) {
                    Log::error("Workflow execution failed", [
                        'workflow_id' => $workflow->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Get account ID from subject
     */
    private function getAccountId($subject): ?int
    {
        if (is_object($subject)) {
            if (isset($subject->account_id)) {
                return $subject->account_id;
            }
            if (method_exists($subject, 'account')) {
                return $subject->account?->id;
            }
        }

        return null;
    }
}

