<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'department_id', 'name', 'description',
        'leader_user_id', 'color', 'sort_order', 'is_active',
        'capacity', 'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', 'active');
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(OrgObjective::class);
    }

    public function costEntries(): HasMany
    {
        return $this->hasMany(OrgCostEntry::class);
    }

    public function getUtilizationRate(): float
    {
        if (!$this->capacity || $this->capacity <= 0) return 0;
        return round(($this->activeMembers()->count() / $this->capacity) * 100, 1);
    }
}
