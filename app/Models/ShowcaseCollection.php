<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShowcaseCollection extends Model
{
    protected $guarded = ['id'];

    public function items(): HasMany { return $this->hasMany(ShowcaseItem::class, 'collection_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }

    public function scopeForAccount($q, $accountId) { return $q->where('account_id', $accountId); }
}
