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
    public const TRIGGER_DATE_BASED = 'date_based';
    public const TRIGGER_TAG_ADDED = 'tag_added';

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'trigger_type',
        'trigger_config',
        'status',
        'contacts_processed',
        'emails_sent',
        'created_by',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'contacts_processed' => 'integer',
        'emails_sent' => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(EmailAutomationStep::class)->orderBy('step_order');
    }

    public function sends(): HasMany
    {
        return $this->hasMany(EmailSend::class, 'sendable_id')
            ->where('sendable_type', 'automation');
    }
}
