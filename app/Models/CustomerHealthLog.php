<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerHealthLog extends Model
{
    protected $fillable = ['customer_id', 'score', 'factors', 'trigger'];

    protected $casts = ['factors' => 'array'];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
}
