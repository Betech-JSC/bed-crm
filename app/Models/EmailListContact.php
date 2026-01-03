<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmailListContact extends Model
{
    protected $fillable = [
        'email_list_id',
        'contact_type',
        'contact_id',
        'email',
        'name',
        'subscribed_at',
        'unsubscribed_at',
        'unsubscribe_reason',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    /**
     * Get the contact (polymorphic)
     */
    public function contact(): MorphTo
    {
        return $this->morphTo('contact', 'contact_type', 'contact_id');
    }

    /**
     * Check if contact is subscribed
     */
    public function isSubscribed(): bool
    {
        return is_null($this->unsubscribed_at);
    }
}
