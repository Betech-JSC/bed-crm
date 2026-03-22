<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id', 'product_id', 'name', 'description',
        'unit', 'quantity', 'unit_price', 'discount_percent', 'tax_rate',
        'total', 'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    // Auto-calculate total on save
    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $subtotal = $item->quantity * $item->unit_price;
            $discount = $subtotal * $item->discount_percent / 100;
            $item->total = $subtotal - $discount;
        });
    }
}
