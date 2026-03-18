<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailCampaign extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_REGULAR = 'regular';
    public const TYPE_AB_TEST = 'ab_test';
    public const TYPE_RSS = 'rss';
    public const TYPE_AUTOMATED = 'automated';

    protected $fillable = [
        'account_id', 'name', 'description', 'email_template_id',
        'subject', 'preview_text', 'body_html', 'body_text',
        'from_name', 'from_email', 'reply_to',
        'email_list_id', 'email_segment_id',
        'status', 'campaign_type', 'ab_test_config', 'winning_variant_id',
        'send_config', 'scheduled_at', 'sent_at',
        'total_recipients', 'sent_count', 'delivered_count',
        'opened_count', 'clicked_count', 'bounced_count',
        'unsubscribed_count', 'spam_reports',
        'open_rate', 'click_rate',
        'revenue_generated', 'conversions_count', 'revenue_per_email',
        'tags', 'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'delivered_count' => 'integer',
        'opened_count' => 'integer',
        'clicked_count' => 'integer',
        'bounced_count' => 'integer',
        'unsubscribed_count' => 'integer',
        'spam_reports' => 'integer',
        'conversions_count' => 'integer',
        'open_rate' => 'decimal:2',
        'click_rate' => 'decimal:2',
        'revenue_generated' => 'decimal:2',
        'revenue_per_email' => 'decimal:2',
        'ab_test_config' => 'array',
        'send_config' => 'array',
        'tags' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(EmailSegment::class, 'email_segment_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(EmailCampaignVariant::class);
    }

    public function sends(): HasMany
    {
        return $this->hasMany(EmailSend::class, 'sendable_id')
            ->where('sendable_type', 'campaign');
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(EmailRevenueAttribution::class);
    }

    /**
     * Check if this is an A/B test campaign
     */
    public function isAbTest(): bool
    {
        return $this->campaign_type === self::TYPE_AB_TEST;
    }

    /**
     * Update campaign statistics
     */
    public function updateStatistics(): void
    {
        $this->sent_count = $this->sends()->whereIn('status', ['sent', 'delivered'])->count();
        $this->delivered_count = $this->sends()->where('status', 'delivered')->count();
        $this->opened_count = $this->sends()->whereNotNull('opened_at')->count();
        $this->clicked_count = $this->sends()->whereNotNull('clicked_at')->count();
        $this->bounced_count = $this->sends()->where('status', 'bounced')->count();
        $this->unsubscribed_count = $this->sends()->whereNotNull('unsubscribed_at')->count();

        if ($this->delivered_count > 0) {
            $this->open_rate = ($this->opened_count / $this->delivered_count) * 100;
            $this->click_rate = ($this->clicked_count / $this->delivered_count) * 100;
        }

        if ($this->sent_count > 0 && $this->revenue_generated > 0) {
            $this->revenue_per_email = $this->revenue_generated / $this->sent_count;
        }

        $this->save();

        // Update variant stats if A/B test
        if ($this->isAbTest()) {
            $this->variants->each(fn ($v) => $v->updateStatistics());
        }
    }

    /**
     * Declare A/B test winner
     */
    public function declareWinner(string $variantId): void
    {
        $this->variants()->update(['is_winner' => false]);
        $this->variants()->where('variant_id', $variantId)->update(['is_winner' => true]);
        $this->update(['winning_variant_id' => $variantId]);
    }
}
