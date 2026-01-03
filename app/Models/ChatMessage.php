<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'account_id',
        'conversation_id',
        'role',
        'content',
        'ai_metadata',
        'tokens_used',
        'cost',
        'response_time_ms',
        'status',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'ai_metadata' => 'array',
        'tokens_used' => 'integer',
        'cost' => 'decimal:6',
        'response_time_ms' => 'integer',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class);
    }

    /**
     * Check if message is from user
     */
    public function isUserMessage(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if message is from assistant
     */
    public function isAssistantMessage(): bool
    {
        return $this->role === 'assistant';
    }
}
