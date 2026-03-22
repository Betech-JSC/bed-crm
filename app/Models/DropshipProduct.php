<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DropshipProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'supplier_id', 'product_id',
        'supplier_sku', 'supplier_product_name', 'supplier_url',
        'cost_price', 'currency', 'moq', 'lead_time_days',
        'variants_map', 'is_active', 'notes', 'metadata',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'moq' => 'integer',
        'lead_time_days' => 'integer',
        'variants_map' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(DropshipSupplier::class, 'supplier_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getProfitMarginAttribute(): ?float
    {
        if (!$this->product || !$this->product->unit_price || $this->cost_price == 0) return null;
        return round(($this->product->unit_price - $this->cost_price) / $this->product->unit_price * 100, 1);
    }
}
