<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'name', 'sku', 'type', 'category', 'description',
        'unit', 'unit_price', 'cost_price', 'currency', 'tax_rate',
        'image', 'is_active', 'sort_order', 'metadata',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeProducts($query)
    {
        return $query->where('type', 'product');
    }

    public function scopeServices($query)
    {
        return $query->where('type', 'service');
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->unit_price, 0, ',', '.') . ' ' . $this->currency;
    }

    public function getMarginAttribute(): ?float
    {
        if (!$this->cost_price || $this->cost_price == 0) return null;
        return round(($this->unit_price - $this->cost_price) / $this->unit_price * 100, 1);
    }
}
