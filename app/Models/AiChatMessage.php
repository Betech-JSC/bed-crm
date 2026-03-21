<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatMessage extends Model
{
    protected $table = 'ai_chat_messages';

    protected $fillable = [
        'conversation_id', 'role', 'content',
        'tokens_used', 'provider', 'model', 'metadata',
        'tool_calls', 'tool_results',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tokens_used' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiChatConversation::class, 'conversation_id');
    }
}
