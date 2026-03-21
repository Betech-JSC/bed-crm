<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTrendItem extends Model
{
    protected $fillable = [
        'account_id', 'monitor_id', 'source', 'external_id',
        'title', 'description', 'url', 'image_url',
        'author', 'language', 'stars', 'stars_today',
        'forks', 'score', 'comments_count',
        'tags', 'extra_data', 'published_at',
        'is_pinned', 'is_read',
    ];

    protected $casts = [
        'tags' => 'array',
        'extra_data' => 'array',
        'published_at' => 'datetime',
        'is_pinned' => 'boolean',
        'is_read' => 'boolean',
        'stars' => 'integer',
        'stars_today' => 'integer',
        'forks' => 'integer',
        'score' => 'integer',
        'comments_count' => 'integer',
    ];

    // ── Relationships ──
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function monitor(): BelongsTo { return $this->belongsTo(AiTrendMonitor::class, 'monitor_id'); }

    // ── Scopes ──
    public function scopeUnread($query) { return $query->where('is_read', false); }
    public function scopePinned($query) { return $query->where('is_pinned', true); }
    public function scopeSource($query, string $source) { return $query->where('source', $source); }
    public function scopeRecent($query, int $days = 7) { return $query->where('created_at', '>=', now()->subDays($days)); }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['source'] ?? null, fn ($q, $s) => $q->where('source', $s))
              ->when($filters['language'] ?? null, fn ($q, $l) => $q->where('language', $l))
              ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($qq) use ($s) {
                  $qq->where('title', 'like', "%{$s}%")
                     ->orWhere('description', 'like', "%{$s}%")
                     ->orWhere('author', 'like', "%{$s}%");
              }))
              ->when(isset($filters['is_read']), fn ($q) => $filters['is_read'] ? $q->where('is_read', true) : $q->unread())
              ->when(isset($filters['is_pinned']) && $filters['is_pinned'], fn ($q) => $q->pinned());
    }

    // ── Helpers ──
    public function sourceIcon(): string
    {
        return match ($this->source) {
            'github' => 'pi pi-github',
            'hackernews' => 'pi pi-bolt',
            'producthunt' => 'pi pi-megaphone',
            'devto' => 'pi pi-code',
            default => 'pi pi-globe',
        };
    }

    public function sourceColor(): string
    {
        return match ($this->source) {
            'github' => '#333',
            'hackernews' => '#ff6600',
            'producthunt' => '#da552f',
            'devto' => '#0a0a0a',
            default => '#6366f1',
        };
    }
}
