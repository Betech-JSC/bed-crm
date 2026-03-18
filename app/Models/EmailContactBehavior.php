<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailContactBehavior extends Model
{
    protected $fillable = [
        'account_id', 'contact_type', 'contact_id', 'email',
        'event_type', 'event_source', 'event_data',
        'ip_address', 'user_agent', 'device_type', 'occurred_at',
    ];

    protected $casts = [
        'event_data' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
