<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmbReview extends Model
{
    protected $fillable = [
        'gmb_location_id', 'google_review_id', 'reviewer_name', 'reviewer_photo_url',
        'rating', 'comment', 'reply', 'replied_at', 'review_time',
    ];

    protected $casts = ['replied_at' => 'datetime', 'review_time' => 'datetime'];

    public function location(): BelongsTo { return $this->belongsTo(GmbLocation::class, 'gmb_location_id'); }
}
