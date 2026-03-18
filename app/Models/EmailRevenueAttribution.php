<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailRevenueAttribution extends Model
{
    public const MODEL_FIRST_TOUCH = 'first_touch';
    public const MODEL_LAST_TOUCH = 'last_touch';
    public const MODEL_LINEAR = 'linear';
    public const MODEL_TIME_DECAY = 'time_decay';

    protected $fillable = [
        'account_id', 'email_campaign_id', 'email_automation_id', 'email_send_id',
        'contact_type', 'contact_id', 'deal_type', 'deal_id', 'deal_value',
        'attribution_model', 'attributed_value', 'attribution_weight',
        'touchpoints_count', 'days_to_conversion', 'conversion_date',
    ];

    protected $casts = [
        'deal_value' => 'decimal:2',
        'attributed_value' => 'decimal:2',
        'attribution_weight' => 'decimal:4',
        'conversion_date' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'email_campaign_id');
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(EmailAutomation::class, 'email_automation_id');
    }
}
