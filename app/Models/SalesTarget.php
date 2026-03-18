<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTarget extends Model
{
    protected $fillable = [
        'account_id', 'user_id', 'year', 'quarter', 'month', 'period_type',
        'revenue_target', 'deals_target', 'leads_target', 'activities_target',
        'revenue_actual', 'deals_actual', 'leads_actual', 'activities_actual',
    ];

    protected $casts = [
        'revenue_target' => 'decimal:2',
        'revenue_actual' => 'decimal:2',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function getRevenueAttainmentAttribute(): ?float
    {
        return $this->revenue_target > 0
            ? round(($this->revenue_actual / $this->revenue_target) * 100, 1)
            : null;
    }

    public function getDealsAttainmentAttribute(): ?float
    {
        return $this->deals_target > 0
            ? round(($this->deals_actual / $this->deals_target) * 100, 1)
            : null;
    }

    // Labels (bilingual)
    public static function getPeriodLabel(string $periodType, int $year, ?int $month, ?int $quarter): string
    {
        return match ($periodType) {
            'month' => Carbon::createFromDate($year, $month, 1)->format('M Y'),
            'quarter' => "Q{$quarter} {$year}",
            default => (string) $year,
        };
    }
}
