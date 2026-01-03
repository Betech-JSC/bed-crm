<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailOpen extends Model
{
    protected $fillable = [
        'email_send_id',
        'ip_address',
        'user_agent',
        'opened_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
    ];

    public function emailSend(): BelongsTo
    {
        return $this->belongsTo(EmailSend::class);
    }
}
