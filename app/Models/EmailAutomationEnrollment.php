<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAutomationEnrollment extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXITED = 'exited';
    public const STATUS_GOAL_MET = 'goal_met';

    protected $fillable = [
        'email_automation_id', 'contact_type', 'contact_id', 'email',
        'current_step_id', 'status', 'entered_at', 'next_action_at',
        'completed_at', 'step_history',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
        'next_action_at' => 'datetime',
        'completed_at' => 'datetime',
        'step_history' => 'array',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(EmailAutomation::class, 'email_automation_id');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(EmailAutomationStep::class, 'current_step_id');
    }

    public function contact()
    {
        return $this->morphTo('contact', 'contact_type', 'contact_id');
    }

    /**
     * Record a step execution in the history
     */
    public function recordStep(int $stepId, string $action, array $data = []): void
    {
        $history = $this->step_history ?? [];
        $history[] = [
            'step_id' => $stepId,
            'action' => $action,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];
        $this->update(['step_history' => $history]);
    }
}
