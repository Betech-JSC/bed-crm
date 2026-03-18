<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategicKpi extends Model
{
    protected $fillable = [
        'account_id', 'strategic_theme_id', 'kpi_definition_id',
        'target_value', 'weight', 'sort_order',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'weight' => 'integer',
        'sort_order' => 'integer',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function theme(): BelongsTo { return $this->belongsTo(StrategicTheme::class, 'strategic_theme_id'); }
    public function kpiDefinition(): BelongsTo { return $this->belongsTo(KpiDefinition::class); }

    /**
     * Get current achievement from latest KPI value.
     */
    public function getCurrentAchievement(): float
    {
        $latestValue = $this->kpiDefinition->values()->latest()->first();
        if (!$latestValue || (float) $this->target_value <= 0) return 0;

        return round(((float) ($latestValue->value ?? 0) / (float) $this->target_value) * 100, 1);
    }
}
