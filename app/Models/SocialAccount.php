<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class SocialAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id',
        'platform',
        'platform_account_id',
        'name',
        'username',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'platform_metadata',
        'is_active',
        'is_connected',
        'last_sync_at',
    ];

    protected $casts = [
        'platform_metadata' => 'array',
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
        'is_connected' => 'boolean',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setRefreshTokenAttribute($value): void
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRefreshTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_connected', true);
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
