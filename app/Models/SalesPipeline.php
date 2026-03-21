<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesPipeline extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_pipelines';

    /**
     * Stage constants (the sales pipeline flow)
     */
    public const STAGE_AUDIT = 'audit';
    public const STAGE_CONNECT = 'connect';
    public const STAGE_PROPOSE = 'propose';
    public const STAGE_DISCUSS = 'discuss';
    public const STAGE_QUOTE = 'quote';
    public const STAGE_CLOSED_WON = 'closed_won';
    public const STAGE_CLOSED_LOST = 'closed_lost';

    /**
     * Priority constants
     */
    public const PRIORITY_HOT = 'hot';
    public const PRIORITY_WARM = 'warm';
    public const PRIORITY_COLD = 'cold';

    /**
     * Social channel constants
     */
    public const CHANNEL_ZALO = 'zalo';
    public const CHANNEL_FACEBOOK = 'facebook';
    public const CHANNEL_OTHER = 'other';

    protected $fillable = [
        'account_id',
        'lead_id',
        'deal_id',
        'company_name',
        'contact_name',
        'phone',
        'email',
        'website_url',
        'stage',
        'assigned_to',
        'social_channel',
        'social_account',
        'audit_data',
        'proposal_summary',
        'proposal_file_path',
        'discussion_notes',
        'quote_amount',
        'quote_valid_until',
        'quote_file_path',
        'quote_notes',
        'close_date',
        'close_notes',
        'lost_reason',
        'priority',
        'notes',
    ];

    protected $casts = [
        'audit_data' => 'array',
        'quote_amount' => 'decimal:2',
        'quote_valid_until' => 'date',
        'close_date' => 'date',
    ];

    /**
     * Get pipeline stages (open stages only, for kanban)
     */
    public static function getStages(): array
    {
        return [
            self::STAGE_AUDIT => 'Audit',
            self::STAGE_CONNECT => 'Kết bạn',
            self::STAGE_PROPOSE => 'Gửi giải pháp',
            self::STAGE_DISCUSS => 'Trao đổi',
            self::STAGE_QUOTE => 'Báo giá',
            self::STAGE_CLOSED_WON => 'Chốt thành công',
            self::STAGE_CLOSED_LOST => 'Chốt thất bại',
        ];
    }

    /**
     * Get open stages only (for kanban board)
     */
    public static function getOpenStages(): array
    {
        return [
            self::STAGE_AUDIT => 'Audit',
            self::STAGE_CONNECT => 'Kết bạn',
            self::STAGE_PROPOSE => 'Gửi giải pháp',
            self::STAGE_DISCUSS => 'Trao đổi',
            self::STAGE_QUOTE => 'Báo giá',
        ];
    }

    /**
     * Get priorities
     */
    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_HOT => 'Hot',
            self::PRIORITY_WARM => 'Warm',
            self::PRIORITY_COLD => 'Cold',
        ];
    }

    /**
     * Get social channels
     */
    public static function getSocialChannels(): array
    {
        return [
            self::CHANNEL_ZALO => 'Zalo',
            self::CHANNEL_FACEBOOK => 'Facebook',
            self::CHANNEL_OTHER => 'Khác',
        ];
    }

    /**
     * Get default audit template
     */
    public static function getAuditTemplate(): array
    {
        return [
            'website' => [
                'has_website' => false,
                'url' => '',
                'speed_score' => null,
                'has_ssl' => false,
                'is_responsive' => false,
                'seo_score' => null,
                'notes' => '',
            ],
            'marketing' => [
                'has_ads' => false,
                'has_fanpage' => false,
                'has_seo' => false,
                'has_content' => false,
                'fanpage_url' => '',
                'notes' => '',
            ],
            'business' => [
                'company_size' => '',
                'industry' => '',
                'estimated_revenue' => '',
                'competitors' => '',
                'pain_points' => '',
                'notes' => '',
            ],
        ];
    }

    /**
     * Get the priority severity for UI badges
     */
    public function getPrioritySeverityAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_HOT => 'danger',
            self::PRIORITY_WARM => 'warning',
            self::PRIORITY_COLD => 'info',
            default => 'secondary',
        };
    }

    /**
     * Check if pipeline is still open
     */
    public function getIsOpenAttribute(): bool
    {
        return !in_array($this->stage, [self::STAGE_CLOSED_WON, self::STAGE_CLOSED_LOST]);
    }

    /**
     * Get audit completion percentage
     */
    public function getAuditScoreAttribute(): int
    {
        if (!$this->audit_data) {
            return 0;
        }

        $total = 0;
        $filled = 0;

        // Website checks
        $website = $this->audit_data['website'] ?? [];
        $websiteFields = ['has_website', 'has_ssl', 'is_responsive'];
        foreach ($websiteFields as $field) {
            $total++;
            if (!empty($website[$field])) $filled++;
        }
        if (!empty($website['speed_score'])) { $total++; $filled++; } else { $total++; }
        if (!empty($website['seo_score'])) { $total++; $filled++; } else { $total++; }

        // Marketing checks
        $marketing = $this->audit_data['marketing'] ?? [];
        $mktFields = ['has_ads', 'has_fanpage', 'has_seo', 'has_content'];
        foreach ($mktFields as $field) {
            $total++;
            if (!empty($marketing[$field])) $filled++;
        }

        // Business checks
        $business = $this->audit_data['business'] ?? [];
        $bizFields = ['company_size', 'industry', 'estimated_revenue', 'pain_points'];
        foreach ($bizFields as $field) {
            $total++;
            if (!empty($business[$field])) $filled++;
        }

        return $total > 0 ? round(($filled / $total) * 100) : 0;
    }

    // ── Relationships ──

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

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')->orderBy('date', 'desc');
    }

    // ── Scopes ──

    public function scopeOpen($query)
    {
        return $query->whereNotIn('stage', [self::STAGE_CLOSED_WON, self::STAGE_CLOSED_LOST]);
    }

    public function scopeClosedWon($query)
    {
        return $query->where('stage', self::STAGE_CLOSED_WON);
    }

    public function scopeClosedLost($query)
    {
        return $query->where('stage', self::STAGE_CLOSED_LOST);
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        })
        ->when($filters['stage'] ?? null, fn ($q, $s) => $q->where('stage', $s))
        ->when($filters['priority'] ?? null, fn ($q, $p) => $q->where('priority', $p))
        ->when($filters['assigned_to'] ?? null, fn ($q, $a) => $q->where('assigned_to', $a))
        ->when($filters['trashed'] ?? null, function ($q, $trashed) {
            if ($trashed === 'with') $q->withTrashed();
            elseif ($trashed === 'only') $q->onlyTrashed();
        });
    }
}
