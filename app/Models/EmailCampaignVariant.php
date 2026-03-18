<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCampaignVariant extends Model
{
    protected $fillable = [
        'email_campaign_id', 'variant_id', 'subject', 'body_html',
        'from_name', 'preview_text', 'recipients_count', 'sent_count',
        'delivered_count', 'opened_count', 'clicked_count', 'bounced_count',
        'open_rate', 'click_rate', 'revenue', 'is_winner',
    ];

    protected $casts = [
        'is_winner' => 'boolean',
        'open_rate' => 'decimal:2',
        'click_rate' => 'decimal:2',
        'revenue' => 'decimal:2',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'email_campaign_id');
    }

    public function updateStatistics(): void
    {
        $sends = EmailSend::where('sendable_type', 'campaign')
            ->where('sendable_id', $this->email_campaign_id)
            ->where('variant_id', $this->variant_id);

        $this->sent_count = (clone $sends)->whereIn('status', ['sent', 'delivered'])->count();
        $this->delivered_count = (clone $sends)->where('status', 'delivered')->count();
        $this->opened_count = (clone $sends)->whereNotNull('opened_at')->count();
        $this->clicked_count = (clone $sends)->whereNotNull('clicked_at')->count();
        $this->bounced_count = (clone $sends)->where('status', 'bounced')->count();

        if ($this->delivered_count > 0) {
            $this->open_rate = ($this->opened_count / $this->delivered_count) * 100;
            $this->click_rate = ($this->clicked_count / $this->delivered_count) * 100;
        }

        $this->save();
    }
}
