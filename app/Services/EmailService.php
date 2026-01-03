<?php

namespace App\Services;

use App\Models\Account;
use App\Models\EmailCampaign;
use App\Models\EmailList;
use App\Models\EmailListContact;
use App\Models\EmailSend;
use App\Models\EmailTemplate;
use App\Models\SMTPSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailService
{
    /**
     * Send campaign emails
     */
    public function sendCampaign(EmailCampaign $campaign): void
    {
        if (
            $campaign->status !== EmailCampaign::STATUS_SENDING &&
            $campaign->status !== EmailCampaign::STATUS_SCHEDULED
        ) {
            return;
        }

        $campaign->status = EmailCampaign::STATUS_SENDING;
        $campaign->save();

        // Get recipients from list
        $listContacts = EmailListContact::where('email_list_id', $campaign->email_list_id)
            ->whereNull('unsubscribed_at')
            ->get();

        $campaign->total_recipients = $listContacts->count();
        $campaign->save();

        foreach ($listContacts as $listContact) {
            try {
                $this->sendEmail(
                    campaign: $campaign,
                    email: $listContact->email,
                    name: $listContact->name,
                    contactType: $listContact->contact_type,
                    contactId: $listContact->contact_id
                );
            } catch (\Exception $e) {
                Log::error('Failed to send campaign email', [
                    'campaign_id' => $campaign->id,
                    'email' => $listContact->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $campaign->status = EmailCampaign::STATUS_SENT;
        $campaign->sent_at = now();
        $campaign->save();

        // Update statistics
        $campaign->updateStatistics();
    }

    /**
     * Send single email (for campaign or automation)
     */
    public function sendEmail(
        ?EmailCampaign $campaign = null,
        ?\App\Models\EmailAutomation $automation = null,
        string $email,
        ?string $name = null,
        ?string $contactType = null,
        ?int $contactId = null,
        ?EmailTemplate $template = null,
        ?string $subject = null,
        ?string $bodyHtml = null,
        ?string $bodyText = null,
        array $variables = []
    ): ?EmailSend {
        $account = $campaign?->account ?? $automation?->account;

        if (!$account) {
            throw new \Exception('Account not found');
        }

        // Get SMTP settings
        $smtpSetting = $account->smtpSetting;
        if (!$smtpSetting || !$smtpSetting->is_active) {
            throw new \Exception('SMTP settings not configured or inactive');
        }

        // Get template if provided
        if ($template) {
            $subject = $subject ?? $template->subject;
            $bodyHtml = $bodyHtml ?? $template->body_html;
            $bodyText = $bodyText ?? $template->body_text;
        }

        // Get from campaign if not provided
        if ($campaign && !$subject) {
            $subject = $campaign->subject;
            $bodyHtml = $campaign->body_html;
            $bodyText = $campaign->body_text;
        }

        if (!$subject || (!$bodyHtml && !$bodyText)) {
            throw new \Exception('Email subject and body are required');
        }

        // Replace variables
        $subject = $this->replaceVariables($subject, $name, $email, $variables);
        $bodyHtml = $bodyHtml ? $this->replaceVariables($bodyHtml, $name, $email, $variables) : null;
        $bodyText = $bodyText ? $this->replaceVariables($bodyText, $name, $email, $variables) : null;

        // Create email send record
        $emailSend = EmailSend::create([
            'account_id' => $account->id,
            'sendable_type' => $campaign ? 'campaign' : 'automation',
            'sendable_id' => $campaign?->id ?? $automation?->id,
            'email_template_id' => $template?->id,
            'contact_type' => $contactType,
            'contact_id' => $contactId,
            'email' => $email,
            'name' => $name,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'status' => EmailSend::STATUS_PENDING,
            'message_id' => null, // Will be set after sending
        ]);

        // Generate tracking message ID
        $messageId = $emailSend->generateMessageId();
        $emailSend->message_id = $messageId;
        $emailSend->save();

        // Add tracking pixels and links
        $bodyHtml = $this->addTracking($bodyHtml, $emailSend);
        $bodyText = $bodyText; // Plain text doesn't need tracking

        try {
            // Configure mail with SMTP settings
            config(['mail.mailers.smtp' => array_merge(
                config('mail.mailers.smtp'),
                $smtpSetting->toMailConfig()
            )]);

            // Send email
            Mail::send([], [], function ($message) use ($smtpSetting, $email, $name, $subject, $bodyHtml, $bodyText, $messageId) {
                $message->from($smtpSetting->from_address, $smtpSetting->from_name)
                    ->to($email, $name)
                    ->subject($subject)
                    ->setBody($bodyHtml ?? $bodyText, $bodyHtml ? 'text/html' : 'text/plain')
                    ->getHeaders()
                    ->addTextHeader('Message-ID', $messageId);
            });

            $emailSend->status = EmailSend::STATUS_SENT;
            $emailSend->sent_at = now();
            $emailSend->save();

            // Mark as delivered (in real implementation, use webhook or check bounce)
            $emailSend->status = EmailSend::STATUS_DELIVERED;
            $emailSend->delivered_at = now();
            $emailSend->save();

            return $emailSend;
        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'email_send_id' => $emailSend->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            $emailSend->status = EmailSend::STATUS_FAILED;
            $emailSend->save();

            throw $e;
        }
    }

    /**
     * Replace variables in email content
     */
    private function replaceVariables(string $content, ?string $name, string $email, array $variables = []): string
    {
        $replacements = [
            '{{name}}' => $name ?? '',
            '{{email}}' => $email,
            '{{first_name}}' => $name ? explode(' ', $name)[0] : '',
            '{{unsubscribe_url}}' => route('email.unsubscribe', ['token' => 'TOKEN_PLACEHOLDER']),
        ];

        // Add custom variables
        foreach ($variables as $key => $value) {
            $replacements['{{' . $key . '}}'] = $value;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Add tracking pixels and click tracking to HTML
     */
    private function addTracking(?string $bodyHtml, EmailSend $emailSend): ?string
    {
        if (!$bodyHtml) {
            return null;
        }

        // Add open tracking pixel
        $openTrackingUrl = route('email.track.open', ['messageId' => $emailSend->message_id]);
        $trackingPixel = '<img src="' . $openTrackingUrl . '" width="1" height="1" style="display:none;" />';

        // Insert before closing body tag, or at the end if no body tag
        if (stripos($bodyHtml, '</body>') !== false) {
            $bodyHtml = str_replace('</body>', $trackingPixel . '</body>', $bodyHtml);
        } else {
            $bodyHtml .= $trackingPixel;
        }

        // Add click tracking to all links
        $clickTrackingUrl = route('email.track.click', ['messageId' => $emailSend->message_id]);
        $bodyHtml = preg_replace_callback(
            '/<a\s+([^>]*href=["\'])([^"\']+)(["\'][^>]*)>/i',
            function ($matches) use ($clickTrackingUrl) {
                $url = $matches[2];
                // Don't track unsubscribe links
                if (stripos($url, 'unsubscribe') !== false) {
                    return $matches[0];
                }
                $trackedUrl = $clickTrackingUrl . '?url=' . urlencode($url);
                return '<a ' . $matches[1] . $trackedUrl . $matches[3] . '>';
            },
            $bodyHtml
        );

        return $bodyHtml;
    }

    /**
     * Track email open
     */
    public function trackOpen(string $messageId, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        $emailSend = EmailSend::where('message_id', $messageId)->first();

        if (!$emailSend) {
            return;
        }

        // Create open record
        $emailSend->opens()->create([
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'opened_at' => now(),
        ]);

        // Update email send
        if (!$emailSend->opened_at) {
            $emailSend->opened_at = now();
        }
        $emailSend->open_count = $emailSend->opens()->count();
        $emailSend->save();

        // Update campaign/automation statistics
        if ($emailSend->sendable_type === 'campaign') {
            $campaign = EmailCampaign::find($emailSend->sendable_id);
            if ($campaign) {
                $campaign->updateStatistics();
            }
        }
    }

    /**
     * Track email click
     */
    public function trackClick(string $messageId, string $url, ?string $ipAddress = null, ?string $userAgent = null): ?string
    {
        $emailSend = EmailSend::where('message_id', $messageId)->first();

        if (!$emailSend) {
            return $url;
        }

        // Create click record
        $emailSend->clicks()->create([
            'url' => $url,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'clicked_at' => now(),
        ]);

        // Update email send
        if (!$emailSend->clicked_at) {
            $emailSend->clicked_at = now();
        }
        $emailSend->click_count = $emailSend->clicks()->count();
        $emailSend->save();

        // Update campaign/automation statistics
        if ($emailSend->sendable_type === 'campaign') {
            $campaign = EmailCampaign::find($emailSend->sendable_id);
            if ($campaign) {
                $campaign->updateStatistics();
            }
        }

        return $url;
    }
}
