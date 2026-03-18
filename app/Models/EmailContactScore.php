<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailContactScore extends Model
{
    public const LEVEL_HOT = 'hot';
    public const LEVEL_WARM = 'warm';
    public const LEVEL_COLD = 'cold';
    public const LEVEL_INACTIVE = 'inactive';

    protected $fillable = [
        'account_id', 'contact_type', 'contact_id', 'email',
        'engagement_score', 'emails_received', 'emails_opened', 'emails_clicked',
        'pages_viewed', 'last_engaged_at', 'last_emailed_at',
        'engagement_level', 'interests', 'preferred_send_time',
    ];

    protected $casts = [
        'engagement_score' => 'integer',
        'interests' => 'array',
        'preferred_send_time' => 'array',
        'last_engaged_at' => 'datetime',
        'last_emailed_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Recalculate engagement score based on behavior
     */
    public function recalculate(): void
    {
        $score = 0;

        // Email engagement (max 40 points)
        if ($this->emails_received > 0) {
            $openRate = $this->emails_opened / $this->emails_received;
            $clickRate = $this->emails_clicked / $this->emails_received;
            $score += min(20, $openRate * 40);
            $score += min(20, $clickRate * 100);
        }

        // Recency (max 30 points)
        if ($this->last_engaged_at) {
            $daysSince = now()->diffInDays($this->last_engaged_at);
            if ($daysSince <= 7) $score += 30;
            elseif ($daysSince <= 14) $score += 20;
            elseif ($daysSince <= 30) $score += 10;
            elseif ($daysSince <= 60) $score += 5;
        }

        // Page views (max 30 points)
        $score += min(30, $this->pages_viewed * 2);

        $this->engagement_score = min(100, (int) $score);
        $this->engagement_level = match(true) {
            $this->engagement_score >= 70 => self::LEVEL_HOT,
            $this->engagement_score >= 40 => self::LEVEL_WARM,
            $this->engagement_score >= 10 => self::LEVEL_COLD,
            default => self::LEVEL_INACTIVE,
        };

        $this->save();
    }
}
