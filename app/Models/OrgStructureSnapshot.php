<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgStructureSnapshot extends Model
{
    protected $fillable = [
        'account_id', 'name', 'description',
        'snapshot_data', 'created_by', 'snapshot_date',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
        'snapshot_date' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Capture current org structure
     */
    public static function capture(int $accountId, string $name, ?string $description = null): static
    {
        $departments = Department::where('account_id', $accountId)
            ->where('is_active', true)
            ->with(['teams.members.user', 'head', 'employees.user'])
            ->get();

        $snapshotData = [
            'departments' => $departments->map(fn ($dept) => [
                'id' => $dept->id,
                'name' => $dept->name,
                'code' => $dept->code,
                'head' => $dept->head ? ['id' => $dept->head->id, 'name' => $dept->head->name] : null,
                'parent_id' => $dept->parent_id,
                'headcount' => $dept->employees->count(),
                'teams' => $dept->teams->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'leader_id' => $t->leader_user_id,
                    'members' => $t->members->map(fn ($m) => [
                        'user_id' => $m->user_id,
                        'name' => $m->user?->name,
                        'position' => $m->position,
                    ])->toArray(),
                ])->toArray(),
            ])->toArray(),
            'total_headcount' => EmployeeProfile::where('account_id', $accountId)->where('status', 'active')->count(),
            'total_departments' => $departments->count(),
            'total_teams' => Team::where('account_id', $accountId)->where('is_active', true)->count(),
            'captured_at' => now()->toISOString(),
        ];

        return static::create([
            'account_id' => $accountId,
            'name' => $name,
            'description' => $description,
            'snapshot_data' => $snapshotData,
            'created_by' => auth()->id(),
            'snapshot_date' => now(),
        ]);
    }
}
