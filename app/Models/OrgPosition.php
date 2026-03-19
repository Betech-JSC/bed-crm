<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgPosition extends Model
{
    public const LEVELS = [
        'c_level' => 'C-Level',
        'director' => 'Director',
        'manager' => 'Manager',
        'lead' => 'Lead',
        'senior' => 'Senior',
        'staff' => 'Staff',
        'junior' => 'Junior',
        'intern' => 'Intern',
    ];

    protected $fillable = [
        'account_id', 'department_id', 'title', 'level',
        'description', 'responsibilities', 'required_skills',
        'salary_range_min', 'salary_range_max', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'responsibilities' => 'array',
        'required_skills' => 'array',
        'is_active' => 'boolean',
        'salary_range_min' => 'decimal:2',
        'salary_range_max' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class);
    }
}
