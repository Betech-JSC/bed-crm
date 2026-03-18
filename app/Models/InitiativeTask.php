<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InitiativeTask extends Model
{
    protected $fillable = [
        'account_id', 'initiative_id', 'project_task_id',
        'title', 'description', 'assigned_to',
        'status', 'priority', 'due_date', 'completed_at', 'sort_order',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function initiative(): BelongsTo { return $this->belongsTo(Initiative::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function projectTask(): BelongsTo { return $this->belongsTo(ProjectTask::class); }

    protected static function booted(): void
    {
        static::saved(function (self $task) {
            $task->initiative->recalculateProgress();
        });
    }
}
