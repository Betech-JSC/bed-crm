<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\SMTPSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     * Exponential backoff: 60s, 120s, 240s
     */
    public array $backoff = [60, 120, 240];

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    public function __construct(
        private int $logId
    ) {}

    public function handle(): void
    {
        $log = NotificationLog::find($this->logId);
        if (!$log || $log->status === NotificationLog::STATUS_DELIVERED) {
            return;
        }

        $metadata = $log->metadata ?? [];
        $email = $log->recipient_email;
        $subject = $metadata['subject'] ?? 'Notification';
        $body = $metadata['body'] ?? '';
        $link = $metadata['link'] ?? null;

        try {
            // Get SMTP settings for the account
            $smtpSetting = SMTPSetting::where('account_id', $log->account_id)
                ->where('is_active', true)
                ->first();

            if (!$smtpSetting) {
                $log->update([
                    'status' => NotificationLog::STATUS_FAILED,
                    'error_message' => 'SMTP settings not configured or inactive',
                ]);
                return;
            }

            // Configure mailer dynamically
            config(['mail.mailers.smtp' => array_merge(
                config('mail.mailers.smtp'),
                $smtpSetting->toMailConfig()
            )]);

            // Build email HTML with localized template
            $locale = $metadata['locale'] ?? 'vi';
            $htmlBody = $this->buildEmailHtml($subject, $body, $link, $locale);

            // Send
            Mail::send([], [], function ($message) use ($smtpSetting, $email, $subject, $htmlBody) {
                $message->from($smtpSetting->from_address, $smtpSetting->from_name)
                    ->to($email)
                    ->subject($subject)
                    ->setBody($htmlBody, 'text/html');
            });

            $log->update([
                'status' => NotificationLog::STATUS_SENT,
                'sent_at' => now(),
            ]);

            Log::info("[NotificationEmail] Sent to {$email}", [
                'log_id' => $log->id,
                'event_type' => $log->event_type,
            ]);

        } catch (\Exception $e) {
            Log::error("[NotificationEmail] Failed for {$email}", [
                'log_id' => $log->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            $log->update([
                'status' => NotificationLog::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw so Laravel handles retry via $backoff
            if ($this->attempts() < $this->tries) {
                $log->markRetrying();
                throw $e;
            }
        }
    }

    /**
     * Handle a job failure after all retries exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        $log = NotificationLog::find($this->logId);
        if ($log) {
            $log->update([
                'status' => NotificationLog::STATUS_FAILED,
                'error_message' => 'All retries exhausted: ' . $exception->getMessage(),
            ]);
        }

        Log::error("[NotificationEmail] Final failure for log #{$this->logId}", [
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Build a clean notification email HTML.
     */
    private function buildEmailHtml(string $subject, string $body, ?string $link, string $locale = 'vi'): string
    {
        $ctaLabel = $locale === 'vi' ? 'Xem chi tiết' : 'View Details';
        $footerText = $locale === 'vi'
            ? 'Đây là email tự động từ hệ thống CRM. Bạn có thể thay đổi cài đặt thông báo trong tài khoản.'
            : 'This is an automated notification from the CRM system. You can adjust notification settings in your account.';

        $ctaButton = $link
            ? "<a href=\"{$link}\" style=\"display:inline-block;padding:10px 24px;background:#6366f1;color:white;text-decoration:none;border-radius:8px;font-weight:600;font-size:14px;\">{$ctaLabel}</a>"
            : '';

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head><meta charset="utf-8"></head>
        <body style="margin:0;padding:0;font-family:'Segoe UI',sans-serif;background:#f8fafc;">
          <div style="max-width:560px;margin:32px auto;background:white;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
            <div style="background:linear-gradient(135deg,#4338ca,#6366f1);padding:24px 32px;">
              <h1 style="margin:0;font-size:18px;color:white;font-weight:700;">{$subject}</h1>
            </div>
            <div style="padding:28px 32px;">
              <p style="margin:0 0 20px;font-size:15px;color:#334155;line-height:1.6;">{$body}</p>
              {$ctaButton}
            </div>
            <div style="padding:16px 32px;border-top:1px solid #f1f5f9;background:#fafbfc;">
              <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">{$footerText}</p>
            </div>
          </div>
        </body>
        </html>
        HTML;
    }
}
