<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailSegment extends Model
{
    use SoftDeletes;

    public const TYPE_STATIC = 'static';
    public const TYPE_DYNAMIC = 'dynamic';

    protected $fillable = [
        'account_id', 'name', 'description', 'type', 'rules',
        'contacts_count', 'last_computed_at', 'is_active', 'created_by',
    ];

    protected $casts = [
        'rules' => 'array',
        'is_active' => 'boolean',
        'contacts_count' => 'integer',
        'last_computed_at' => 'datetime',
    ];

    /**
     * Dynamic rules engine schema:
     * {
     *   "match": "all|any",
     *   "conditions": [
     *     {"field": "lead.status", "operator": "=", "value": "qualified"},
     *     {"field": "deal.value", "operator": ">", "value": 1000000},
     *     {"field": "behavior.last_opened", "operator": "within_days", "value": 30},
     *     {"field": "tag", "operator": "contains", "value": "enterprise"},
     *     {"field": "engagement_level", "operator": "in", "value": ["hot", "warm"]}
     *   ]
     * }
     */

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function segmentContacts(): HasMany
    {
        return $this->hasMany(EmailSegmentContact::class);
    }

    public function activeContacts(): HasMany
    {
        return $this->hasMany(EmailSegmentContact::class)
            ->whereNull('unsubscribed_at');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(EmailCampaign::class, 'email_segment_id');
    }

    /**
     * Recompute dynamic segment membership
     */
    public function recompute(): int
    {
        if ($this->type !== self::TYPE_DYNAMIC || !$this->rules) {
            return $this->contacts_count;
        }

        $count = app(\App\Services\EmailSegmentService::class)
            ->computeSegment($this);

        $this->update([
            'contacts_count' => $count,
            'last_computed_at' => now(),
        ]);

        return $count;
    }
}
