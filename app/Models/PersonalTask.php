<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalTask extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'user_id', 'title', 'description',
        'status', 'priority', 'category', 'color',
        'due_date', 'reminder_at', 'completed_at',
        'related_type', 'related_id',
        'checklist', 'tags',
        'sort_order', 'is_pinned',
    ];

    protected $casts = [
        'due_date' => 'date',
        'reminder_at' => 'date',
        'completed_at' => 'datetime',
        'checklist' => 'array',
        'tags' => 'array',
        'is_pinned' => 'boolean',
    ];

    // ── Statuses ──
    public static function getStatuses(): array
    {
        return [
            'todo' => ['label' => 'Cần làm', 'icon' => 'pi pi-circle', 'color' => '#94a3b8'],
            'in_progress' => ['label' => 'Đang làm', 'icon' => 'pi pi-spin pi-spinner', 'color' => '#3b82f6'],
            'done' => ['label' => 'Hoàn thành', 'icon' => 'pi pi-check-circle', 'color' => '#10b981'],
            'cancelled' => ['label' => 'Hủy', 'icon' => 'pi pi-times-circle', 'color' => '#ef4444'],
        ];
    }

    // ── Priorities ──
    public static function getPriorities(): array
    {
        return [
            'low' => ['label' => 'Thấp', 'icon' => 'pi pi-arrow-down', 'color' => '#22c55e'],
            'medium' => ['label' => 'Trung bình', 'icon' => 'pi pi-minus', 'color' => '#f59e0b'],
            'high' => ['label' => 'Cao', 'icon' => 'pi pi-arrow-up', 'color' => '#ef4444'],
            'urgent' => ['label' => 'Khẩn cấp', 'icon' => 'pi pi-exclamation-triangle', 'color' => '#dc2626'],
        ];
    }

    // ── Categories ──
    public static function getCategories(): array
    {
        return [
            'work' => ['label' => 'Công việc', 'icon' => 'pi pi-briefcase', 'color' => '#6366f1'],
            'personal' => ['label' => 'Cá nhân', 'icon' => 'pi pi-user', 'color' => '#8b5cf6'],
            'meeting' => ['label' => 'Cuộc họp', 'icon' => 'pi pi-video', 'color' => '#3b82f6'],
            'follow_up' => ['label' => 'Follow-up', 'icon' => 'pi pi-replay', 'color' => '#f59e0b'],
            'other' => ['label' => 'Khác', 'icon' => 'pi pi-tag', 'color' => '#94a3b8'],
        ];
    }

    // ── Scopes ──
    public function scopeForUser($q, $userId)
    {
        return $q->where('user_id', $userId);
    }

    public function scopeFilter($q, array $filters)
    {
        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $q->where(fn ($q2) => $q2->where('title', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%"));
        }
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['priority'])) $q->where('priority', $filters['priority']);
        if (!empty($filters['category'])) $q->where('category', $filters['category']);
        if (!empty($filters['due'])) {
            match ($filters['due']) {
                'today' => $q->whereDate('due_date', today()),
                'this_week' => $q->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()]),
                'overdue' => $q->whereDate('due_date', '<', today())->where('status', '!=', 'done'),
                'no_date' => $q->whereNull('due_date'),
                default => null,
            };
        }
        return $q;
    }

    // ── Accessors ──
    public function getStatusInfoAttribute(): array
    {
        return static::getStatuses()[$this->status] ?? ['label' => $this->status, 'icon' => 'pi pi-circle', 'color' => '#94a3b8'];
    }

    public function getPriorityInfoAttribute(): array
    {
        return static::getPriorities()[$this->priority] ?? ['label' => $this->priority, 'icon' => 'pi pi-minus', 'color' => '#94a3b8'];
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'done';
    }

    public function getChecklistProgressAttribute(): array
    {
        $items = $this->checklist ?? [];
        $total = count($items);
        $done = collect($items)->where('done', true)->count();
        return ['total' => $total, 'done' => $done, 'percent' => $total > 0 ? round($done / $total * 100) : 0];
    }

    // ── Relations ──
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
