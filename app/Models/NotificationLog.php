<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETRYING = 'retrying';

    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_IN_APP = 'in_app';

    protected $fillable = [
        'account_id', 'notification_id', 'channel', 'event_type',
        'recipient_email', 'recipient_user_id',
        'status', 'error_message', 'attempt', 'max_attempts',
        'sent_at', 'next_retry_at', 'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function notification(): BelongsTo { return $this->belongsTo(Notification::class); }
    public function recipientUser(): BelongsTo { return $this->belongsTo(User::class, 'recipient_user_id'); }

    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED && $this->attempt < $this->max_attempts;
    }

    public function markRetrying(): void
    {
        $backoff = pow(2, $this->attempt) * 60; // exponential: 2min, 4min, 8min
        $this->update([
            'status' => self::STATUS_RETRYING,
            'attempt' => $this->attempt + 1,
            'next_retry_at' => now()->addSeconds($backoff),
        ]);
    }

    // Scopes
    public function scopePending($query) { return $query->where('status', self::STATUS_PENDING); }
    public function scopeFailed($query) { return $query->where('status', self::STATUS_FAILED); }
    public function scopeRetryable($query)
    {
        return $query->where('status', self::STATUS_RETRYING)
            ->where('next_retry_at', '<=', now());
    }
}
