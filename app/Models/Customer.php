<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    // Lifecycle statuses
    public const STATUS_ONBOARDING = 'onboarding';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_AT_RISK = 'at_risk';
    public const STATUS_CHURNED = 'churned';
    public const STATUS_REACTIVATED = 'reactivated';

    // Renewal statuses
    public const RENEWAL_UPCOMING = 'upcoming';
    public const RENEWAL_IN_PROGRESS = 'in_progress';
    public const RENEWAL_RENEWED = 'renewed';
    public const RENEWAL_LOST = 'lost';

    protected $fillable = [
        'account_id', 'organization_id', 'contact_id', 'deal_id', 'assigned_to',
        'name', 'email', 'phone',
        'lifecycle_status', 'start_date', 'mrr', 'arr',
        'health_score', 'health_factors', 'health_calculated_at',
        'contract_start', 'contract_end', 'contract_term', 'auto_renew', 'renewal_status',
        'notes',
    ];

    protected $casts = [
        'mrr' => 'decimal:2',
        'arr' => 'decimal:2',
        'health_factors' => 'array',
        'health_calculated_at' => 'datetime',
        'start_date' => 'date',
        'contract_start' => 'date',
        'contract_end' => 'date',
        'auto_renew' => 'boolean',
    ];

    public static function getLifecycleStatuses(): array
    {
        return [
            self::STATUS_ONBOARDING => 'Onboarding',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_AT_RISK => 'At Risk',
            self::STATUS_CHURNED => 'Churned',
            self::STATUS_REACTIVATED => 'Reactivated',
        ];
    }

    public static function getRenewalStatuses(): array
    {
        return [
            self::RENEWAL_UPCOMING => 'Upcoming',
            self::RENEWAL_IN_PROGRESS => 'In Progress',
            self::RENEWAL_RENEWED => 'Renewed',
            self::RENEWAL_LOST => 'Lost',
        ];
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    // Relationships
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function deal(): BelongsTo { return $this->belongsTo(Deal::class); }
    public function assignedUser(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function tickets(): HasMany { return $this->hasMany(SupportTicket::class); }
    public function upsellOpportunities(): HasMany { return $this->hasMany(UpsellOpportunity::class); }
    public function healthLogs(): HasMany { return $this->hasMany(CustomerHealthLog::class); }

    // Scopes
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->when($filters['lifecycle_status'] ?? null, fn ($q, $s) => $q->where('lifecycle_status', $s))
        ->when($filters['assigned_to'] ?? null, fn ($q, $a) => $q->where('assigned_to', $a))
        ->when($filters['health_min'] ?? null, fn ($q, $h) => $q->where('health_score', '>=', $h))
        ->when($filters['health_max'] ?? null, fn ($q, $h) => $q->where('health_score', '<=', $h))
        ->when($filters['renewal_status'] ?? null, fn ($q, $r) => $q->where('renewal_status', $r))
        ->when($filters['trashed'] ?? null, function ($q, $trashed) {
            if ($trashed === 'with') $q->withTrashed();
            elseif ($trashed === 'only') $q->onlyTrashed();
        });
    }

    public function scopeAtRisk($query) { return $query->where('lifecycle_status', self::STATUS_AT_RISK); }
    public function scopeActive($query) { return $query->where('lifecycle_status', self::STATUS_ACTIVE); }
    public function scopeChurned($query) { return $query->where('lifecycle_status', self::STATUS_CHURNED); }

    public function scopeRenewingSoon($query, int $days = 30)
    {
        return $query->whereNotNull('contract_end')
            ->where('contract_end', '<=', now()->addDays($days))
            ->where('contract_end', '>=', now());
    }
}
