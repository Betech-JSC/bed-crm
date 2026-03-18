<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Initiative extends Model
{
    use SoftDeletes;

    public const STATUS_PLANNED = 'planned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    protected $fillable = [
        'account_id', 'objective_id', 'project_id', 'title', 'description',
        'owner_id', 'status', 'priority', 'start_date', 'due_date',
        'completed_at', 'budget', 'actual_cost', 'progress',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'progress' => 'decimal:2',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function objective(): BelongsTo { return $this->belongsTo(Objective::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function tasks(): HasMany { return $this->hasMany(InitiativeTask::class)->orderBy('sort_order'); }

    public function isOverBudget(): bool
    {
        return (float) $this->actual_cost > (float) $this->budget && (float) $this->budget > 0;
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== self::STATUS_COMPLETED;
    }

    /**
     * Recalculate progress from tasks.
     */
    public function recalculateProgress(): void
    {
        $tasks = $this->tasks;
        if ($tasks->isEmpty()) return;

        $done = $tasks->where('status', 'done')->count();
        $this->progress = round(($done / $tasks->count()) * 100, 2);

        if ($this->progress >= 100) {
            $this->status = self::STATUS_COMPLETED;
            $this->completed_at = now();
        }

        $this->save();
    }
}
