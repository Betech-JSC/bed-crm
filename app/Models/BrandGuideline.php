<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandGuideline extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'brand_values' => 'array',
        'brand_personality' => 'array',
        'brand_positioning' => 'array',
        'value_propositions' => 'array',
        'primary_colors' => 'array',
        'secondary_colors' => 'array',
        'neutral_colors' => 'array',
        'font_config' => 'array',
        'logo_guidelines' => 'array',
        'voice_traits' => 'array',
        'tone_variations' => 'array',
        'writing_guidelines' => 'array',
        'published_at' => 'datetime',
    ];

    /* ── Status ── */
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';

    /* ── Relationships ── */
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function assets(): HasMany { return $this->hasMany(BrandAsset::class); }
    public function auditLogs(): HasMany { return $this->hasMany(BrandAuditLog::class); }

    /* ── Accessors ── */
    public function getCompletionScoreAttribute(): int
    {
        $sections = [
            'foundation' => !empty($this->brand_purpose) && !empty($this->brand_vision) && !empty($this->brand_mission),
            'values' => !empty($this->brand_values) && count($this->brand_values) >= 2,
            'personality' => !empty($this->brand_personality) && count($this->brand_personality) >= 3,
            'colors' => !empty($this->primary_colors) && count($this->primary_colors) >= 1,
            'typography' => !empty($this->font_primary),
            'logo' => !empty($this->logo_primary),
            'voice' => !empty($this->voice_traits) && count($this->voice_traits) >= 2,
            'positioning' => !empty($this->brand_positioning),
        ];
        $filled = count(array_filter($sections));
        return (int) round(($filled / count($sections)) * 100);
    }

    public function getSectionStatusAttribute(): array
    {
        return [
            'foundation' => !empty($this->brand_purpose) || !empty($this->brand_vision),
            'visual' => !empty($this->primary_colors) || !empty($this->font_primary) || !empty($this->logo_primary),
            'voice' => !empty($this->voice_traits),
            'assets' => $this->assets()->count() > 0,
        ];
    }

    /* ── Scopes ── */
    public function scopeForAccount($q, $accountId) { return $q->where('account_id', $accountId); }
    public function scopeActive($q) { return $q->where('status', self::STATUS_ACTIVE); }
}
