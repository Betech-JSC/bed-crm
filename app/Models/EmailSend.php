<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmailSend extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_BOUNCED = 'bounced';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'account_id',
        'sendable_type',
        'sendable_id',
        'email_template_id',
        'contact_type',
        'contact_id',
        'email',
        'name',
        'subject',
        'body_html',
        'body_text',
        'status',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'open_count',
        'click_count',
        'bounce_type',
        'bounce_reason',
        'message_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'open_count' => 'integer',
        'click_count' => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    /**
     * Get the sendable (campaign or automation)
     */
    public function sendable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the contact (polymorphic)
     */
    public function contact(): MorphTo
    {
        return $this->morphTo('contact', 'contact_type', 'contact_id');
    }

    public function opens(): HasMany
    {
        return $this->hasMany(EmailOpen::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(EmailClick::class);
    }

    /**
     * Generate unique message ID for tracking
     */
    public function generateMessageId(): string
    {
        return 'email_' . $this->id . '_' . md5($this->email . $this->created_at) . '@' . parse_url(config('app.url'), PHP_URL_HOST);
    }
}
