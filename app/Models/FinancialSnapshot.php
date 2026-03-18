<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialSnapshot extends Model
{
    protected $fillable = [
        'account_id', 'period_label', 'period_start', 'period_end',
        'total_income', 'total_expenses', 'net_cashflow',
        'profit_margin', 'burn_rate', 'runway_months', 'cash_balance',
        'income_breakdown', 'expense_breakdown',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_income' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'net_cashflow' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'burn_rate' => 'decimal:2',
        'runway_months' => 'decimal:2',
        'cash_balance' => 'decimal:2',
        'income_breakdown' => 'array',
        'expense_breakdown' => 'array',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
