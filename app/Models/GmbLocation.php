<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GmbLocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'location_name', 'google_location_id', 'place_id',
        'address', 'phone', 'website', 'category', 'business_hours', 'attributes',
        'total_views', 'total_searches', 'total_actions', 'avg_rating', 'review_count',
        'status', 'last_synced_at',
    ];

    protected $casts = [
        'business_hours' => 'array',
        'attributes' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function reviews(): HasMany { return $this->hasMany(GmbReview::class); }
    public function posts(): HasMany { return $this->hasMany(GmbPost::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
