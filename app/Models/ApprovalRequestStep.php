<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequestStep extends Model
{
    protected $fillable = [
        'approval_request_id', 'approval_workflow_step_id',
        'step_order', 'approver_user_id', 'status', 'comment', 'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflowStep::class, 'approval_workflow_step_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    /**
     * Approve this step
     */
    public function approve(string $comment = ''): void
    {
        $this->update([
            'status' => 'approved',
            'comment' => $comment,
            'approver_user_id' => auth()->id(),
            'decided_at' => now(),
        ]);

        $this->request->advance();
    }
}
