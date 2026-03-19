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

    public const EMPLOYMENT_TYPES = [
        'full_time' => 'Full Time',
        'part_time' => 'Part Time',
        'contract' => 'Contract',
        'intern' => 'Intern',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'on_leave' => 'On Leave',
        'probation' => 'Probation',
        'terminated' => 'Terminated',
    ];

    protected $fillable = [
        'account_id', 'user_id', 'employee_code',
        'department_id', 'team_id', 'org_position_id', 'reports_to_user_id',
        'department', 'position', 'employment_type', 'status',
        'hire_date', 'termination_date',
        'base_salary', 'hourly_rate', 'target_hours_monthly',
        'skills', 'certifications',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'base_salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'skills' => 'array',
        'certifications' => 'array',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function departmentRelation(): BelongsTo { return $this->belongsTo(Department::class, 'department_id'); }
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function orgPosition(): BelongsTo { return $this->belongsTo(OrgPosition::class); }
    public function manager(): BelongsTo { return $this->belongsTo(User::class, 'reports_to_user_id'); }
    public function kpiValues(): HasMany { return $this->hasMany(KpiValue::class); }
    public function reviews(): HasMany { return $this->hasMany(PerformanceReview::class); }

    /**
     * Get direct reports (subordinates)
     */
    public function directReports(): HasMany
    {
        return $this->hasMany(EmployeeProfile::class, 'reports_to_user_id', 'user_id');
    }

    /**
     * Get all subordinates recursively
     */
    public function getAllSubordinateIds(): array
    {
        $ids = [];
        $directs = EmployeeProfile::where('reports_to_user_id', $this->user_id)->pluck('user_id')->toArray();
        $ids = array_merge($ids, $directs);
        foreach ($directs as $directId) {
            $sub = EmployeeProfile::where('user_id', $directId)->first();
            if ($sub) {
                $ids = array_merge($ids, $sub->getAllSubordinateIds());
            }
        }
        return array_unique($ids);
    }

    public function getTenureMonths(): int
    {
        return $this->hire_date ? $this->hire_date->diffInMonths(now()) : 0;
    }

    /**
     * Check if user can view this employee's data
     */
    public function isVisibleTo(int $viewerUserId): bool
    {
        // Same user can always see self
        if ($this->user_id === $viewerUserId) return true;

        // Check if viewer is this employee's manager (up the chain)
        $current = $this;
        while ($current->reports_to_user_id) {
            if ($current->reports_to_user_id === $viewerUserId) return true;
            $current = EmployeeProfile::where('user_id', $current->reports_to_user_id)->first();
            if (!$current) break;
        }

        return false;
    }
}
