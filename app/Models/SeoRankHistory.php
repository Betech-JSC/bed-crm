<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoRankHistory extends Model
{
    protected $table = 'seo_rank_history';

    protected $fillable = ['seo_keyword_id', 'rank', 'checked_date'];

    protected $casts = ['checked_date' => 'date'];

    public function keyword(): BelongsTo { return $this->belongsTo(SeoKeyword::class, 'seo_keyword_id'); }
}
