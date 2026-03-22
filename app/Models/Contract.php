<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'contract_number', 'title', 'status', 'contract_type',
        'customer_id', 'deal_id', 'quotation_id', 'contact_id',
        'value', 'currency', 'payment_terms', 'scope_of_work', 'terms_conditions',
        'start_date', 'end_date', 'signed_date', 'renewal_date', 'auto_renew',
        'created_by', 'approved_by', 'approved_at',
        'signed_by_client', 'signed_by_company',
        'file_path', 'metadata',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_date' => 'date',
        'renewal_date' => 'date',
        'approved_at' => 'datetime',
        'auto_renew' => 'boolean',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function deal(): BelongsTo { return $this->belongsTo(Deal::class); }
    public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public static function generateNumber(int $accountId): string
    {
        $year = date('Y');
        $count = self::where('account_id', $accountId)->whereYear('created_at', $year)->count() + 1;
        return 'HD-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Nháp',
            'pending_approval' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'active' => 'Đang hiệu lực',
            'paused' => 'Tạm dừng',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
            'expired' => 'Hết hạn',
            default => ucfirst($this->status),
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->contract_type) {
            'one_time' => 'Một lần',
            'recurring' => 'Định kỳ',
            'retainer' => 'Retainer',
            'project_based' => 'Theo dự án',
            default => ucfirst($this->contract_type),
        };
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->end_date) return null;
        return max(0, now()->diffInDays($this->end_date, false));
    }

    public function getIsActiveContractAttribute(): bool
    {
        return $this->status === 'active' && (!$this->end_date || $this->end_date->isFuture());
    }
}
