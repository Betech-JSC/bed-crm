<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StrategicPlan extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'account_id', 'title', 'vision', 'mission', 'values',
        'time_horizon_start', 'time_horizon_end', 'status', 'created_by',
    ];

    protected $casts = [
        'values' => 'array',
        'time_horizon_start' => 'date',
        'time_horizon_end' => 'date',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function themes(): HasMany { return $this->hasMany(StrategicTheme::class)->orderBy('sort_order'); }

    public function scopeActive($query) { return $query->where('status', self::STATUS_ACTIVE); }

    /**
     * Get the active plan for an account (singleton pattern).
     */
    public static function getActivePlan(int $accountId): ?self
    {
        return static::where('account_id', $accountId)->active()->first();
    }
}
