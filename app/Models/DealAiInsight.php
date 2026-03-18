<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealAiInsight extends Model
{
    protected $table = 'deal_ai_insights';

    protected $fillable = [
        'account_id', 'deal_id', 'predicted_win_probability', 'predicted_close_value',
        'predicted_close_date', 'risk_factors', 'recommended_actions',
        'sentiment', 'engagement_score', 'ai_notes', 'generated_at',
    ];

    protected $casts = [
        'risk_factors' => 'array',
        'recommended_actions' => 'array',
        'generated_at' => 'datetime',
    ];

    public function deal(): BelongsTo { return $this->belongsTo(Deal::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
