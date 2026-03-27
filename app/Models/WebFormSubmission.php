<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebFormSubmission extends Model
{
    protected $fillable = [
        'web_form_id', 'account_id', 'lead_id', 'data',
        'ip_address', 'user_agent', 'referrer_url',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'page_url', 'status', 'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    // ── Scopes ──
    public function scopeFilter($q, array $filters)
    {
        if (!empty($filters['search'])) {
            $q->where('data', 'like', "%{$filters['search']}%");
        }
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['form_id'])) $q->where('web_form_id', $filters['form_id']);
        return $q;
    }

    public function scopeUnread($q) { return $q->whereNull('read_at'); }

    // ── Accessors ──
    public function getUtmSummaryAttribute(): ?string
    {
        $parts = array_filter([
            $this->utm_source,
            $this->utm_medium,
            $this->utm_campaign,
        ]);
        return count($parts) ? implode(' / ', $parts) : null;
    }

    // ── Relations ──
    public function form(): BelongsTo { return $this->belongsTo(WebForm::class, 'web_form_id'); }
    public function lead(): BelongsTo { return $this->belongsTo(Lead::class); }
}
