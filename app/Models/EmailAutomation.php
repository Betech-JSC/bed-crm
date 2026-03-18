<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailAutomation extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';

    public const TRIGGER_LEAD_CREATED = 'lead_created';
    public const TRIGGER_CONTACT_CREATED = 'contact_created';
    public const TRIGGER_DEAL_WON = 'deal_won';
    public const TRIGGER_DEAL_STAGE_CHANGED = 'deal_stage_changed';
    public const TRIGGER_DATE_BASED = 'date_based';
    public const TRIGGER_TAG_ADDED = 'tag_added';
    public const TRIGGER_SEGMENT_ENTERED = 'segment_entered';
    public const TRIGGER_FORM_SUBMITTED = 'form_submitted';
    public const TRIGGER_PAGE_VISITED = 'page_visited';

    protected $fillable = [
        'account_id', 'name', 'description',
        'trigger_type', 'trigger_config',
        'entry_conditions', 'exit_conditions', 'goal_config',
        'status', 'contacts_processed', 'emails_sent',
        'active_contacts', 'completed_contacts', 'goal_conversions',
        'revenue_generated', 'created_by',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'entry_conditions' => 'array',
        'exit_conditions' => 'array',
        'goal_config' => 'array',
        'contacts_processed' => 'integer',
        'emails_sent' => 'integer',
        'active_contacts' => 'integer',
        'completed_contacts' => 'integer',
        'goal_conversions' => 'integer',
        'revenue_generated' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(EmailAutomationStep::class)->orderBy('step_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(EmailAutomationEnrollment::class);
    }

    public function activeEnrollments(): HasMany
    {
        return $this->enrollments()->where('status', EmailAutomationEnrollment::STATUS_ACTIVE);
    }

    public function sends(): HasMany
    {
        return $this->hasMany(EmailSend::class, 'sendable_id')
            ->where('sendable_type', 'automation');
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(EmailRevenueAttribution::class);
    }

    /**
     * Get conversion rate
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->contacts_processed <= 0) return 0;
        return round(($this->goal_conversions / $this->contacts_processed) * 100, 2);
    }
}
