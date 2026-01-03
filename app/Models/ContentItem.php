<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentItem extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_TEXT = 'text';
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_CAROUSEL = 'carousel';
    public const TYPE_LINK = 'link';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'account_id',
        'created_by',
        'template_id',
        'type',
        'title',
        'content',
        'metadata',
        'ai_model',
        'ai_metadata',
        'status',
        'tags',
        'usage_count',
    ];

    protected $casts = [
        'metadata' => 'array',
        'ai_metadata' => 'array',
        'tags' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContentTemplate::class, 'template_id');
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_TEXT => 'Text',
            self::TYPE_IMAGE => 'Image',
            self::TYPE_VIDEO => 'Video',
            self::TYPE_CAROUSEL => 'Carousel',
            self::TYPE_LINK => 'Link',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }
}
