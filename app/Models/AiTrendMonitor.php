<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiTrendMonitor extends Model
{
    // ── Source Types ──
    public const SOURCE_GITHUB = 'github';
    public const SOURCE_HACKERNEWS = 'hackernews';
    public const SOURCE_PRODUCTHUNT = 'producthunt';
    public const SOURCE_DEVTO = 'devto';

    // ── Frequencies ──
    public const FREQ_HOURLY = 'hourly';
    public const FREQ_EVERY_6H = 'every_6h';
    public const FREQ_EVERY_12H = 'every_12h';
    public const FREQ_DAILY = 'daily';
    public const FREQ_WEEKLY = 'weekly';

    protected $fillable = [
        'account_id', 'created_by', 'name', 'source',
        'source_config', 'schedule_frequency', 'schedule_time',
        'schedule_day', 'notify_in_app', 'notify_email',
        'is_active', 'last_run_at', 'next_run_at',
    ];

    protected $casts = [
        'source_config' => 'array',
        'notify_in_app' => 'boolean',
        'notify_email' => 'boolean',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    /**
     * Get all available sources with metadata.
     */
    public static function getSources(): array
    {
        return [
            self::SOURCE_GITHUB => [
                'label' => 'GitHub Trending',
                'icon' => 'pi pi-github',
                'color' => '#333',
                'description' => 'Repositories đang trending trên GitHub',
            ],
            self::SOURCE_HACKERNEWS => [
                'label' => 'Hacker News',
                'icon' => 'pi pi-bolt',
                'color' => '#ff6600',
                'description' => 'Top stories từ Hacker News',
            ],
            self::SOURCE_PRODUCTHUNT => [
                'label' => 'Product Hunt',
                'icon' => 'pi pi-megaphone',
                'color' => '#da552f',
                'description' => 'Sản phẩm mới trên Product Hunt',
            ],
            self::SOURCE_DEVTO => [
                'label' => 'DEV.to',
                'icon' => 'pi pi-code',
                'color' => '#0a0a0a',
                'description' => 'Bài viết trending trên DEV.to',
            ],
        ];
    }

    /**
     * Get available frequencies.
     */
    public static function getFrequencies(): array
    {
        return [
            self::FREQ_HOURLY => 'Mỗi giờ',
            self::FREQ_EVERY_6H => 'Mỗi 6 giờ',
            self::FREQ_EVERY_12H => 'Mỗi 12 giờ',
            self::FREQ_DAILY => 'Hàng ngày',
            self::FREQ_WEEKLY => 'Hàng tuần',
        ];
    }

    // ── Relationships ──
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(AiTrendItem::class, 'monitor_id'); }

    // ── Scopes ──
    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeDue($query) { return $query->active()->where('next_run_at', '<=', now()); }

    // ── Helpers ──
    public function calculateNextRun(): void
    {
        $next = match ($this->schedule_frequency) {
            self::FREQ_HOURLY => now()->addHour(),
            self::FREQ_EVERY_6H => now()->addHours(6),
            self::FREQ_EVERY_12H => now()->addHours(12),
            self::FREQ_DAILY => now()->addDay()->setTimeFromTimeString($this->schedule_time),
            self::FREQ_WEEKLY => now()->next($this->schedule_day ?? 'monday')->setTimeFromTimeString($this->schedule_time),
            default => now()->addDay(),
        };

        $this->update([
            'last_run_at' => now(),
            'next_run_at' => $next,
        ]);
    }
}
