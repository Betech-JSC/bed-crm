<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgObjective extends Model
{
    use SoftDeletes;

    public const LEVEL_COMPANY = 'company';
    public const LEVEL_DEPARTMENT = 'department';
    public const LEVEL_TEAM = 'team';
    public const LEVEL_INDIVIDUAL = 'individual';

    public const HEALTH_ON_TRACK = 'on_track';
    public const HEALTH_AT_RISK = 'at_risk';
    public const HEALTH_BEHIND = 'behind';
    public const HEALTH_COMPLETED = 'completed';

    protected $fillable = [
        'account_id', 'parent_id', 'level',
        'department_id', 'team_id', 'owner_user_id',
        'title', 'description', 'period', 'period_label',
        'start_date', 'end_date', 'progress', 'status', 'health',
        'weight', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrgObjective::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(OrgObjective::class, 'parent_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function keyResults(): HasMany
    {
        return $this->hasMany(OrgKeyResult::class);
    }

    /**
     * Recalculate progress from key results
     */
    public function recalculateProgress(): void
    {
        $keyResults = $this->keyResults;
        if ($keyResults->isEmpty()) {
            // Calculate from children objectives
            $children = $this->children()->where('status', '!=', 'cancelled')->get();
            if ($children->isEmpty()) return;

            $totalWeight = $children->sum('weight');
            $weightedProgress = $children->sum(fn ($c) => $c->progress * $c->weight);
            $this->progress = $totalWeight > 0 ? round($weightedProgress / $totalWeight, 2) : 0;
        } else {
            $totalWeight = $keyResults->sum('weight');
            $weightedProgress = $keyResults->sum(fn ($kr) => $kr->progress * $kr->weight);
            $this->progress = $totalWeight > 0 ? round($weightedProgress / $totalWeight, 2) : 0;
        }

        // Auto-determine health
        $daysTotal = $this->start_date && $this->end_date ? $this->start_date->diffInDays($this->end_date) : 90;
        $daysElapsed = $this->start_date ? $this->start_date->diffInDays(now()) : 0;
        $expectedProgress = $daysTotal > 0 ? min(100, ($daysElapsed / $daysTotal) * 100) : 0;

        if ($this->progress >= 100) {
            $this->health = self::HEALTH_COMPLETED;
        } elseif ($this->progress >= $expectedProgress * 0.8) {
            $this->health = self::HEALTH_ON_TRACK;
        } elseif ($this->progress >= $expectedProgress * 0.5) {
            $this->health = self::HEALTH_AT_RISK;
        } else {
            $this->health = self::HEALTH_BEHIND;
        }

        $this->save();

        // Cascade up to parent
        if ($this->parent) {
            $this->parent->recalculateProgress();
        }
    }
}
