<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LinkBioPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id', 'created_by', 'username', 'display_name', 'bio', 'avatar',
        'theme', 'links', 'social_links', 'style_settings',
        'total_views', 'total_clicks',
    ];

    protected $casts = [
        'links' => 'array',
        'social_links' => 'array',
        'style_settings' => 'array',
    ];

    public function getPublicUrlAttribute(): string
    {
        return url("/bio/{$this->username}");
    }

    public function getCtrAttribute(): float
    {
        return $this->total_views > 0
            ? round(($this->total_clicks / $this->total_views) * 100, 1) : 0;
    }

    public static function getThemes(): array
    {
        return [
            'default' => ['label' => 'Classic', 'bg' => '#ffffff', 'text' => '#1e293b'],
            'dark' => ['label' => 'Dark Mode', 'bg' => '#0f172a', 'text' => '#f1f5f9'],
            'gradient' => ['label' => 'Gradient', 'bg' => 'linear-gradient(135deg, #667eea, #764ba2)', 'text' => '#ffffff'],
            'minimal' => ['label' => 'Minimal', 'bg' => '#fafbfc', 'text' => '#475569'],
            'neon' => ['label' => 'Neon', 'bg' => '#0a0a0a', 'text' => '#00ff88'],
        ];
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
