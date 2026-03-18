<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    // ── Income Categories ──
    public const CAT_DEAL_REVENUE = 'deal_revenue';
    public const CAT_PROJECT_REVENUE = 'project_revenue';
    public const CAT_SERVICE_FEE = 'service_fee';
    public const CAT_SUBSCRIPTION = 'subscription_income';
    public const CAT_OTHER_INCOME = 'other_income';

    // ── Expense Categories ──
    public const CAT_SALARY = 'salary';
    public const CAT_OFFICE = 'office';
    public const CAT_SOFTWARE = 'software';
    public const CAT_MARKETING = 'marketing';
    public const CAT_HOSTING = 'hosting';
    public const CAT_TRAVEL = 'travel';
    public const CAT_EQUIPMENT = 'equipment';
    public const CAT_TAX = 'tax';
    public const CAT_INSURANCE = 'insurance';
    public const CAT_CONTRACTOR = 'contractor';
    public const CAT_OTHER_EXPENSE = 'other_expense';

    public static function getIncomeCategories(): array
    {
        return [
            self::CAT_DEAL_REVENUE => 'Deal Revenue',
            self::CAT_PROJECT_REVENUE => 'Project Revenue',
            self::CAT_SERVICE_FEE => 'Service Fee',
            self::CAT_SUBSCRIPTION => 'Subscription Income',
            self::CAT_OTHER_INCOME => 'Other Income',
        ];
    }

    public static function getExpenseCategories(): array
    {
        return [
            self::CAT_SALARY => 'Salary & Wages',
            self::CAT_OFFICE => 'Office & Rent',
            self::CAT_SOFTWARE => 'Software & Tools',
            self::CAT_MARKETING => 'Marketing & Ads',
            self::CAT_HOSTING => 'Hosting & Infrastructure',
            self::CAT_TRAVEL => 'Travel & Transport',
            self::CAT_EQUIPMENT => 'Equipment & Hardware',
            self::CAT_TAX => 'Tax & Compliance',
            self::CAT_INSURANCE => 'Insurance',
            self::CAT_CONTRACTOR => 'Contractors & Freelancers',
            self::CAT_OTHER_EXPENSE => 'Other Expense',
        ];
    }

    public static function getAllCategories(): array
    {
        return array_merge(self::getIncomeCategories(), self::getExpenseCategories());
    }

    protected $fillable = [
        'account_id', 'recorded_by', 'type', 'category', 'description',
        'amount', 'transaction_date', 'reference',
        'linkable_type', 'linkable_id',
        'is_recurring', 'recurring_period', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    // ── Relationships ──
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }

    // ── Helpers ──
    public function isIncome(): bool { return $this->type === self::TYPE_INCOME; }
    public function isExpense(): bool { return $this->type === self::TYPE_EXPENSE; }

    /**
     * Get the signed amount (positive for income, negative for expense).
     */
    public function getSignedAmount(): float
    {
        return $this->isIncome() ? (float) $this->amount : -(float) $this->amount;
    }

    // ── Scopes ──
    public function scopeIncome($query) { return $query->where('type', self::TYPE_INCOME); }
    public function scopeExpense($query) { return $query->where('type', self::TYPE_EXPENSE); }

    public function scopeInPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeInMonth($query, int $year, int $month)
    {
        return $query->whereYear('transaction_date', $year)
                     ->whereMonth('transaction_date', $month);
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $s) {
            $q->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                  ->orWhere('reference', 'like', "%{$s}%");
            });
        })
        ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
        ->when($filters['category'] ?? null, fn ($q, $c) => $q->where('category', $c))
        ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('transaction_date', '>=', $d))
        ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('transaction_date', '<=', $d));
    }
}
