<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'quote_number', 'title', 'status',
        'customer_id', 'lead_id', 'deal_id', 'contact_id',
        'subtotal', 'discount_amount', 'discount_percent', 'tax_amount', 'total', 'currency',
        'issue_date', 'valid_until', 'notes', 'terms', 'rejection_reason',
        'created_by', 'approved_by', 'approved_at', 'sent_at', 'metadata',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'issue_date' => 'date',
        'valid_until' => 'date',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function lead(): BelongsTo { return $this->belongsTo(Lead::class); }
    public function deal(): BelongsTo { return $this->belongsTo(Deal::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    public function contract()
    {
        return $this->hasOne(Contract::class);
    }

    // Auto-generate quote number
    public static function generateNumber(int $accountId): string
    {
        $year = date('Y');
        $count = self::where('account_id', $accountId)->whereYear('created_at', $year)->count() + 1;
        return 'QT-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // Recalculate totals from items
    public function recalculate(): void
    {
        $subtotal = $this->items->sum('total');
        $discountAmount = $this->discount_percent > 0
            ? $subtotal * $this->discount_percent / 100
            : $this->discount_amount;
        $taxableAmount = $subtotal - $discountAmount;
        $taxAmount = $this->items->sum(fn ($item) => $item->total * $item->tax_rate / 100);

        $this->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total' => $taxableAmount + $taxAmount,
        ]);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Nháp',
            'pending_approval' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'sent' => 'Đã gửi',
            'accepted' => 'Chấp nhận',
            'rejected' => 'Từ chối',
            'expired' => 'Hết hạn',
            default => ucfirst($this->status),
        };
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until && $this->valid_until->isPast() && !in_array($this->status, ['accepted', 'rejected']);
    }
}
