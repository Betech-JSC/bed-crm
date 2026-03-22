<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmGuide extends Model
{
    protected $fillable = [
        'account_id', 'title', 'category', 'icon', 'summary',
        'content', 'is_published', 'sort_order', 'created_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopePublished($q) { return $q->where('is_published', true); }
}
