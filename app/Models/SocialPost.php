<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialPost extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_POSTING = 'posting';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'account_id',
        'content_item_id',
        'social_account_id',
        'created_by',
        'platform',
        'content',
        'media_urls',
        'scheduled_at',
        'posted_at',
        'status',
        'platform_post_id',
        'platform_metadata',
        'error_message',
        'retry_count',
        'last_retry_at',
        'analytics',
        'analytics_synced_at',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'platform_metadata' => 'array',
        'analytics' => 'array',
        'scheduled_at' => 'datetime',
        'posted_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'analytics_synced_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_POSTING => 'Posting',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_FAILED => 'Failed',
        ];
    }
}
