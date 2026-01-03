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

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'email_template_id',
        'subject',
        'body_html',
        'body_text',
        'email_list_id',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'bounced_count',
        'unsubscribed_count',
        'open_rate',
        'click_rate',
        'created_by',
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
        'open_rate' => 'decimal:2',
        'click_rate' => 'decimal:2',
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

    public function sends(): HasMany
    {
        return $this->hasMany(EmailSend::class, 'sendable_id')
            ->where('sendable_type', 'campaign');
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

        $this->save();
    }
}
