<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutboundCampaign extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // Step constants
    public const STEP_INTRO = 0;
    public const STEP_FOLLOW_UP_RESEND = 1;
    public const STEP_CASE_STUDY = 2;
    public const STEP_FINAL_OFFER = 3;

    // Score weights
    public const SCORE_EMAIL_OPENED = 10;
    public const SCORE_LINK_CLICKED = 20;
    public const SCORE_REPLIED = 50;

    protected $fillable = [
        'account_id', 'lead_id', 'status',
        'current_step', 'lead_score', 'score_breakdown',
        'email_opened', 'link_clicked', 'replied',
        'first_email_sent_at', 'last_action_at',
        'next_action_at', 'completed_at',
    ];

    protected $casts = [
        'score_breakdown' => 'array',
        'email_opened' => 'boolean',
        'link_clicked' => 'boolean',
        'replied' => 'boolean',
        'first_email_sent_at' => 'datetime',
        'last_action_at' => 'datetime',
        'next_action_at' => 'datetime',
        'completed_at' => 'datetime',
        'lead_score' => 'integer',
        'current_step' => 'integer',
    ];

    // --- Relationships ---

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OutboundCampaignLog::class)->orderBy('created_at', 'desc');
    }

    // --- Lead Status Mapping ---

    public function getLeadStatusAttribute(): string
    {
        if ($this->replied) return 'qualified';
        if ($this->link_clicked || $this->email_opened) return 'engaged';
        if ($this->current_step > 0) return 'contacted';
        return 'new';
    }

    public function getLeadStatusLabelAttribute(): string
    {
        return match ($this->lead_status) {
            'qualified' => 'Qualified',
            'engaged' => 'Engaged',
            'contacted' => 'Contacted',
            default => 'New',
        };
    }

    public function getLeadStatusColorAttribute(): string
    {
        return match ($this->lead_status) {
            'qualified' => 'success',
            'engaged' => 'warning',
            'contacted' => 'info',
            default => 'secondary',
        };
    }

    // --- Score Management ---

    public function addScore(string $reason, int $points): void
    {
        $breakdown = $this->score_breakdown ?? [];
        $breakdown[] = [
            'reason' => $reason,
            'points' => $points,
            'at' => now()->toISOString(),
        ];

        $this->update([
            'lead_score' => $this->lead_score + $points,
            'score_breakdown' => $breakdown,
        ]);

        // Sync score to lead
        $this->lead?->update([
            'score' => ($this->lead->score ?? 0) + $points,
        ]);
    }

    public function getStepNameAttribute(): string
    {
        return match ($this->current_step) {
            self::STEP_INTRO => 'Intro Email',
            self::STEP_FOLLOW_UP_RESEND => 'Follow-up Resend',
            self::STEP_CASE_STUDY => 'Case Study',
            self::STEP_FINAL_OFFER => 'Final Offer',
            default => 'Completed',
        };
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDueForAction($query)
    {
        return $query->active()
            ->where('next_action_at', '<=', now())
            ->whereNotNull('next_action_at');
    }

    // --- Static Helpers ---

    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}
