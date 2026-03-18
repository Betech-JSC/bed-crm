<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Stage constants
     */
    public const STAGE_PROSPECTING = 'prospecting';
    public const STAGE_QUALIFICATION = 'qualification';
    public const STAGE_PROPOSAL = 'proposal';
    public const STAGE_NEGOTIATION = 'negotiation';
    public const STAGE_CLOSING = 'closing';

    /**
     * Status constants
     */
    public const STATUS_OPEN = 'open';
    public const STATUS_WON = 'won';
    public const STATUS_LOST = 'lost';

    // Forecast category constants
    public const FORECAST_PIPELINE = 'pipeline';
    public const FORECAST_BEST_CASE = 'best_case';
    public const FORECAST_COMMIT = 'commit';
    public const FORECAST_OMIT = 'omit';

    protected $fillable = [
        'account_id', 'lead_id', 'title', 'stage', 'value',
        'expected_close_date', 'status', 'lost_reason', 'assigned_to', 'notes',
        // Sales Intelligence fields
        'win_probability', 'weighted_value', 'forecast_category',
        'days_in_stage', 'stage_changed_at', 'stage_history', 'days_to_close',
        'gross_margin', 'cost', 'currency', 'close_quarter',
        'competitors', 'pain_points', 'next_steps',
        'health_score', 'at_risk', 'ai_summary',
        'last_activity_at', 'activity_count',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'weighted_value' => 'decimal:2',
        'expected_close_date' => 'date',
        'stage_changed_at' => 'datetime',
        'stage_history' => 'array',
        'competitors' => 'array',
        'pain_points' => 'array',
        'next_steps' => 'array',
        'at_risk' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $deal) {
            // Auto-record stage change history
            if ($deal->isDirty('stage') && $deal->getOriginal('stage') !== $deal->stage) {
                app(\App\Services\SalesIntelligenceService::class)->recordStageChange($deal, $deal->stage);
            }
            // Auto-recalculate weighted value
            if ($deal->isDirty(['value', 'win_probability'])) {
                $deal->weighted_value = round(($deal->value ?? 0) * ($deal->win_probability ?? 10) / 100, 2);
            }
        });
    }


    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')->orderBy('date', 'desc');
    }

    public function aiInsight(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DealAiInsight::class);
    }

    public function getHealthLabelAttribute(): string
    {
        return match (true) {
            $this->health_score >= 70 => 'healthy',
            $this->health_score >= 40 => 'at_risk',
            default => 'critical',
        };
    }

    public static function getForecastCategories(): array
    {
        return [
            self::FORECAST_COMMIT => ['vi' => 'Cam kết', 'en' => 'Commit'],
            self::FORECAST_BEST_CASE => ['vi' => 'Tốt nhất', 'en' => 'Best Case'],
            self::FORECAST_PIPELINE => ['vi' => 'Pipeline', 'en' => 'Pipeline'],
            self::FORECAST_OMIT => ['vi' => 'Bỏ qua', 'en' => 'Omit'],
        ];
    }

    /**
     * Get available stages
     */
    public static function getStages(): array
    {
        return [
            self::STAGE_PROSPECTING => 'Prospecting',
            self::STAGE_QUALIFICATION => 'Qualification',
            self::STAGE_PROPOSAL => 'Proposal',
            self::STAGE_NEGOTIATION => 'Negotiation',
            self::STAGE_CLOSING => 'Closing',
        ];
    }

    /**
     * Get available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_WON => 'Won',
            self::STATUS_LOST => 'Lost',
        ];
    }

    /**
     * Scope for filtering
     */
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhereHas('lead', function ($q) use ($search) {
                        $q->where('name', 'like', '%'.$search.'%')
                            ->orWhere('company', 'like', '%'.$search.'%');
                    });
            });
        })
        ->when($filters['stage'] ?? null, function ($query, $stage) {
            $query->where('stage', $stage);
        })
        ->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })
        ->when($filters['assigned_to'] ?? null, function ($query, $assignedTo) {
            $query->where('assigned_to', $assignedTo);
        })
        ->when($filters['trashed'] ?? null, function ($query, $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            }
        });
    }

    /**
     * Scope for open deals only
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope for won deals
     */
    public function scopeWon($query)
    {
        return $query->where('status', self::STATUS_WON);
    }

    /**
     * Scope for lost deals
     */
    public function scopeLost($query)
    {
        return $query->where('status', self::STATUS_LOST);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'deal_id');
    }
}
