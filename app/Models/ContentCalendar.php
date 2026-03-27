<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentCalendar extends Model
{
    use SoftDeletes;

    protected $table = 'content_calendar';

    protected $fillable = [
        'account_id', 'created_by', 'assigned_to',
        'title', 'description', 'content_type', 'channel', 'status', 'priority',
        'planned_date', 'published_at', 'content_body', 'media', 'tags', 'seo_meta',
        'views_count', 'clicks_count', 'shares_count', 'leads_count',
    ];

    protected $casts = [
        'media' => 'array',
        'tags' => 'array',
        'seo_meta' => 'array',
        'planned_date' => 'date',
        'published_at' => 'datetime',
    ];

    public static function getContentTypes(): array
    {
        return [
            'blog' => ['label' => 'Blog/Bài viết', 'icon' => 'pi pi-file-edit', 'color' => '#6366f1'],
            'social' => ['label' => 'Social Post', 'icon' => 'pi pi-share-alt', 'color' => '#ec4899'],
            'email' => ['label' => 'Email', 'icon' => 'pi pi-envelope', 'color' => '#f59e0b'],
            'video' => ['label' => 'Video', 'icon' => 'pi pi-video', 'color' => '#ef4444'],
            'infographic' => ['label' => 'Infographic', 'icon' => 'pi pi-image', 'color' => '#10b981'],
            'case_study' => ['label' => 'Case Study', 'icon' => 'pi pi-trophy', 'color' => '#8b5cf6'],
        ];
    }

    public static function getChannels(): array
    {
        return [
            'website' => ['label' => 'Website', 'icon' => 'pi pi-globe', 'color' => '#6366f1'],
            'facebook' => ['label' => 'Facebook', 'icon' => 'pi pi-facebook', 'color' => '#1877f2'],
            'instagram' => ['label' => 'Instagram', 'icon' => 'pi pi-instagram', 'color' => '#e4405f'],
            'tiktok' => ['label' => 'TikTok', 'icon' => 'pi pi-video', 'color' => '#010101'],
            'linkedin' => ['label' => 'LinkedIn', 'icon' => 'pi pi-linkedin', 'color' => '#0a66c2'],
            'youtube' => ['label' => 'YouTube', 'icon' => 'pi pi-youtube', 'color' => '#ff0000'],
            'zalo' => ['label' => 'Zalo', 'icon' => 'pi pi-comments', 'color' => '#0068ff'],
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'idea' => ['label' => 'Ý tưởng', 'color' => '#94a3b8'],
            'planned' => ['label' => 'Lên kế hoạch', 'color' => '#6366f1'],
            'in_progress' => ['label' => 'Đang viết', 'color' => '#f59e0b'],
            'review' => ['label' => 'Review', 'color' => '#ec4899'],
            'published' => ['label' => 'Đã đăng', 'color' => '#10b981'],
            'archived' => ['label' => 'Lưu trữ', 'color' => '#cbd5e1'],
        ];
    }

    public function scopeFilter($q, array $filters)
    {
        if (!empty($filters['search'])) $q->where('title', 'like', "%{$filters['search']}%");
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['content_type'])) $q->where('content_type', $filters['content_type']);
        if (!empty($filters['channel'])) $q->where('channel', $filters['channel']);
        if (!empty($filters['month'])) {
            $q->whereMonth('planned_date', substr($filters['month'], 5, 2))
              ->whereYear('planned_date', substr($filters['month'], 0, 4));
        }
        return $q;
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
