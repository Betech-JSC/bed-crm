<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    protected $fillable = [
        'account_id', 'api_key_id', 'method', 'endpoint', 'status_code',
        'request_body', 'response_body', 'ip_address', 'user_agent',
        'duration_ms', 'error_message',
    ];

    protected $casts = [
        'request_body' => 'array',
        'response_body' => 'array',
        'status_code' => 'integer',
        'duration_ms' => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function getStatusClassAttribute(): string
    {
        if ($this->status_code >= 200 && $this->status_code < 300) return 'success';
        if ($this->status_code >= 400 && $this->status_code < 500) return 'warning';
        return 'danger';
    }
}
