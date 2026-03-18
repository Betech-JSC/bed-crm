<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeProfile extends Model
{
    public const DEPARTMENTS = [
        'sales' => 'Sales',
        'marketing' => 'Marketing',
        'engineering' => 'Engineering',
        'design' => 'Design',
        'support' => 'Support',
        'management' => 'Management',
        'hr' => 'HR',
        'finance' => 'Finance',
    ];

    protected $fillable = [
        'account_id', 'user_id', 'department', 'position',
        'hire_date', 'base_salary', 'hourly_rate', 'target_hours_monthly',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'base_salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function kpiValues(): HasMany { return $this->hasMany(KpiValue::class); }
    public function reviews(): HasMany { return $this->hasMany(PerformanceReview::class); }

    public function getTenureMonths(): int
    {
        return $this->hire_date ? $this->hire_date->diffInMonths(now()) : 0;
    }
}
