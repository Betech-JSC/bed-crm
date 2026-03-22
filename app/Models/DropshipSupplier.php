<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DropshipSupplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'name', 'code', 'contact_name', 'email', 'phone',
        'website', 'platform', 'country', 'address',
        'shipping_methods', 'avg_processing_days', 'avg_shipping_days',
        'return_policy', 'payment_terms', 'notes',
        'rating', 'is_active', 'sort_order', 'metadata',
    ];

    protected $casts = [
        'shipping_methods' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'rating' => 'decimal:1',
        'avg_processing_days' => 'integer',
        'avg_shipping_days' => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(DropshipOrder::class, 'supplier_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(DropshipProduct::class, 'supplier_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getOrderCountAttribute(): int
    {
        return $this->orders()->count();
    }

    public function getFulfillmentRateAttribute(): ?float
    {
        $total = $this->orders()->count();
        if ($total === 0) return null;
        $fulfilled = $this->orders()->where('fulfillment_status', 'delivered')->count();
        return round($fulfilled / $total * 100, 1);
    }

    public static function generateCode($accountId): string
    {
        $last = static::where('account_id', $accountId)->max('id') ?? 0;
        return 'SUP-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }
}
