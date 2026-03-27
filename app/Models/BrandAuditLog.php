<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandAuditLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'changes' => 'array',
    ];

    public function guideline(): BelongsTo { return $this->belongsTo(BrandGuideline::class, 'brand_guideline_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public static function log(int $guidelineId, string $action, string $section, ?array $changes = null): self
    {
        return self::create([
            'brand_guideline_id' => $guidelineId,
            'user_id' => auth()->id(),
            'action' => $action,
            'section' => $section,
            'changes' => $changes,
        ]);
    }
}
