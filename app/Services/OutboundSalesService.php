<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\OutboundCampaign;
use App\Models\OutboundCampaignLog;
use App\Models\SMTPSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OutboundSalesService
{
    public function __construct(
        private EmailService $emailService,
        private ZaloMessagingService $zaloService,
    ) {}

    /**
     * Initiate outbound campaign for a new lead.
     * Called by listener on LeadCreated event.
     */
    public function initiateCampaign(Lead $lead): ?OutboundCampaign
    {
        // Don't create duplicate campaign
        $existing = OutboundCampaign::where('lead_id', $lead->id)
            ->whereIn('status', [OutboundCampaign::STATUS_ACTIVE, OutboundCampaign::STATUS_PAUSED])
            ->first();

        if ($existing) {
            Log::info('Outbound campaign already exists for lead', ['lead_id' => $lead->id]);
            return $existing;
        }

        $campaign = OutboundCampaign::create([
            'account_id' => $lead->account_id,
            'lead_id' => $lead->id,
            'status' => OutboundCampaign::STATUS_ACTIVE,
            'current_step' => OutboundCampaign::STEP_INTRO,
        ]);

        // Execute step 0: intro email + zalo
        $this->executeStep($campaign);

        return $campaign;
    }

    /**
     * Execute the current step of the campaign.
     */
    public function executeStep(OutboundCampaign $campaign): void
    {
        if ($campaign->status !== OutboundCampaign::STATUS_ACTIVE) {
            return;
        }

        $lead = $campaign->lead;
        if (!$lead) {
            $campaign->update(['status' => OutboundCampaign::STATUS_CANCELLED]);
            return;
        }

        match ($campaign->current_step) {
            OutboundCampaign::STEP_INTRO => $this->sendIntroEmail($campaign, $lead),
            OutboundCampaign::STEP_FOLLOW_UP_RESEND => $this->sendFollowUpResend($campaign, $lead),
            OutboundCampaign::STEP_CASE_STUDY => $this->sendCaseStudyEmail($campaign, $lead),
            OutboundCampaign::STEP_FINAL_OFFER => $this->sendFinalOfferEmail($campaign, $lead),
            default => $this->completeCampaign($campaign),
        };
    }

    /**
     * Process follow-up logic for campaigns due for next action.
     * Called by scheduled command.
     */
    public function processFollowUps(): int
    {
        $campaigns = OutboundCampaign::dueForAction()->with('lead')->get();
        $processed = 0;

        foreach ($campaigns as $campaign) {
            try {
                // If lead replied, mark as completed
                if ($campaign->replied) {
                    $this->completeCampaign($campaign);
                    continue;
                }

                $this->executeStep($campaign);
                $processed++;
            } catch (\Exception $e) {
                Log::error('Failed to process outbound follow-up', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // STEP HANDLERS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    private function sendIntroEmail(OutboundCampaign $campaign, Lead $lead): void
    {
        $companyName = $lead->company ?? 'your company';
        $subject = "Website giúp {$companyName} tăng đơn hàng quốc tế — Xem portfolio ngay";

        $body = $this->buildIntroEmailBody($lead);

        $this->sendOutboundEmail($campaign, $lead, $subject, $body, 'intro');

        // Send Zalo message if phone exists
        if ($lead->phone) {
            $this->sendZaloMessage($campaign, $lead);
        }

        // Update lead status
        $lead->update(['status' => Lead::STATUS_CONTACTED]);

        // Schedule follow-up: if no open after 2 days → resend
        $campaign->update([
            'first_email_sent_at' => now(),
            'last_action_at' => now(),
            'next_action_at' => now()->addDays(2),
            'current_step' => OutboundCampaign::STEP_FOLLOW_UP_RESEND,
        ]);
    }

    private function sendFollowUpResend(OutboundCampaign $campaign, Lead $lead): void
    {
        // Only resend if email hasn't been opened
        if ($campaign->email_opened) {
            // Skip to case study step instead
            $campaign->update([
                'next_action_at' => now()->addDays(3), // 5 days total
                'current_step' => OutboundCampaign::STEP_CASE_STUDY,
            ]);
            return;
        }

        $companyName = $lead->company ?? 'your company';
        $subject = "RE: {$companyName} — Bạn đã xem website mẫu cho ngành của bạn chưa?";

        $body = $this->buildFollowUpEmailBody($lead);

        $this->sendOutboundEmail($campaign, $lead, $subject, $body, 'follow_up');

        // Schedule next: case study after 3 more days (5 days total)
        $campaign->update([
            'last_action_at' => now(),
            'next_action_at' => now()->addDays(3),
            'current_step' => OutboundCampaign::STEP_CASE_STUDY,
        ]);
    }

    private function sendCaseStudyEmail(OutboundCampaign $campaign, Lead $lead): void
    {
        // If replied, stop
        if ($campaign->replied) {
            $this->completeCampaign($campaign);
            return;
        }

        $companyName = $lead->company ?? 'your company';
        $subject = "[Case Study] Công ty XYZ tăng 300% lead từ website mới — Áp dụng cho {$companyName}";

        $body = $this->buildCaseStudyEmailBody($lead);

        $this->sendOutboundEmail($campaign, $lead, $subject, $body, 'case_study');

        // Schedule final offer: 2 more days (7 days total)
        $campaign->update([
            'last_action_at' => now(),
            'next_action_at' => now()->addDays(2),
            'current_step' => OutboundCampaign::STEP_FINAL_OFFER,
        ]);
    }

    private function sendFinalOfferEmail(OutboundCampaign $campaign, Lead $lead): void
    {
        if ($campaign->replied) {
            $this->completeCampaign($campaign);
            return;
        }

        $companyName = $lead->company ?? 'your company';
        $subject = "🎯 Ưu đãi cuối cho {$companyName}: Thiết kế mockup website MIỄN PHÍ trong 48h";

        $body = $this->buildFinalOfferEmailBody($lead);

        $this->sendOutboundEmail($campaign, $lead, $subject, $body, 'final_offer');

        // Campaign complete
        $this->completeCampaign($campaign);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // EMAIL SENDING
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    private function sendOutboundEmail(
        OutboundCampaign $campaign,
        Lead $lead,
        string $subject,
        string $bodyHtml,
        string $stepName
    ): void {
        try {
            // Use existing email service infrastructure
            $this->emailService->sendEmail(
                email: $lead->email,
                name: $lead->name,
                contactType: 'lead',
                contactId: $lead->id,
                subject: $subject,
                bodyHtml: $bodyHtml,
                variables: [
                    'company' => $lead->company ?? '',
                    'name' => $lead->name ?? '',
                ],
            );

            // Log the action
            $this->logAction($campaign, $lead, OutboundCampaignLog::ACTION_EMAIL_SENT, $stepName, 'email', $subject, Str::limit(strip_tags($bodyHtml), 200));

            // Also create CRM activity
            Activity::create([
                'account_id' => $lead->account_id,
                'subject_type' => Lead::class,
                'subject_id' => $lead->id,
                'type' => Activity::TYPE_EMAIL,
                'title' => "Outbound: {$stepName} email sent",
                'description' => "Subject: {$subject}",
                'date' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Outbound email failed', [
                'campaign_id' => $campaign->id,
                'lead_id' => $lead->id,
                'step' => $stepName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendZaloMessage(OutboundCampaign $campaign, Lead $lead): void
    {
        try {
            $message = $this->buildZaloMessage($lead);
            $this->zaloService->sendMessage($lead->phone, $message);

            $this->logAction(
                $campaign, $lead,
                OutboundCampaignLog::ACTION_ZALO_SENT,
                'intro', 'zalo',
                null, $message
            );

            Activity::create([
                'account_id' => $lead->account_id,
                'subject_type' => Lead::class,
                'subject_id' => $lead->id,
                'type' => Activity::TYPE_CALL,
                'title' => 'Outbound: Zalo message sent',
                'description' => $message,
                'date' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Zalo message failed', [
                'campaign_id' => $campaign->id,
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // SCORING
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function recordEmailOpened(OutboundCampaign $campaign): void
    {
        if ($campaign->email_opened) return;

        $campaign->update(['email_opened' => true]);
        $campaign->addScore('email_opened', OutboundCampaign::SCORE_EMAIL_OPENED);

        $this->logAction($campaign, $campaign->lead, OutboundCampaignLog::ACTION_EMAIL_OPENED, null, 'email');
        $this->logAction($campaign, $campaign->lead, OutboundCampaignLog::ACTION_SCORE_UPDATED, null, null, null, null, [
            'reason' => 'email_opened',
            'points' => OutboundCampaign::SCORE_EMAIL_OPENED,
            'total' => $campaign->fresh()->lead_score,
        ]);
    }

    public function recordLinkClicked(OutboundCampaign $campaign): void
    {
        if ($campaign->link_clicked) return;

        $campaign->update(['link_clicked' => true]);
        $campaign->addScore('link_clicked', OutboundCampaign::SCORE_LINK_CLICKED);

        $this->logAction($campaign, $campaign->lead, OutboundCampaignLog::ACTION_LINK_CLICKED, null, 'email');
        $this->logAction($campaign, $campaign->lead, OutboundCampaignLog::ACTION_SCORE_UPDATED, null, null, null, null, [
            'reason' => 'link_clicked',
            'points' => OutboundCampaign::SCORE_LINK_CLICKED,
            'total' => $campaign->fresh()->lead_score,
        ]);
    }

    public function recordReplied(OutboundCampaign $campaign): void
    {
        if ($campaign->replied) return;

        $campaign->update(['replied' => true]);
        $campaign->addScore('replied', OutboundCampaign::SCORE_REPLIED);

        $this->logAction($campaign, $campaign->lead, OutboundCampaignLog::ACTION_REPLIED, null, 'email');

        // Auto-complete campaign on reply
        $this->completeCampaign($campaign);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // HELPERS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    private function completeCampaign(OutboundCampaign $campaign): void
    {
        $campaign->update([
            'status' => OutboundCampaign::STATUS_COMPLETED,
            'completed_at' => now(),
            'next_action_at' => null,
        ]);

        $this->logAction($campaign, $campaign->lead, OutboundCampaignLog::ACTION_STATUS_CHANGED, null, null, null, null, [
            'new_status' => 'completed',
        ]);
    }

    private function logAction(
        OutboundCampaign $campaign,
        ?Lead $lead,
        string $action,
        ?string $stepName = null,
        ?string $channel = null,
        ?string $subject = null,
        ?string $contentPreview = null,
        ?array $metadata = null,
    ): void {
        OutboundCampaignLog::create([
            'outbound_campaign_id' => $campaign->id,
            'lead_id' => $lead?->id ?? $campaign->lead_id,
            'action' => $action,
            'step_name' => $stepName,
            'channel' => $channel,
            'subject' => $subject,
            'content_preview' => $contentPreview,
            'metadata' => $metadata,
        ]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // EMAIL TEMPLATES (B2B tone)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    private function buildIntroEmailBody(Lead $lead): string
    {
        $company = $lead->company ?? 'quý công ty';
        $name = $lead->name ?? 'Anh/Chị';
        $portfolioUrl = config('app.url') . '/portfolio';
        $demoUrl = config('app.url') . '/request-demo';

        return <<<HTML
        <div style="font-family: 'Segoe UI', Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1e293b;">
            <div style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 32px; border-radius: 12px 12px 0 0;">
                <h1 style="color: white; margin: 0; font-size: 22px;">Chào {$name} 👋</h1>
                <p style="color: rgba(255,255,255,0.85); margin: 8px 0 0; font-size: 14px;">Website chuyên nghiệp cho {$company}</p>
            </div>
            <div style="padding: 28px 32px; background: white; border: 1px solid #e2e8f0; border-top: none;">
                <p style="font-size: 14px; line-height: 1.7;">
                    Tôi là <strong>đội ngũ BED Creative</strong> — chuyên thiết kế website cho doanh nghiệp xuất khẩu, sản xuất và thương mại B2B.
                </p>
                <p style="font-size: 14px; line-height: 1.7;">
                    Chúng tôi nhận thấy <strong>{$company}</strong> có tiềm năng rất lớn để mở rộng khách hàng quốc tế qua một website chuyên nghiệp. Đây là những gì chúng tôi có thể giúp:
                </p>
                <div style="background: #f8fafc; border-radius: 8px; padding: 20px; margin: 16px 0;">
                    <div style="display: flex; align-items: center; margin-bottom: 12px;">
                        <span style="background: #6366f1; color: white; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; margin-right: 10px;">✓</span>
                        <strong style="font-size: 13px;">Website thiết kế riêng</strong> — không dùng template rập khuôn
                    </div>
                    <div style="display: flex; align-items: center; margin-bottom: 12px;">
                        <span style="background: #10b981; color: white; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; margin-right: 10px;">✓</span>
                        <strong style="font-size: 13px;">SEO tối ưu</strong> — lên top Google cho từ khóa ngành
                    </div>
                    <div style="display: flex; align-items: center;">
                        <span style="background: #f59e0b; color: white; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; margin-right: 10px;">✓</span>
                        <strong style="font-size: 13px;">Đa ngôn ngữ</strong> — EN/VI/CN/JP sẵn sàng cho thị trường quốc tế
                    </div>
                </div>
                <p style="font-size: 14px; line-height: 1.7;">
                    👉 <a href="{$portfolioUrl}" style="color: #6366f1; font-weight: 600;">Xem portfolio của chúng tôi</a>
                </p>
                <div style="text-align: center; margin: 24px 0;">
                    <a href="{$demoUrl}" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-block;">
                        🎨 Yêu cầu mockup miễn phí
                    </a>
                </div>
                <p style="font-size: 13px; color: #64748b; line-height: 1.6;">
                    Nếu bạn muốn trao đổi nhanh, hãy reply email này hoặc gọi cho tôi qua Zalo.
                </p>
            </div>
            <div style="padding: 16px 32px; background: #f8fafc; border-radius: 0 0 12px 12px; border: 1px solid #e2e8f0; border-top: none; text-align: center;">
                <p style="font-size: 12px; color: #94a3b8; margin: 0;">BED Creative — Website & Digital Solutions for B2B</p>
            </div>
        </div>
        HTML;
    }

    private function buildFollowUpEmailBody(Lead $lead): string
    {
        $company = $lead->company ?? 'quý công ty';
        $name = $lead->name ?? 'Anh/Chị';
        $portfolioUrl = config('app.url') . '/portfolio';

        return <<<HTML
        <div style="font-family: 'Segoe UI', Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1e293b;">
            <div style="padding: 28px 32px; background: white; border: 1px solid #e2e8f0; border-radius: 12px;">
                <p style="font-size: 14px; line-height: 1.7;">Chào {$name},</p>
                <p style="font-size: 14px; line-height: 1.7;">
                    Tôi đã gửi email trước đó về giải pháp website cho <strong>{$company}</strong>. Có thể bạn bận chưa kịp xem — hoàn toàn hiểu!
                </p>
                <p style="font-size: 14px; line-height: 1.7;">
                    Tôi chỉ muốn chia sẻ nhanh: chúng tôi vừa hoàn thành một dự án cho công ty xuất khẩu tương tự, và kết quả khá ấn tượng:
                </p>
                <div style="background: linear-gradient(135deg, #eff6ff, #eef2ff); border-radius: 10px; padding: 20px; margin: 16px 0; border-left: 4px solid #6366f1;">
                    <p style="margin: 0 0 8px; font-size: 14px;"><strong>📈 +180% traffic quốc tế</strong> sau 3 tháng</p>
                    <p style="margin: 0 0 8px; font-size: 14px;"><strong>📧 +45 leads/tháng</strong> từ form liên hệ</p>
                    <p style="margin: 0; font-size: 14px;"><strong>🌐 4 ngôn ngữ</strong> phục vụ thị trường EU + Asia</p>
                </div>
                <p style="font-size: 14px; line-height: 1.7;">
                    👉 <a href="{$portfolioUrl}" style="color: #6366f1; font-weight: 600;">Xem mẫu website ngành của bạn</a>
                </p>
                <p style="font-size: 13px; color: #64748b;">Reply nhanh "Quan tâm" — tôi sẽ gửi mockup riêng cho {$company} trong 24h.</p>
            </div>
        </div>
        HTML;
    }

    private function buildCaseStudyEmailBody(Lead $lead): string
    {
        $company = $lead->company ?? 'quý công ty';
        $name = $lead->name ?? 'Anh/Chị';
        $caseStudyUrl = config('app.url') . '/case-studies';

        return <<<HTML
        <div style="font-family: 'Segoe UI', Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1e293b;">
            <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); padding: 28px 32px; border-radius: 12px 12px 0 0;">
                <p style="color: rgba(255,255,255,0.7); margin: 0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Case Study</p>
                <h2 style="color: white; margin: 6px 0 0; font-size: 18px;">Công ty XYZ tăng 300% lead nhờ website mới</h2>
            </div>
            <div style="padding: 28px 32px; background: white; border: 1px solid #e2e8f0; border-top: none;">
                <p style="font-size: 14px; line-height: 1.7;">Chào {$name},</p>
                <p style="font-size: 14px; line-height: 1.7;">
                    Tôi muốn chia sẻ câu chuyện của <strong>Công ty XYZ</strong> — cũng trong ngành tương tự <strong>{$company}</strong>.
                </p>
                <div style="border: 1px solid #d1fae5; border-radius: 10px; padding: 20px; margin: 16px 0;">
                    <h4 style="color: #059669; margin: 0 0 12px; font-size: 15px;">🏢 Trước khi làm website mới:</h4>
                    <ul style="margin: 0; padding-left: 18px; font-size: 13px; color: #475569; line-height: 1.8;">
                        <li>Website cũ từ 2018, không mobile-friendly</li>
                        <li>0 leads từ online</li>
                        <li>Chỉ có khách hàng qua giới thiệu</li>
                    </ul>
                    <h4 style="color: #059669; margin: 16px 0 12px; font-size: 15px;">🚀 Sau 3 tháng với BED Creative:</h4>
                    <ul style="margin: 0; padding-left: 18px; font-size: 13px; color: #475569; line-height: 1.8;">
                        <li><strong>300% tăng lead</strong> từ website</li>
                        <li><strong>Top 5 Google</strong> cho 15 keywords chính</li>
                        <li><strong>4 thị trường mới</strong> nhờ nội dung đa ngôn ngữ</li>
                    </ul>
                </div>
                <p style="font-size: 14px; line-height: 1.7;">
                    👉 <a href="{$caseStudyUrl}" style="color: #059669; font-weight: 600;">Đọc full case study tại đây</a>
                </p>
                <p style="font-size: 14px; line-height: 1.7;">
                    Tôi tin rằng <strong>{$company}</strong> có thể đạt kết quả tương tự. Bạn có muốn trao đổi 15 phút để tôi phân tích cụ thể?
                </p>
            </div>
            <div style="padding: 16px 32px; background: #f8fafc; border-radius: 0 0 12px 12px; border: 1px solid #e2e8f0; border-top: none; text-align: center;">
                <p style="font-size: 12px; color: #94a3b8; margin: 0;">BED Creative — Website & Digital Solutions for B2B</p>
            </div>
        </div>
        HTML;
    }

    private function buildFinalOfferEmailBody(Lead $lead): string
    {
        $company = $lead->company ?? 'quý công ty';
        $name = $lead->name ?? 'Anh/Chị';
        $demoUrl = config('app.url') . '/request-demo';

        return <<<HTML
        <div style="font-family: 'Segoe UI', Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1e293b;">
            <div style="background: linear-gradient(135deg, #ef6820 0%, #e04f0f 100%); padding: 28px 32px; border-radius: 12px 12px 0 0;">
                <p style="color: rgba(255,255,255,0.7); margin: 0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Ưu đãi đặc biệt</p>
                <h2 style="color: white; margin: 6px 0 0; font-size: 18px;">🎯 Mockup website MIỄN PHÍ cho {$company}</h2>
            </div>
            <div style="padding: 28px 32px; background: white; border: 1px solid #e2e8f0; border-top: none;">
                <p style="font-size: 14px; line-height: 1.7;">Chào {$name},</p>
                <p style="font-size: 14px; line-height: 1.7;">
                    Đây là email cuối cùng từ tôi về chủ đề này. Tôi hiểu bạn có thể đang bận, nhưng trước khi kết thúc, tôi muốn đề xuất một thứ:
                </p>
                <div style="background: linear-gradient(135deg, #fef5f0, #fde8dc); border-radius: 12px; padding: 24px; margin: 20px 0; border: 1px solid #fbd0b8; text-align: center;">
                    <h3 style="color: #e04f0f; margin: 0 0 12px; font-size: 18px;">🎨 Mockup Website MIỄN PHÍ</h3>
                    <p style="font-size: 14px; margin: 0 0 8px; color: #475569;">Chúng tôi sẽ thiết kế 1 mockup trang chủ <strong>hoàn toàn miễn phí</strong> cho {$company}</p>
                    <p style="font-size: 13px; margin: 0; color: #64748b;">Không cam kết • Không phí ẩn • Giao trong 48h</p>
                </div>
                <div style="text-align: center; margin: 24px 0;">
                    <a href="{$demoUrl}" style="background: linear-gradient(135deg, #ef6820, #e04f0f); color: white; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 15px; display: inline-block;">
                        Nhận mockup miễn phí ngay →
                    </a>
                </div>
                <p style="font-size: 13px; color: #94a3b8; text-align: center;">
                    Ưu đãi có hiệu lực trong 7 ngày.
                </p>
            </div>
            <div style="padding: 16px 32px; background: #f8fafc; border-radius: 0 0 12px 12px; border: 1px solid #e2e8f0; border-top: none; text-align: center;">
                <p style="font-size: 12px; color: #94a3b8; margin: 0;">BED Creative — Website & Digital Solutions for B2B</p>
            </div>
        </div>
        HTML;
    }

    private function buildZaloMessage(Lead $lead): string
    {
        $company = $lead->company ?? 'quý công ty';
        $name = $lead->name ?? 'Anh/Chị';
        $landingUrl = config('app.url') . '/portfolio';

        return "Chào {$name}! 👋\n\n"
            . "Mình từ BED Creative — chuyên thiết kế website cho doanh nghiệp B2B.\n\n"
            . "Mình vừa gửi email với thông tin chi tiết về giải pháp website cho {$company}.\n\n"
            . "👉 Xem portfolio: {$landingUrl}\n\n"
            . "Nếu bạn quan tâm, mình có thể gửi mockup miễn phí ngay. Reply \"OK\" nha!";
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // DASHBOARD DATA
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function getDashboardStats(int $accountId): array
    {
        $campaigns = OutboundCampaign::where('account_id', $accountId);

        return [
            'total' => (clone $campaigns)->count(),
            'active' => (clone $campaigns)->where('status', OutboundCampaign::STATUS_ACTIVE)->count(),
            'completed' => (clone $campaigns)->where('status', OutboundCampaign::STATUS_COMPLETED)->count(),
            'by_status' => [
                'new' => (clone $campaigns)->where('email_opened', false)->where('link_clicked', false)->where('replied', false)->count(),
                'contacted' => (clone $campaigns)->where('current_step', '>', 0)->where('email_opened', false)->count(),
                'engaged' => (clone $campaigns)->where(function ($q) {
                    $q->where('email_opened', true)->orWhere('link_clicked', true);
                })->where('replied', false)->count(),
                'qualified' => (clone $campaigns)->where('replied', true)->count(),
            ],
            'avg_score' => round((clone $campaigns)->avg('lead_score') ?? 0, 1),
            'total_emails_sent' => OutboundCampaignLog::whereIn('outbound_campaign_id',
                (clone $campaigns)->pluck('id')
            )->where('action', OutboundCampaignLog::ACTION_EMAIL_SENT)->count(),
            'total_zalo_sent' => OutboundCampaignLog::whereIn('outbound_campaign_id',
                (clone $campaigns)->pluck('id')
            )->where('action', OutboundCampaignLog::ACTION_ZALO_SENT)->count(),
            'email_open_rate' => $this->calculateRate(
                (clone $campaigns)->where('email_opened', true)->count(),
                (clone $campaigns)->count()
            ),
            'reply_rate' => $this->calculateRate(
                (clone $campaigns)->where('replied', true)->count(),
                (clone $campaigns)->count()
            ),
        ];
    }

    private function calculateRate(int $numerator, int $denominator): float
    {
        if ($denominator <= 0) return 0;
        return round(($numerator / $denominator) * 100, 1);
    }
}
