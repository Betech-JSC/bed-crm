<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiDefinition extends Model
{
    public const UNIT_NUMBER = 'number';
    public const UNIT_CURRENCY = 'currency';
    public const UNIT_PERCENTAGE = 'percentage';
    public const UNIT_HOURS = 'hours';

    public const CATEGORIES = [
        'sales' => 'Sales',
        'support' => 'Support',
        'productivity' => 'Productivity',
        'quality' => 'Quality',
        'custom' => 'Custom',
    ];

    protected $fillable = [
        'account_id', 'name', 'description', 'unit', 'period',
        'category', 'target_value', 'higher_is_better', 'is_active',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'higher_is_better' => 'boolean',
        'is_active' => 'boolean',
    ];

    public static function getUnits(): array
    {
        return [
            self::UNIT_NUMBER => 'Number',
            self::UNIT_CURRENCY => 'Currency (VND)',
            self::UNIT_PERCENTAGE => 'Percentage (%)',
            self::UNIT_HOURS => 'Hours',
        ];
    }

    public static function getPeriods(): array
    {
        return ['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'];
    }

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function values(): HasMany { return $this->hasMany(KpiValue::class); }
}
