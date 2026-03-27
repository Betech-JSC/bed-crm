<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentConversation extends Model
{
    protected $fillable = [
        'agent_id', 'user_id', 'title', 'context', 'messages',
        'message_count', 'satisfaction_rating', 'feedback', 'tokens_used',
    ];

    protected $casts = [
        'context' => 'array',
        'messages' => 'array',
    ];

    public function agent(): BelongsTo { return $this->belongsTo(AiAgent::class, 'agent_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function addMessage(string $role, string $content, array $sources = []): void
    {
        $messages = $this->messages ?? [];
        $messages[] = [
            'role' => $role,
            'content' => $content,
            'sources' => $sources,
            'timestamp' => now()->toISOString(),
        ];
        $this->update([
            'messages' => $messages,
            'message_count' => count($messages),
        ]);
    }
}
