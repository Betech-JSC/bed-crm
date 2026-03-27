<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandAsset extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
    ];

    const CATEGORIES = [
        'logo' => 'Logo',
        'icon' => 'Icon',
        'pattern' => 'Pattern',
        'template' => 'Template',
        'photo' => 'Photo',
        'illustration' => 'Illustration',
        'document' => 'Document',
    ];

    public function guideline(): BelongsTo { return $this->belongsTo(BrandGuideline::class, 'brand_guideline_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
