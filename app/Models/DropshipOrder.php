<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DropshipOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'supplier_id', 'order_number', 'shopify_order_id',
        'shopify_order_number', 'customer_name', 'customer_email', 'customer_phone',
        'shipping_name', 'shipping_address', 'shipping_city', 'shipping_country',
        'shipping_zip', 'shipping_method',
        'items', 'subtotal', 'shipping_cost', 'total_cost', 'selling_price', 'profit',
        'currency',
        'order_status', 'fulfillment_status', 'payment_status',
        'supplier_order_id', 'tracking_number', 'tracking_url', 'carrier',
        'ordered_at', 'shipped_at', 'delivered_at', 'cancelled_at',
        'notes', 'created_by', 'metadata',
    ];

    protected $casts = [
        'items' => 'array',
        'metadata' => 'array',
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'profit' => 'decimal:2',
        'ordered_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_ORDERED = 'ordered';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_RETURNED = 'returned';

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(DropshipSupplier::class, 'supplier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->order_status) {
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'ordered' => 'Đã đặt NCC',
            'shipped' => 'Đang vận chuyển',
            'delivered' => 'Đã giao',
            'cancelled' => 'Đã hủy',
            'returned' => 'Hoàn trả',
            default => ucfirst($this->order_status),
        };
    }

    public function getFulfillmentLabelAttribute(): string
    {
        return match ($this->fulfillment_status) {
            'unfulfilled' => 'Chưa hoàn tất',
            'partial' => 'Hoàn tất 1 phần',
            'fulfilled' => 'Đã hoàn tất',
            'delivered' => 'Đã giao hàng',
            default => ucfirst($this->fulfillment_status ?? 'unfulfilled'),
        };
    }

    public function getProfitMarginAttribute(): ?float
    {
        if (!$this->selling_price || $this->selling_price == 0) return null;
        return round($this->profit / $this->selling_price * 100, 1);
    }

    public static function generateNumber($accountId): string
    {
        $prefix = 'DS-' . now()->format('ym');
        $last = static::where('account_id', $accountId)
            ->where('order_number', 'like', $prefix . '%')
            ->max('order_number');
        $seq = $last ? (int) substr($last, -4) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
