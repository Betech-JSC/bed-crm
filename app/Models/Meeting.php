<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Meeting extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_LIVE = 'live';
    public const STATUS_ENDED = 'ended';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_VIDEO = 'video';
    public const TYPE_AUDIO = 'audio';
    public const TYPE_SCREEN = 'screen_share';

    protected $fillable = [
        'account_id', 'created_by', 'title', 'description', 'room_code',
        'status', 'type', 'scheduled_at', 'started_at', 'ended_at', 'duration_minutes',
        'participants', 'max_participants', 'is_public',
        'record_enabled', 'recording_path', 'recording_url', 'recording_size_mb',
        'ai_transcript', 'ai_summary', 'ai_action_items', 'ai_key_decisions', 'ai_topics',
        'agenda', 'meeting_notes', 'settings', 'password',
    ];

    protected $casts = [
        'participants' => 'array',
        'ai_action_items' => 'array',
        'ai_key_decisions' => 'array',
        'ai_topics' => 'array',
        'settings' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'record_enabled' => 'boolean',
        'is_public' => 'boolean',
    ];

    protected $hidden = ['password'];

    // ── Boot ──
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($meeting) {
            if (!$meeting->room_code) {
                $meeting->room_code = self::generateRoomCode();
            }
        });
    }

    /**
     * Generate unique room code like "BED-A3X-K9M"
     */
    public static function generateRoomCode(): string
    {
        do {
            $code = 'BED-' . strtoupper(Str::random(3)) . '-' . strtoupper(Str::random(3));
        } while (self::where('room_code', $code)->exists());
        return $code;
    }

    // ── Statuses ──
    public static function getStatuses(): array
    {
        return [
            self::STATUS_SCHEDULED => ['label' => 'Đã lên lịch', 'en' => 'Scheduled', 'icon' => 'pi pi-clock', 'color' => '#3b82f6', 'severity' => 'info'],
            self::STATUS_LIVE => ['label' => 'Đang diễn ra', 'en' => 'Live', 'icon' => 'pi pi-circle-fill', 'color' => '#ef4444', 'severity' => 'danger'],
            self::STATUS_ENDED => ['label' => 'Đã kết thúc', 'en' => 'Ended', 'icon' => 'pi pi-check-circle', 'color' => '#10b981', 'severity' => 'success'],
            self::STATUS_CANCELLED => ['label' => 'Đã hủy', 'en' => 'Cancelled', 'icon' => 'pi pi-times-circle', 'color' => '#94a3b8', 'severity' => 'secondary'],
        ];
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_VIDEO => ['label' => 'Video Call', 'icon' => 'pi pi-video'],
            self::TYPE_AUDIO => ['label' => 'Audio Call', 'icon' => 'pi pi-phone'],
            self::TYPE_SCREEN => ['label' => 'Screen Share', 'icon' => 'pi pi-desktop'],
        ];
    }

    // ── Computed ──
    public function getStatusInfoAttribute(): array
    {
        return self::getStatuses()[$this->status] ?? self::getStatuses()[self::STATUS_SCHEDULED];
    }

    public function getParticipantCountAttribute(): int
    {
        return count($this->participants ?? []);
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_minutes) return '—';
        $h = intdiv($this->duration_minutes, 60);
        $m = $this->duration_minutes % 60;
        return $h > 0 ? "{$h}h {$m}m" : "{$m} phút";
    }

    public function getIsLiveAttribute(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    public function getHasRecapAttribute(): bool
    {
        return !empty($this->ai_summary);
    }

    public function getJoinUrlAttribute(): string
    {
        return url("/meetings/{$this->room_code}/join");
    }

    // ── Relationships ──
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    // ── Scopes ──
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $s) {
            $q->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('room_code', 'like', "%{$s}%");
            });
        })
        ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
        ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('type', $t));
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
                     ->where('scheduled_at', '>=', now())
                     ->orderBy('scheduled_at');
    }

    public function scopeLive($query)
    {
        return $query->where('status', self::STATUS_LIVE);
    }
}
