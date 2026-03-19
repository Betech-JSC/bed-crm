<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgKeyResult extends Model
{
    protected $fillable = [
        'org_objective_id', 'title', 'description',
        'metric_type', 'start_value', 'target_value', 'current_value',
        'progress', 'unit', 'weight', 'status',
        'owner_user_id', 'milestones', 'check_ins',
    ];

    protected $casts = [
        'start_value' => 'decimal:2',
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'progress' => 'decimal:2',
        'milestones' => 'array',
        'check_ins' => 'array',
    ];

    public function objective(): BelongsTo
    {
        return $this->belongsTo(OrgObjective::class, 'org_objective_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Update progress based on current value
     */
    public function updateProgress(float $newValue, ?string $note = null): void
    {
        $this->current_value = $newValue;

        $range = $this->target_value - $this->start_value;
        if ($range != 0) {
            $this->progress = min(100, max(0, round((($newValue - $this->start_value) / $range) * 100, 2)));
        }

        if ($this->progress >= 100) {
            $this->status = 'completed';
        } elseif ($this->progress > 0) {
            $this->status = 'in_progress';
        }

        // Add check-in entry
        $checkIns = $this->check_ins ?? [];
        $checkIns[] = [
            'value' => $newValue,
            'note' => $note,
            'date' => now()->toDateString(),
            'user_id' => auth()->id(),
        ];
        $this->check_ins = $checkIns;

        $this->save();

        // Cascade recalculation to parent objective
        $this->objective->recalculateProgress();
    }
}
