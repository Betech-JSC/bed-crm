<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'leads';

    /**
     * Status constants
     */
    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_LOST = 'lost';
    public const STATUS_WON = 'won';

    /**
     * Source constants
     */
    public const SOURCE_WEBSITE = 'website';
    public const SOURCE_REFERRAL = 'referral';
    public const SOURCE_SOCIAL = 'social';
    public const SOURCE_EMAIL = 'email';
    public const SOURCE_PHONE = 'phone';
    public const SOURCE_OTHER = 'other';

    protected $fillable = [
        'account_id',
        'name',
        'phone',
        'email',
        'company',
        'industry',
        'source',
        'status',
        'assigned_to',
        'notes',
        'tags',
        'score',
        'icp_id',
        'scoring_details',
        'enrichment_data',
        'last_scored_at',
        // Engagement fields
        'email_opens',
        'email_clicks',
        'last_email_open_at',
        'last_email_click_at',
        // Website behavior fields
        'website_visits',
        'page_views',
        'visited_pages',
        'last_website_visit_at',
        'time_on_site_seconds',
        // SLA tracking fields
        'sla_setting_id',
        'sla_started_at',
        'first_response_at',
        'response_time_minutes',
        'sla_status',
        'sla_warning_sent_at',
        'sla_breach_sent_at',
        'sla_resolved_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'scoring_details' => 'array',
        'enrichment_data' => 'array',
        'visited_pages' => 'array',
        'last_scored_at' => 'datetime',
        'last_email_open_at' => 'datetime',
        'last_email_click_at' => 'datetime',
        'last_website_visit_at' => 'datetime',
        // SLA tracking casts
        'sla_started_at' => 'datetime',
        'first_response_at' => 'datetime',
        'sla_warning_sent_at' => 'datetime',
        'sla_breach_sent_at' => 'datetime',
        'sla_resolved_at' => 'datetime',
    ];

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function deal(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Deal::class);
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')->orderBy('date', 'desc');
    }



    /**
     * Get score quality label
     */
    public function getScoreQualityAttribute(): string
    {
        if ($this->score >= 80) {
            return 'high';
        } elseif ($this->score >= 60) {
            return 'medium';
        } elseif ($this->score >= 40) {
            return 'low';
        }
        return 'poor';
    }

    /**
     * Get lead priority (Hot/Warm/Cold)
     */
    public function getPriorityAttribute(): string
    {
        if (!$this->score) {
            return 'cold';
        }

        if ($this->score >= 70) {
            return 'hot';
        } elseif ($this->score >= 40) {
            return 'warm';
        } else {
            return 'cold';
        }
    }

    /**
     * Get priority label
     */
    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'hot' => 'Hot',
            'warm' => 'Warm',
            'cold' => 'Cold',
            default => 'Unknown',
        };
    }

    /**
     * Get priority severity for UI
     */
    public function getPrioritySeverityAttribute(): string
    {
        return match ($this->priority) {
            'hot' => 'danger',
            'warm' => 'warning',
            'cold' => 'info',
            default => 'secondary',
        };
    }

    /**
     * Calculate comprehensive lead score using LeadScoringService
     */
    public function calculateComprehensiveScore(): array
    {
        $scoringService = app(\App\Services\LeadScoringService::class);
        return $scoringService->calculateScore($this);
    }

    /**
     * Check for duplicate by phone or email
     */
    public static function findDuplicate(?string $phone, ?string $email, int $accountId, ?int $excludeId = null): ?self
    {
        $query = self::where('account_id', $accountId)
            ->where(function ($q) use ($phone, $email) {
                if ($phone) {
                    $q->where('phone', $phone);
                }
                if ($email) {
                    $q->orWhere('email', $email);
                }
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    /**
     * Get available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_CONTACTED => 'Contacted',
            self::STATUS_QUALIFIED => 'Qualified',
            self::STATUS_LOST => 'Lost',
            self::STATUS_WON => 'Won',
        ];
    }

    /**
     * Get available sources
     */
    public static function getSources(): array
    {
        return [
            self::SOURCE_WEBSITE => 'Website',
            self::SOURCE_REFERRAL => 'Referral',
            self::SOURCE_SOCIAL => 'Social Media',
            self::SOURCE_EMAIL => 'Email',
            self::SOURCE_PHONE => 'Phone',
            self::SOURCE_OTHER => 'Other',
        ];
    }

    /**
     * Scope for filtering
     */
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('company', 'like', '%' . $search . '%');
            });
        })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['source'] ?? null, function ($query, $source) {
                $query->where('source', $source);
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

    public function scopeOrderByStatus($query)
    {
        $order = [
            self::STATUS_NEW => 1,
            self::STATUS_CONTACTED => 2,
            self::STATUS_QUALIFIED => 3,
            self::STATUS_WON => 4,
            self::STATUS_LOST => 5,
        ];

        $query->orderByRaw("FIELD(status, 'new', 'contacted', 'qualified', 'won', 'lost')");
    }
}
