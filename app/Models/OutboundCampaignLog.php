<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundCampaignLog extends Model
{
    // Action constants
    public const ACTION_EMAIL_SENT = 'email_sent';
    public const ACTION_ZALO_SENT = 'zalo_sent';
    public const ACTION_EMAIL_OPENED = 'email_opened';
    public const ACTION_LINK_CLICKED = 'link_clicked';
    public const ACTION_REPLIED = 'replied';
    public const ACTION_SCORE_UPDATED = 'score_updated';
    public const ACTION_STATUS_CHANGED = 'status_changed';

    // Step names
    public const STEP_INTRO = 'intro';
    public const STEP_FOLLOW_UP = 'follow_up';
    public const STEP_CASE_STUDY = 'case_study';
    public const STEP_FINAL_OFFER = 'final_offer';

    protected $fillable = [
        'outbound_campaign_id', 'lead_id',
        'action', 'step_name', 'channel',
        'subject', 'content_preview', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(OutboundCampaign::class, 'outbound_campaign_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    // --- Helpers ---

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_EMAIL_SENT => 'Email Sent',
            self::ACTION_ZALO_SENT => 'Zalo Message Sent',
            self::ACTION_EMAIL_OPENED => 'Email Opened',
            self::ACTION_LINK_CLICKED => 'Link Clicked',
            self::ACTION_REPLIED => 'Replied',
            self::ACTION_SCORE_UPDATED => 'Score Updated',
            self::ACTION_STATUS_CHANGED => 'Status Changed',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_EMAIL_SENT => 'pi-envelope',
            self::ACTION_ZALO_SENT => 'pi-comments',
            self::ACTION_EMAIL_OPENED => 'pi-eye',
            self::ACTION_LINK_CLICKED => 'pi-external-link',
            self::ACTION_REPLIED => 'pi-reply',
            self::ACTION_SCORE_UPDATED => 'pi-chart-line',
            self::ACTION_STATUS_CHANGED => 'pi-sync',
            default => 'pi-circle',
        };
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_EMAIL_SENT => '#3b82f6',
            self::ACTION_ZALO_SENT => '#0068ff',
            self::ACTION_EMAIL_OPENED => '#10b981',
            self::ACTION_LINK_CLICKED => '#f59e0b',
            self::ACTION_REPLIED => '#8b5cf6',
            self::ACTION_SCORE_UPDATED => '#06b6d4',
            self::ACTION_STATUS_CHANGED => '#64748b',
            default => '#94a3b8',
        };
    }
}
