<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SystemActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'module',
        'subject_type',
        'subject_id',
        'subject_label',
        'changes',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
        'metadata' => 'array',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Scopes ──

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ── Static Helpers ──

    /**
     * Log an activity.
     */
    public static function log(
        string $action,
        string $module,
        ?Model $subject = null,
        ?string $label = null,
        ?array $changes = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => $module,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'subject_label' => $label,
            'changes' => $changes,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get action label in Vietnamese.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Tạo mới',
            'updated' => 'Cập nhật',
            'deleted' => 'Xóa',
            'restored' => 'Khôi phục',
            'login' => 'Đăng nhập',
            'logout' => 'Đăng xuất',
            'exported' => 'Xuất dữ liệu',
            'imported' => 'Nhập dữ liệu',
            'assigned' => 'Giao việc',
            'status_changed' => 'Đổi trạng thái',
            default => ucfirst($this->action),
        };
    }

    /**
     * Get module label in Vietnamese.
     */
    public function getModuleLabelAttribute(): string
    {
        return match ($this->module) {
            'leads' => 'Leads',
            'contacts' => 'Liên hệ',
            'deals' => 'Thương vụ',
            'customers' => 'Khách hàng',
            'users' => 'Người dùng',
            'organizations' => 'Tổ chức',
            'proposals' => 'Đề xuất',
            'projects' => 'Dự án',
            'tasks' => 'Công việc',
            'wiki' => 'Wiki',
            'settings' => 'Cài đặt',
            'files' => 'Tài liệu',
            'email' => 'Email',
            'social' => 'Mạng xã hội',
            default => ucfirst($this->module),
        };
    }
}
