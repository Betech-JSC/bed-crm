<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalWorkflowStep extends Model
{
    protected $fillable = [
        'approval_workflow_id', 'step_order', 'name',
        'approver_type', 'approver_user_id', 'approver_role_id',
        'can_skip', 'timeout_hours', 'instructions',
    ];

    protected $casts = [
        'can_skip' => 'boolean',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    /**
     * Resolve the actual approver user for a given context
     */
    public function resolveApprover(int $requesterId): ?int
    {
        switch ($this->approver_type) {
            case 'user':
                return $this->approver_user_id;

            case 'manager':
                $employee = EmployeeProfile::where('user_id', $requesterId)->first();
                return $employee?->reports_to_user_id;

            case 'department_head':
                $employee = EmployeeProfile::where('user_id', $requesterId)->first();
                $dept = $employee?->department;
                return $dept?->head_user_id;

            case 'ceo':
                $account = User::find($requesterId)?->account;
                return $account?->users()->where('owner', true)->first()?->id;

            default:
                return null;
        }
    }
}
