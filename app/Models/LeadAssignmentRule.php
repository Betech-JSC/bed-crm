<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadAssignmentRule extends Model
{
    protected $fillable = [
        'account_id', 'name', 'assignment_type', 'conditions', 'assignees',
        'is_active', 'priority', 'last_assigned_index',
    ];

    protected $casts = [
        'conditions' => 'array',
        'assignees' => 'array',
        'is_active' => 'boolean',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }

    public static function getAssignmentTypes(): array
    {
        return [
            'round_robin' => ['vi' => 'Xoay vòng', 'en' => 'Round Robin'],
            'load_balance' => ['vi' => 'Cân bằng tải', 'en' => 'Load Balance'],
            'score_based' => ['vi' => 'Theo điểm', 'en' => 'Score Based'],
            'territory' => ['vi' => 'Theo khu vực', 'en' => 'Territory'],
        ];
    }
}
