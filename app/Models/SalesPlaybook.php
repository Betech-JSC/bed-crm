<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPlaybook extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'name', 'description',
        'industries', 'deal_stages', 'pain_points',
        'talking_points', 'email_template_subject', 'email_template_body',
        'recommended_documents', 'objections_handling', 'next_steps',
        'tags', 'priority', 'is_active',
    ];

    protected $casts = [
        'industries' => 'array',
        'deal_stages' => 'array',
        'pain_points' => 'array',
        'recommended_documents' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
