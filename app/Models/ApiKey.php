<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'name', 'key', 'secret_hash', 'secret_last4',
        'permissions', 'rate_limit', 'allowed_ips', 'allowed_domains',
        'is_active', 'expires_at', 'last_used_at',
        'total_requests', 'created_by', 'notes',
    ];

    protected $casts = [
        'permissions' => 'array',
        'allowed_ips' => 'array',
        'allowed_domains' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'total_requests' => 'integer',
        'rate_limit' => 'integer',
    ];

    protected $hidden = ['secret_hash'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApiLog::class, 'api_key_id');
    }

    public function getStatusLabelAttribute(): string
    {
        if (!$this->is_active) return 'Disabled';
        if ($this->expires_at && $this->expires_at->isPast()) return 'Expired';
        return 'Active';
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public static function generateKey(): string
    {
        return 'bed_' . Str::random(32);
    }

    public static function generateSecret(): string
    {
        return Str::random(48);
    }
}
