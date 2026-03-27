<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiGeneratedContent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'created_by', 'template_id', 'title',
        'content_type', 'input_data', 'generated_content',
        'seo_suggestions', 'status',
    ];

    protected $casts = [
        'input_data' => 'array',
        'seo_suggestions' => 'array',
    ];

    public function template(): BelongsTo { return $this->belongsTo(AiContentTemplate::class, 'template_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
