<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShowcaseItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'analysis' => 'array',
        'is_own_project' => 'boolean',
    ];

    public function collection(): BelongsTo { return $this->belongsTo(ShowcaseCollection::class, 'collection_id'); }
}
