<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseStudyLink extends Model
{
    protected $fillable = [
        'case_study_id', 'linkable_type', 'linkable_id', 'context',
    ];

    public const LINKABLE_TYPES = [
        'lead' => 'Lead',
        'deal' => 'Deal',
        'email_campaign' => 'Email Campaign',
    ];

    public function caseStudy(): BelongsTo
    {
        return $this->belongsTo(CaseStudy::class);
    }

    /**
     * Get the linked entity
     */
    public function linkable()
    {
        return match ($this->linkable_type) {
            'lead' => Lead::find($this->linkable_id),
            'deal' => Deal::find($this->linkable_id),
            'email_campaign' => EmailCampaign::find($this->linkable_id),
            default => null,
        };
    }
}
