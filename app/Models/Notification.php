<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    // ── Event Types ──
    public const EVENT_LEAD_CREATED = 'lead_created';
    public const EVENT_LEAD_QUALIFIED = 'lead_qualified';
    public const EVENT_DEAL_UPDATED = 'deal_updated';
    public const EVENT_DEAL_WON = 'deal_won';
    public const EVENT_DEAL_LOST = 'deal_lost';
    public const EVENT_TASK_REMINDER = 'task_reminder';
    public const EVENT_TASK_OVERDUE = 'task_overdue';
    public const EVENT_CUSTOMER_ONBOARDING = 'customer_onboarding';
    public const EVENT_CUSTOMER_AT_RISK = 'customer_at_risk';
    public const EVENT_PROJECT_COMPLETED = 'project_completed';
    public const EVENT_PROJECT_OVERDUE = 'project_overdue';
    public const EVENT_INVOICE_CREATED = 'invoice_created';
    public const EVENT_SYSTEM = 'system';

    // ── Severity ──
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_SUCCESS = 'success';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_DANGER = 'danger';

    protected $fillable = [
        'account_id', 'user_id', 'event_type',
        'title', 'body', 'icon', 'severity', 'link',
        'linkable_type', 'linkable_id',
        'read_at', 'data',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'data' => 'array',
    ];

    /**
     * All supported event types with metadata.
     */
    public static function getEventTypes(): array
    {
        return [
            self::EVENT_LEAD_CREATED => ['label_vi' => 'Lead mới', 'label_en' => 'New Lead', 'icon' => 'pi pi-user-plus', 'severity' => 'info'],
            self::EVENT_LEAD_QUALIFIED => ['label_vi' => 'Lead đạt chuẩn', 'label_en' => 'Lead Qualified', 'icon' => 'pi pi-star', 'severity' => 'success'],
            self::EVENT_DEAL_UPDATED => ['label_vi' => 'Deal cập nhật', 'label_en' => 'Deal Updated', 'icon' => 'pi pi-pencil', 'severity' => 'info'],
            self::EVENT_DEAL_WON => ['label_vi' => 'Deal thành công', 'label_en' => 'Deal Won', 'icon' => 'pi pi-check-circle', 'severity' => 'success'],
            self::EVENT_DEAL_LOST => ['label_vi' => 'Deal thất bại', 'label_en' => 'Deal Lost', 'icon' => 'pi pi-times-circle', 'severity' => 'danger'],
            self::EVENT_TASK_REMINDER => ['label_vi' => 'Nhắc nhở công việc', 'label_en' => 'Task Reminder', 'icon' => 'pi pi-clock', 'severity' => 'warning'],
            self::EVENT_TASK_OVERDUE => ['label_vi' => 'Công việc quá hạn', 'label_en' => 'Task Overdue', 'icon' => 'pi pi-exclamation-triangle', 'severity' => 'danger'],
            self::EVENT_CUSTOMER_ONBOARDING => ['label_vi' => 'Khách hàng mới', 'label_en' => 'Customer Onboarding', 'icon' => 'pi pi-users', 'severity' => 'info'],
            self::EVENT_CUSTOMER_AT_RISK => ['label_vi' => 'Khách hàng có rủi ro', 'label_en' => 'Customer At Risk', 'icon' => 'pi pi-exclamation-circle', 'severity' => 'warning'],
            self::EVENT_PROJECT_COMPLETED => ['label_vi' => 'Dự án hoàn thành', 'label_en' => 'Project Completed', 'icon' => 'pi pi-flag', 'severity' => 'success'],
            self::EVENT_PROJECT_OVERDUE => ['label_vi' => 'Dự án quá hạn', 'label_en' => 'Project Overdue', 'icon' => 'pi pi-calendar-times', 'severity' => 'danger'],
            self::EVENT_INVOICE_CREATED => ['label_vi' => 'Hóa đơn mới', 'label_en' => 'Invoice Created', 'icon' => 'pi pi-file', 'severity' => 'info'],
            self::EVENT_SYSTEM => ['label_vi' => 'Hệ thống', 'label_en' => 'System', 'icon' => 'pi pi-cog', 'severity' => 'info'],
        ];
    }

    // ── Relationships ──
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function linkable(): MorphTo { return $this->morphTo(); }

    // ── Helpers ──
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    // ── Scopes ──
    public function scopeUnread($query) { return $query->whereNull('read_at'); }
    public function scopeRead($query) { return $query->whereNotNull('read_at'); }
    public function scopeForUser($query, int $userId) { return $query->where('user_id', $userId); }
    public function scopeOfEvent($query, string $type) { return $query->where('event_type', $type); }
    public function scopeRecent($query, int $days = 30) { return $query->where('created_at', '>=', now()->subDays($days)); }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['event_type'] ?? null, fn ($q, $t) => $q->where('event_type', $t))
              ->when($filters['severity'] ?? null, fn ($q, $s) => $q->where('severity', $s))
              ->when(isset($filters['read']), function ($q) use ($filters) {
                  $filters['read'] ? $q->read() : $q->unread();
              });
    }
}
