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

    protected $fillable = [
        'account_id',
        'lead_id',
        'title',
        'stage',
        'value',
        'expected_close_date',
        'status',
        'lost_reason',
        'assigned_to',
        'notes',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expected_close_date' => 'date',
    ];

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
