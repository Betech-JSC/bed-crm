<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiValue extends Model
{
    protected $fillable = [
        'employee_profile_id', 'kpi_definition_id', 'value', 'target',
        'period_label', 'period_start', 'period_end', 'notes',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'target' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id'); }
    public function definition(): BelongsTo { return $this->belongsTo(KpiDefinition::class, 'kpi_definition_id'); }

    public function getAchievement(): float
    {
        if ((float) $this->target <= 0) return 0;
        return round(((float) $this->value / (float) $this->target) * 100, 1);
    }
}
