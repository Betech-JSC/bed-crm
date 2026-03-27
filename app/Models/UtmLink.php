<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UtmLink extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'created_by', 'name', 'base_url',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'full_url', 'clicks_count', 'leads_count',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($link) {
            $link->full_url = $link->buildFullUrl();
        });
        static::updating(function ($link) {
            $link->full_url = $link->buildFullUrl();
        });
    }

    public function buildFullUrl(): string
    {
        $params = array_filter([
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'utm_term' => $this->utm_term,
            'utm_content' => $this->utm_content,
        ]);

        $separator = str_contains($this->base_url, '?') ? '&' : '?';
        return $this->base_url . $separator . http_build_query($params);
    }

    // ── Scopes ──
    public function scopeFilter($q, array $filters)
    {
        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $q->where(fn ($q2) => $q2->where('name', 'like', "%{$s}%")
                ->orWhere('utm_campaign', 'like', "%{$s}%")
                ->orWhere('base_url', 'like', "%{$s}%"));
        }
        if (!empty($filters['source'])) $q->where('utm_source', $filters['source']);
        if (!empty($filters['medium'])) $q->where('utm_medium', $filters['medium']);
        if (!empty($filters['campaign'])) $q->where('utm_campaign', $filters['campaign']);
        return $q;
    }

    // ── Presets ──
    public static function getSourcePresets(): array
    {
        return ['google', 'facebook', 'instagram', 'tiktok', 'zalo', 'linkedin', 'youtube', 'twitter', 'email', 'newsletter', 'referral', 'direct'];
    }

    public static function getMediumPresets(): array
    {
        return ['cpc', 'organic', 'social', 'email', 'referral', 'display', 'video', 'affiliate', 'qr_code', 'offline'];
    }

    // ── Relations ──
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
