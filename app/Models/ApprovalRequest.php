<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRequest extends Model
{
    protected $fillable = [
        'account_id', 'approval_workflow_id', 'entity_type', 'entity_id',
        'title', 'description', 'entity_data', 'current_step',
        'status', 'requested_by', 'completed_at',
    ];

    protected $casts = [
        'entity_data' => 'array',
        'completed_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function stepApprovals(): HasMany
    {
        return $this->hasMany(ApprovalRequestStep::class)->orderBy('step_order');
    }

    public function currentStepApproval()
    {
        return $this->stepApprovals()->where('step_order', $this->current_step)->first();
    }

    /**
     * Move to next step or mark complete
     */
    public function advance(): void
    {
        $totalSteps = $this->workflow->steps()->count();

        if ($this->current_step >= $totalSteps) {
            $this->status = 'approved';
            $this->completed_at = now();
        } else {
            $this->current_step++;
            $nextWorkflowStep = $this->workflow->steps()
                ->where('step_order', $this->current_step)
                ->first();

            if ($nextWorkflowStep) {
                ApprovalRequestStep::create([
                    'approval_request_id' => $this->id,
                    'approval_workflow_step_id' => $nextWorkflowStep->id,
                    'step_order' => $this->current_step,
                    'approver_user_id' => $nextWorkflowStep->resolveApprover($this->requested_by),
                ]);
            }
        }

        $this->save();
    }

    /**
     * Reject the entire request
     */
    public function reject(string $comment = ''): void
    {
        $this->status = 'rejected';
        $this->completed_at = now();
        $this->save();

        $currentStep = $this->currentStepApproval();
        if ($currentStep) {
            $currentStep->update([
                'status' => 'rejected',
                'comment' => $comment,
                'decided_at' => now(),
            ]);
        }
    }
}
