<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StrategicTheme extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'account_id', 'strategic_plan_id', 'name', 'description',
        'color', 'icon', 'sort_order', 'status', 'weight',
    ];

    protected $casts = [
        'weight' => 'integer',
        'sort_order' => 'integer',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function plan(): BelongsTo { return $this->belongsTo(StrategicPlan::class, 'strategic_plan_id'); }
    public function objectives(): HasMany { return $this->hasMany(Objective::class)->orderBy('sort_order'); }
    public function strategicKpis(): HasMany { return $this->hasMany(StrategicKpi::class); }

    /**
     * Calculate health score for this theme (0-100).
     */
    public function getHealthScore(): float
    {
        $objectives = $this->objectives()->where('status', '!=', 'cancelled')->get();
        if ($objectives->isEmpty()) return 0;

        $totalWeight = $objectives->count();
        $weightedProgress = $objectives->sum('progress');

        return round($weightedProgress / $totalWeight, 1);
    }
}
