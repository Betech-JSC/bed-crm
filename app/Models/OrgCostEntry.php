<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgCostEntry extends Model
{
    protected $fillable = [
        'account_id', 'department_id', 'team_id',
        'category', 'description', 'amount',
        'period_label', 'entry_date', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'entry_date' => 'date',
    ];

    public const CATEGORIES = [
        'salary' => 'Salary & Benefits',
        'tools' => 'Tools & Software',
        'marketing' => 'Marketing Spend',
        'office' => 'Office & Facilities',
        'training' => 'Training & Development',
        'travel' => 'Travel & Expense',
        'other' => 'Other',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
