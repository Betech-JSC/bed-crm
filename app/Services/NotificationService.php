<?php

namespace App\Services;

use App\Jobs\SendNotificationEmail;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    // ════════════════════════════════════════════
    //  CORE — Dispatch Notification
    // ════════════════════════════════════════════

    /**
     * Send a notification to one or more users.
     *
     * @param int          $accountId
     * @param string       $eventType     One of Notification::EVENT_* constants
     * @param array        $recipients    Array of User models or user IDs
     * @param array        $payload       ['title_vi', 'title_en', 'body_vi', 'body_en', 'link', 'linkable_type', 'linkable_id', 'data']
     * @param string|null  $severity      info, success, warning, danger (auto-detected if null)
     */
    public function dispatch(int $accountId, string $eventType, array $recipients, array $payload, ?string $severity = null): array
    {
        $eventMeta = Notification::getEventTypes()[$eventType] ?? null;
        if (!$eventMeta) {
            Log::warning("[Notification] Unknown event type: {$eventType}");
            return [];
        }

        $severity = $severity ?? $eventMeta['severity'];
        $icon = $eventMeta['icon'];
        $notifications = [];

        foreach ($recipients as $recipient) {
            $user = $recipient instanceof User ? $recipient : User::find($recipient);
            if (!$user) continue;

            // Check user preferences
            $preference = NotificationPreference::where('user_id', $user->id)
                ->where('event_type', $eventType)
                ->first();

            $sendInApp = $preference ? $preference->in_app : true;
            $sendEmail = $preference ? $preference->email : true;

            // Determine locale (from user or default)
            $locale = $user->locale ?? 'vi';

            // Resolve localized content
            $title = $this->resolveLocalized($payload, 'title', $locale);
            $body = $this->resolveLocalized($payload, 'body', $locale);

            if ($sendInApp) {
                $notification = Notification::create([
                    'account_id' => $accountId,
                    'user_id' => $user->id,
                    'event_type' => $eventType,
                    'title' => $title,
                    'body' => $body,
                    'icon' => $icon,
                    'severity' => $severity,
                    'link' => $payload['link'] ?? null,
                    'linkable_type' => $payload['linkable_type'] ?? null,
                    'linkable_id' => $payload['linkable_id'] ?? null,
                    'data' => $payload['data'] ?? null,
                ]);

                // Log in-app delivery
                NotificationLog::create([
                    'account_id' => $accountId,
                    'notification_id' => $notification->id,
                    'channel' => NotificationLog::CHANNEL_IN_APP,
                    'event_type' => $eventType,
                    'recipient_user_id' => $user->id,
                    'status' => NotificationLog::STATUS_DELIVERED,
                    'sent_at' => now(),
                ]);

                $notifications[] = $notification;
            }

            // Queue email notification
            if ($sendEmail && $user->email) {
                $emailLog = NotificationLog::create([
                    'account_id' => $accountId,
                    'notification_id' => $notification->id ?? null,
                    'channel' => NotificationLog::CHANNEL_EMAIL,
                    'event_type' => $eventType,
                    'recipient_email' => $user->email,
                    'recipient_user_id' => $user->id,
                    'status' => NotificationLog::STATUS_PENDING,
                    'max_attempts' => 3,
                    'metadata' => [
                        'subject' => $title,
                        'body' => $body,
                        'locale' => $locale,
                        'link' => $payload['link'] ?? null,
                    ],
                ]);

                // Dispatch to queue
                SendNotificationEmail::dispatch($emailLog->id)
                    ->onQueue('notifications');
            }
        }

        Log::info("[Notification] Dispatched {$eventType} to " . count($recipients) . " recipients", [
            'event_type' => $eventType,
            'recipient_count' => count($recipients),
        ]);

        return $notifications;
    }

    // ════════════════════════════════════════════
    //  CONVENIENCE — Pre-built event dispatchers
    // ════════════════════════════════════════════

    /**
     * Lead created notification → assigned user + account admins.
     */
    public function notifyLeadCreated(\App\Models\Lead $lead): void
    {
        $recipients = $this->getRecipientsForLead($lead);

        $this->dispatch($lead->account_id, Notification::EVENT_LEAD_CREATED, $recipients, [
            'title_vi' => "Lead mới: {$lead->name}",
            'title_en' => "New Lead: {$lead->name}",
            'body_vi' => "Lead mới từ nguồn {$lead->source}: {$lead->name} ({$lead->email})",
            'body_en' => "New lead from {$lead->source}: {$lead->name} ({$lead->email})",
            'link' => "/leads/{$lead->id}/edit",
            'linkable_type' => 'lead',
            'linkable_id' => $lead->id,
        ]);
    }

    /**
     * Deal updated notification → assigned user.
     */
    public function notifyDealUpdated(\App\Models\Deal $deal, string $change = ''): void
    {
        $recipients = [$deal->assigned_to];

        $changeVi = $change ?: "giai đoạn: {$deal->stage}";
        $changeEn = $change ?: "stage: {$deal->stage}";

        $this->dispatch($deal->account_id, Notification::EVENT_DEAL_UPDATED, $recipients, [
            'title_vi' => "Deal cập nhật: {$deal->title}",
            'title_en' => "Deal Updated: {$deal->title}",
            'body_vi' => "Deal \"{$deal->title}\" đã được cập nhật — {$changeVi}",
            'body_en' => "Deal \"{$deal->title}\" has been updated — {$changeEn}",
            'link' => "/deals/{$deal->id}/edit",
            'linkable_type' => 'deal',
            'linkable_id' => $deal->id,
        ]);
    }

    /**
     * Deal won notification → full team.
     */
    public function notifyDealWon(\App\Models\Deal $deal): void
    {
        $recipients = User::where('account_id', $deal->account_id)->pluck('id')->toArray();
        $value = number_format($deal->value, 0, ',', '.');

        $this->dispatch($deal->account_id, Notification::EVENT_DEAL_WON, $recipients, [
            'title_vi' => "🎉 Deal thành công: {$deal->title}",
            'title_en' => "🎉 Deal Won: {$deal->title}",
            'body_vi' => "Deal \"{$deal->title}\" trị giá {$value}₫ đã thành công!",
            'body_en' => "Deal \"{$deal->title}\" worth {$value}₫ has been won!",
            'link' => "/deals/{$deal->id}/edit",
            'linkable_type' => 'deal',
            'linkable_id' => $deal->id,
        ], Notification::SEVERITY_SUCCESS);
    }

    /**
     * Task reminder notification.
     */
    public function notifyTaskReminder(\App\Models\ProjectTask $task): void
    {
        $userId = $task->assigned_to;
        if (!$userId) return;

        $this->dispatch($task->project->account_id, Notification::EVENT_TASK_REMINDER, [$userId], [
            'title_vi' => "Nhắc nhở: {$task->title}",
            'title_en' => "Reminder: {$task->title}",
            'body_vi' => "Công việc \"{$task->title}\" sắp đến hạn",
            'body_en' => "Task \"{$task->title}\" is due soon",
            'link' => "/projects/{$task->project_id}",
            'linkable_type' => 'task',
            'linkable_id' => $task->id,
        ]);
    }

    /**
     * Customer onboarding notification → assigned CSM.
     */
    public function notifyCustomerOnboarding(\App\Models\Customer $customer): void
    {
        $recipients = [$customer->assigned_to];

        $this->dispatch($customer->account_id, Notification::EVENT_CUSTOMER_ONBOARDING, $recipients, [
            'title_vi' => "Khách hàng mới cần onboarding: {$customer->name}",
            'title_en' => "New Customer Onboarding: {$customer->name}",
            'body_vi' => "Khách hàng \"{$customer->name}\" đang ở giai đoạn onboarding. Hãy bắt đầu quy trình chào đón.",
            'body_en' => "Customer \"{$customer->name}\" is in onboarding stage. Start the welcome process.",
            'link' => "/customers/{$customer->id}",
            'linkable_type' => 'customer',
            'linkable_id' => $customer->id,
        ]);
    }

    // ════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════

    /**
     * Resolve localized field from payload.
     * Tries: title_{locale} → title → fallback to key.
     */
    private function resolveLocalized(array $payload, string $field, string $locale): string
    {
        return $payload["{$field}_{$locale}"]
            ?? $payload["{$field}_vi"]
            ?? $payload["{$field}_en"]
            ?? $payload[$field]
            ?? '';
    }

    /**
     * Get notification recipients for a lead event.
     * Returns assigned user + account admins.
     */
    private function getRecipientsForLead(\App\Models\Lead $lead): array
    {
        $recipients = [];

        if ($lead->assigned_to) {
            $recipients[] = $lead->assigned_to;
        }

        // Also notify admins
        $admins = User::where('account_id', $lead->account_id)
            ->where('owner', true)
            ->pluck('id')
            ->toArray();

        return array_unique(array_merge($recipients, $admins));
    }

    // ════════════════════════════════════════════
    //  QUERY: For Controller
    // ════════════════════════════════════════════

    /**
     * Get notifications for a user with unread count.
     */
    public function getUserNotifications(int $userId, int $limit = 50): array
    {
        $notifications = Notification::forUser($userId)
            ->recent(30)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $unreadCount = Notification::forUser($userId)->unread()->count();

        return [
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ];
    }

    /**
     * Mark all unread notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::forUser($userId)
            ->unread()
            ->update(['read_at' => now()]);
    }
}
