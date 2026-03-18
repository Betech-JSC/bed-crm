<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'account_id', 'user_id', 'event_type',
        'in_app', 'email',
    ];

    protected $casts = [
        'in_app' => 'boolean',
        'email' => 'boolean',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    /**
     * Get user preference for an event, with default fallback.
     */
    public static function getForUser(int $userId, string $eventType): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId, 'event_type' => $eventType],
            ['account_id' => auth()->user()->account_id ?? 1, 'in_app' => true, 'email' => true]
        );
    }
}
