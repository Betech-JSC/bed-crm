<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSegmentContact extends Model
{
    protected $fillable = [
        'email_segment_id', 'contact_type', 'contact_id',
        'email', 'source', 'tags', 'subscribed_at', 'unsubscribed_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function segment(): BelongsTo
    {
        return $this->belongsTo(EmailSegment::class, 'email_segment_id');
    }

    public function contact()
    {
        return $this->morphTo('contact', 'contact_type', 'contact_id');
    }
}
