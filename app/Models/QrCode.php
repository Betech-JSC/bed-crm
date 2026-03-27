<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class QrCode extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'created_by', 'name', 'target_url', 'short_code',
        'qr_type', 'design', 'content_data', 'scans_count', 'unique_scans',
    ];

    protected $casts = [
        'design' => 'array',
        'content_data' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($qr) {
            if (empty($qr->short_code)) {
                $qr->short_code = Str::random(8);
            }
        });
    }

    public function getTrackingUrlAttribute(): string
    {
        return url("/qr/{$this->short_code}");
    }

    public static function getQrTypes(): array
    {
        return [
            'url' => ['label' => 'URL/Website', 'icon' => 'pi pi-link'],
            'vcard' => ['label' => 'vCard/Contact', 'icon' => 'pi pi-id-card'],
            'wifi' => ['label' => 'WiFi', 'icon' => 'pi pi-wifi'],
            'text' => ['label' => 'Text', 'icon' => 'pi pi-file-edit'],
        ];
    }

    public function scopeFilter($q, array $filters)
    {
        if (!empty($filters['search'])) {
            $q->where(fn ($q2) => $q2->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('target_url', 'like', "%{$filters['search']}%"));
        }
        if (!empty($filters['qr_type'])) $q->where('qr_type', $filters['qr_type']);
        return $q;
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
