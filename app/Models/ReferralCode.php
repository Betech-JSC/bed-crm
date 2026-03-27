<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ReferralCode extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'referrer_id', 'code', 'referrer_name', 'referrer_email',
        'reward_type', 'reward_value', 'reward_unit', 'max_uses', 'uses_count',
        'status', 'expires_at',
    ];

    protected $casts = ['expires_at' => 'date'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($code) {
            if (empty($code->code)) {
                $code->code = strtoupper(Str::random(8));
            }
        });
    }

    public function getRewardDisplayAttribute(): string
    {
        return $this->reward_unit === 'percent'
            ? "{$this->reward_value}%" : number_format($this->reward_value) . 'đ';
    }

    public function getConversionRateAttribute(): float
    {
        return $this->uses_count > 0
            ? round(($this->conversions()->where('status', 'converted')->count() / $this->uses_count) * 100, 1) : 0;
    }

    public function getTotalRevenueAttribute(): float
    {
        return $this->conversions()->whereNotNull('deal_value')->sum('deal_value');
    }

    public function scopeFilter($q, array $filters)
    {
        if (!empty($filters['search'])) {
            $q->where(fn ($q2) => $q2->where('referrer_name', 'like', "%{$filters['search']}%")
                ->orWhere('code', 'like', "%{$filters['search']}%"));
        }
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        return $q;
    }

    public function conversions(): HasMany { return $this->hasMany(ReferralConversion::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
