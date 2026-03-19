<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'parent_id', 'name', 'code', 'description',
        'head_user_id', 'color', 'icon', 'sort_order', 'is_active',
        'budget_monthly', 'budget_yearly', 'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'budget_monthly' => 'decimal:2',
        'budget_yearly' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id')->orderBy('sort_order');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class)->orderBy('sort_order');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(OrgPosition::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class);
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(OrgObjective::class);
    }

    public function costEntries(): HasMany
    {
        return $this->hasMany(OrgCostEntry::class);
    }

    /**
     * Get total headcount including all sub-departments
     */
    public function getTotalHeadcount(): int
    {
        $count = $this->employees()->where('status', 'active')->count();
        foreach ($this->children as $child) {
            $count += $child->getTotalHeadcount();
        }
        return $count;
    }

    /**
     * Get total budget including sub-departments
     */
    public function getTotalBudget(): float
    {
        $budget = (float) $this->budget_monthly;
        foreach ($this->children as $child) {
            $budget += $child->getTotalBudget();
        }
        return $budget;
    }

    /**
     * Build org chart tree
     */
    public static function buildTree(int $accountId): array
    {
        $departments = static::where('account_id', $accountId)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children.children', 'teams.members', 'head', 'employees.user'])
            ->orderBy('sort_order')
            ->get();

        return $departments->map(fn ($dept) => static::formatNode($dept))->toArray();
    }

    private static function formatNode(Department $dept): array
    {
        return [
            'id' => $dept->id,
            'name' => $dept->name,
            'code' => $dept->code,
            'color' => $dept->color,
            'head' => $dept->head ? ['id' => $dept->head->id, 'name' => $dept->head->name] : null,
            'headcount' => $dept->employees->where('status', 'active')->count(),
            'teams' => $dept->teams->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'leader' => $t->leader_user_id,
                'members_count' => $t->members->count(),
            ])->toArray(),
            'children' => $dept->children->map(fn ($c) => static::formatNode($c))->toArray(),
        ];
    }
}
