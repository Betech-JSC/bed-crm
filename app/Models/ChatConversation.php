<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatConversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'widget_id',
        'visitor_id',
        'session_id',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'visitor_ip',
        'user_agent',
        'referrer_url',
        'page_url',
        'lead_id',
        'contact_id',
        'status',
        'last_message_at',
        'message_count',
        'metadata',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'message_count' => 'integer',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(ChatWidget::class, 'widget_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get conversation summary for AI context
     */
    public function getContextSummary(int $maxMessages = 10): array
    {
        $messages = $this->messages()
            ->where('role', '!=', 'system')
            ->latest()
            ->limit($maxMessages)
            ->get()
            ->reverse()
            ->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();

        return [
            'visitor_name' => $this->visitor_name,
            'visitor_email' => $this->visitor_email,
            'visitor_phone' => $this->visitor_phone,
            'messages' => $messages,
        ];
    }

    /**
     * Mark conversation as closed
     */
    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }
}
