<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectResource extends Model
{
    protected $fillable = [
        'project_id', 'user_id', 'role', 'hourly_rate',
        'allocated_hours', 'logged_hours', 'start_date', 'end_date',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'logged_hours' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public static function getRoles(): array
    {
        return [
            'manager' => 'Project Manager',
            'developer' => 'Developer',
            'designer' => 'Designer',
            'qa' => 'QA',
            'member' => 'Team Member',
        ];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function getCost(): float { return (float) $this->logged_hours * (float) $this->hourly_rate; }
    public function getUtilization(): float { return $this->allocated_hours > 0 ? round(((float) $this->logged_hours / $this->allocated_hours) * 100, 1) : 0; }
}
