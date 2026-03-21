<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WikiArticle extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'account_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'is_pinned',
        'created_by',
        'updated_by',
        'published_at',
        'views_count',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
                // Ensure uniqueness within account
                $base = $article->slug;
                $i = 1;
                while (self::where('account_id', $article->account_id)->where('slug', $article->slug)->exists()) {
                    $article->slug = $base . '-' . $i++;
                }
            }
        });
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Bản nháp',
            self::STATUS_PUBLISHED => 'Đã xuất bản',
        ];
    }

    public function getStatusSeverityAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'warning',
            self::STATUS_PUBLISHED => 'success',
            default => 'secondary',
        };
    }

    /**
     * Get a short preview of the content
     */
    public function getPreviewAttribute(): string
    {
        if ($this->excerpt) return $this->excerpt;
        return Str::limit(strip_tags($this->content), 150);
    }

    /**
     * Increment view count
     */
    public function recordView(): void
    {
        $this->increment('views_count');
    }

    /**
     * Create a new version snapshot
     */
    public function createVersion(?string $changeSummary = null): WikiArticleVersion
    {
        $latestVersion = $this->versions()->max('version_number') ?? 0;

        return $this->versions()->create([
            'version_number' => $latestVersion + 1,
            'title' => $this->title,
            'content' => $this->content,
            'edited_by' => auth()->id(),
            'change_summary' => $changeSummary,
        ]);
    }

    // ── Relationships ──

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WikiCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(WikiArticleVersion::class, 'article_id')->orderBy('version_number', 'desc');
    }

    // ── Scopes ──

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        })
        ->when($filters['category_id'] ?? null, fn ($q, $c) => $q->where('category_id', $c))
        ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
        ->when($filters['trashed'] ?? null, function ($q, $trashed) {
            if ($trashed === 'with') $q->withTrashed();
            elseif ($trashed === 'only') $q->onlyTrashed();
        });
    }
}
