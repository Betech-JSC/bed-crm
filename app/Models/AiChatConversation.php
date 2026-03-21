<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiChatConversation extends Model
{
    protected $table = 'ai_chat_conversations';

    protected $fillable = [
        'account_id', 'user_id', 'title', 'provider', 'model',
        'system_prompt', 'message_count', 'total_tokens',
        'is_pinned', 'last_message_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'message_count' => 'integer',
        'total_tokens' => 'integer',
        'last_message_at' => 'datetime',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
