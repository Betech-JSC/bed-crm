<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use SoftDeletes;

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'account_id', 'customer_id', 'assigned_to', 'created_by',
        'subject', 'description', 'priority', 'status', 'category',
        'resolved_at', 'first_response_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'first_response_at' => 'datetime',
    ];

    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_WAITING => 'Waiting',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    public static function getCategories(): array
    {
        return [
            'bug' => 'Bug',
            'feature' => 'Feature Request',
            'billing' => 'Billing',
            'general' => 'General',
            'technical' => 'Technical',
        ];
    }

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function assignedUser(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function createdByUser(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeOpen($query) { return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS]); }
    public function scopeResolved($query) { return $query->where('status', self::STATUS_RESOLVED); }
}
