<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Activity type constants
     */
    public const TYPE_CALL = 'call';
    public const TYPE_EMAIL = 'email';
    public const TYPE_MEETING = 'meeting';
    public const TYPE_NOTE = 'note';

    protected $fillable = [
        'account_id',
        'subject_type',
        'subject_id',
        'type',
        'title',
        'description',
        'date',
        'user_id',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    /**
     * Get available activity types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_CALL => 'Call',
            self::TYPE_EMAIL => 'Email',
            self::TYPE_MEETING => 'Meeting',
            self::TYPE_NOTE => 'Note',
        ];
    }

    /**
     * Get activity type icon
     */
    public function getIconAttribute(): string
    {
        return match($this->type) {
            self::TYPE_CALL => 'pi-phone',
            self::TYPE_EMAIL => 'pi-envelope',
            self::TYPE_MEETING => 'pi-calendar',
            self::TYPE_NOTE => 'pi-file-edit',
            default => 'pi-circle',
        };
    }

    /**
     * Get activity type color
     */
    public function getColorAttribute(): string
    {
        return match($this->type) {
            self::TYPE_CALL => 'blue',
            self::TYPE_EMAIL => 'green',
            self::TYPE_MEETING => 'purple',
            self::TYPE_NOTE => 'gray',
            default => 'gray',
        };
    }

    /**
     * Polymorphic relation: Activity belongs to Lead, Deal, or Contact
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Activity belongs to Account
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Activity created by User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Order by date (newest first)
     */
    public function scopeOrderByDate($query, string $direction = 'desc')
    {
        return $query->orderBy('date', $direction);
    }
}
