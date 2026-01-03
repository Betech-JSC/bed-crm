<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAutomationStep extends Model
{
    public const TYPE_SEND_EMAIL = 'send_email';
    public const TYPE_WAIT = 'wait';
    public const TYPE_CONDITION = 'condition';
    public const TYPE_TAG = 'tag';

    protected $fillable = [
        'email_automation_id',
        'step_order',
        'step_type',
        'step_config',
        'email_template_id',
        'wait_days',
        'conditions',
        'is_active',
    ];

    protected $casts = [
        'step_config' => 'array',
        'conditions' => 'array',
        'is_active' => 'boolean',
        'wait_days' => 'integer',
        'step_order' => 'integer',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(EmailAutomation::class, 'email_automation_id');
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }
}
