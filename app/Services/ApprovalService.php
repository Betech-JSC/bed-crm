<?php

namespace App\Services;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;

class ApprovalService
{
    /**
     * Submit a new approval request
     *
     * @param int    $accountId
     * @param string $entityType  e.g., 'deal', 'campaign', 'expense'
     * @param int    $entityId
     * @param string $title
     * @param array  $entityData  Snapshot of entity data
     * @return ApprovalRequest|null Returns null if no matching workflow
     */
    public function submit(
        int $accountId,
        string $entityType,
        int $entityId,
        string $title,
        array $entityData = [],
        ?string $description = null
    ): ?ApprovalRequest {
        // Find matching active workflow
        $workflow = ApprovalWorkflow::where('account_id', $accountId)
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->first(fn ($w) => $w->shouldTrigger($entityType, $entityData));

        if (!$workflow || $workflow->steps->isEmpty()) {
            return null; // No workflow matches — auto-approve
        }

        $request = ApprovalRequest::create([
            'account_id' => $accountId,
            'approval_workflow_id' => $workflow->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'title' => $title,
            'description' => $description,
            'entity_data' => $entityData,
            'current_step' => 1,
            'status' => 'pending',
            'requested_by' => auth()->id(),
        ]);

        // Create first step approval
        $firstStep = $workflow->steps()->where('step_order', 1)->first();
        if ($firstStep) {
            ApprovalRequestStep::create([
                'approval_request_id' => $request->id,
                'approval_workflow_step_id' => $firstStep->id,
                'step_order' => 1,
                'approver_user_id' => $firstStep->resolveApprover(auth()->id()),
            ]);
        }

        return $request;
    }

    /**
     * Approve a specific step
     */
    public function approveStep(int $stepId, string $comment = ''): ApprovalRequestStep
    {
        $step = ApprovalRequestStep::findOrFail($stepId);
        $step->approve($comment);
        return $step->fresh();
    }

    /**
     * Reject a request
     */
    public function rejectRequest(int $requestId, string $comment = ''): ApprovalRequest
    {
        $request = ApprovalRequest::findOrFail($requestId);
        $request->reject($comment);
        return $request->fresh();
    }

    /**
     * Get pending approvals for a user
     */
    public function getPendingForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return ApprovalRequest::where('status', 'pending')
            ->whereHas('stepApprovals', function ($q) use ($userId) {
                $q->where('approver_user_id', $userId)
                  ->where('status', 'pending');
            })
            ->with(['workflow', 'requester', 'stepApprovals.approver'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get approval history for an entity
     */
    public function getHistory(string $entityType, int $entityId): \Illuminate\Database\Eloquent\Collection
    {
        return ApprovalRequest::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->with(['workflow', 'requester', 'stepApprovals.approver'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
