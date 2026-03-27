<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    protected $fillable = [
        'chatbot_flow_id', 'session_id', 'sender', 'node_id',
        'message', 'options_shown', 'option_selected', 'data_collected',
    ];

    protected $casts = [
        'options_shown' => 'array',
        'data_collected' => 'array',
    ];

    public function flow(): BelongsTo { return $this->belongsTo(ChatbotFlow::class, 'chatbot_flow_id'); }
}
