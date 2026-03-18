<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTask extends Model
{
    public const STATUS_TODO = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_REVIEW = 'review';
    public const STATUS_DONE = 'done';

    protected $fillable = [
        'project_id', 'assigned_to', 'title', 'description',
        'status', 'priority', 'start_date', 'due_date',
        'estimated_hours', 'actual_hours', 'hourly_cost', 'sort_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'actual_hours' => 'decimal:2',
        'hourly_cost' => 'decimal:2',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_TODO => 'To Do',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_REVIEW => 'Review',
            self::STATUS_DONE => 'Done',
        ];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function assignedUser(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }

    public function getCost(): float
    {
        return (float) $this->actual_hours * (float) $this->hourly_cost;
    }
}
