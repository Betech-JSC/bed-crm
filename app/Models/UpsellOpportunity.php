<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UpsellOpportunity extends Model
{
    use SoftDeletes;

    public const STATUS_IDENTIFIED = 'identified';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_PROPOSED = 'proposed';
    public const STATUS_WON = 'won';
    public const STATUS_LOST = 'lost';

    public const TYPE_UPSELL = 'upsell';
    public const TYPE_CROSS_SELL = 'cross_sell';
    public const TYPE_EXPANSION = 'expansion';

    protected $fillable = [
        'account_id', 'customer_id', 'assigned_to',
        'title', 'description', 'value', 'status', 'type', 'target_close_date',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'target_close_date' => 'date',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_IDENTIFIED => 'Identified',
            self::STATUS_QUALIFIED => 'Qualified',
            self::STATUS_PROPOSED => 'Proposed',
            self::STATUS_WON => 'Won',
            self::STATUS_LOST => 'Lost',
        ];
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_UPSELL => 'Upsell',
            self::TYPE_CROSS_SELL => 'Cross-sell',
            self::TYPE_EXPANSION => 'Expansion',
        ];
    }

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function assignedUser(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
}
