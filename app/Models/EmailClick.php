<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailClick extends Model
{
    protected $fillable = [
        'email_send_id',
        'url',
        'ip_address',
        'user_agent',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public function emailSend(): BelongsTo
    {
        return $this->belongsTo(EmailSend::class);
    }
}
