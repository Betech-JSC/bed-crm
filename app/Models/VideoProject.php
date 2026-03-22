<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoProject extends Model
{
    use HasFactory, SoftDeletes;

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCRIPTING = 'scripting';
    public const STATUS_PRODUCING = 'producing';
    public const STATUS_REVIEW = 'review';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    // Video types
    public const TYPE_PRODUCT = 'product';
    public const TYPE_TESTIMONIAL = 'testimonial';
    public const TYPE_TUTORIAL = 'tutorial';
    public const TYPE_PROMO = 'promo';
    public const TYPE_STORY = 'story';
    public const TYPE_UGC = 'ugc';

    // Platforms
    public const PLATFORM_TIKTOK = 'tiktok';
    public const PLATFORM_FACEBOOK = 'facebook';
    public const PLATFORM_INSTAGRAM = 'instagram';
    public const PLATFORM_YOUTUBE = 'youtube';
    public const PLATFORM_ZALO = 'zalo';

    protected $fillable = [
        'account_id', 'created_by', 'title', 'description', 'status',
        'video_type', 'target_platforms', 'aspect_ratio', 'duration_seconds', 'language',
        'ai_script', 'ai_scenes', 'ai_voiceover_text', 'ai_music_suggestion',
        'ai_hashtags', 'ai_caption',
        'product_id', 'product_name', 'product_highlights', 'product_price', 'product_url', 'promo_code',
        'media_assets', 'thumbnail_path', 'output_video_path',
        'cta_text', 'cta_url', 'brand_logo_path', 'brand_color',
        'publish_schedule', 'published_at', 'settings',
    ];

    protected $casts = [
        'target_platforms' => 'array',
        'ai_scenes' => 'array',
        'ai_hashtags' => 'array',
        'media_assets' => 'array',
        'publish_schedule' => 'array',
        'settings' => 'array',
        'product_price' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    // ── Static Helpers ──

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => ['label' => 'Bản nháp', 'en' => 'Draft', 'severity' => 'secondary', 'icon' => 'pi pi-file'],
            self::STATUS_SCRIPTING => ['label' => 'Viết kịch bản', 'en' => 'Scripting', 'severity' => 'info', 'icon' => 'pi pi-pencil'],
            self::STATUS_PRODUCING => ['label' => 'Đang sản xuất', 'en' => 'Producing', 'severity' => 'warning', 'icon' => 'pi pi-video'],
            self::STATUS_REVIEW => ['label' => 'Đang duyệt', 'en' => 'Review', 'severity' => 'help', 'icon' => 'pi pi-eye'],
            self::STATUS_PUBLISHED => ['label' => 'Đã đăng', 'en' => 'Published', 'severity' => 'success', 'icon' => 'pi pi-check-circle'],
            self::STATUS_ARCHIVED => ['label' => 'Lưu trữ', 'en' => 'Archived', 'severity' => 'contrast', 'icon' => 'pi pi-inbox'],
        ];
    }

    public static function getVideoTypes(): array
    {
        return [
            self::TYPE_PRODUCT => ['label' => 'Giới thiệu sản phẩm', 'en' => 'Product Showcase', 'icon' => '🛍️'],
            self::TYPE_TESTIMONIAL => ['label' => 'Đánh giá / Testimonial', 'en' => 'Testimonial', 'icon' => '⭐'],
            self::TYPE_TUTORIAL => ['label' => 'Hướng dẫn sử dụng', 'en' => 'Tutorial/How-to', 'icon' => '📖'],
            self::TYPE_PROMO => ['label' => 'Khuyến mãi / Sale', 'en' => 'Promotion', 'icon' => '🔥'],
            self::TYPE_STORY => ['label' => 'Kể chuyện / Story', 'en' => 'Brand Story', 'icon' => '📺'],
            self::TYPE_UGC => ['label' => 'UGC Style', 'en' => 'User-Generated', 'icon' => '📱'],
        ];
    }

    public static function getPlatforms(): array
    {
        return [
            self::PLATFORM_TIKTOK => ['label' => 'TikTok', 'icon' => 'pi pi-hashtag', 'color' => '#010101', 'ratios' => ['9:16']],
            self::PLATFORM_FACEBOOK => ['label' => 'Facebook Reels', 'icon' => 'pi pi-facebook', 'color' => '#1877f2', 'ratios' => ['9:16', '1:1', '16:9']],
            self::PLATFORM_INSTAGRAM => ['label' => 'Instagram Reels', 'icon' => 'pi pi-instagram', 'color' => '#e4405f', 'ratios' => ['9:16', '1:1', '4:5']],
            self::PLATFORM_YOUTUBE => ['label' => 'YouTube Shorts', 'icon' => 'pi pi-youtube', 'color' => '#ff0000', 'ratios' => ['9:16', '16:9']],
            self::PLATFORM_ZALO => ['label' => 'Zalo', 'icon' => 'pi pi-comments', 'color' => '#0068ff', 'ratios' => ['9:16', '1:1']],
        ];
    }

    public static function getAspectRatios(): array
    {
        return [
            '9:16' => ['label' => '9:16 (Dọc / Portrait)', 'w' => 1080, 'h' => 1920],
            '16:9' => ['label' => '16:9 (Ngang / Landscape)', 'w' => 1920, 'h' => 1080],
            '1:1' => ['label' => '1:1 (Vuông / Square)', 'w' => 1080, 'h' => 1080],
            '4:5' => ['label' => '4:5 (Instagram Feed)', 'w' => 1080, 'h' => 1350],
        ];
    }

    // ── Computed Attributes ──

    public function getStatusInfoAttribute(): array
    {
        return self::getStatuses()[$this->status] ?? self::getStatuses()[self::STATUS_DRAFT];
    }

    public function getSceneCountAttribute(): int
    {
        return count($this->ai_scenes ?? []);
    }

    public function getPlatformLabelsAttribute(): array
    {
        $platforms = self::getPlatforms();
        return collect($this->target_platforms ?? [])
            ->map(fn ($p) => $platforms[$p]['label'] ?? $p)
            ->toArray();
    }

    // ── Relationships ──

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    // ── Scopes ──

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%");
            });
        })
        ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
        ->when($filters['video_type'] ?? null, fn ($q, $t) => $q->where('video_type', $t));
    }
}
