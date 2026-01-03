<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ChatWidget extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'name',
        'widget_key',
        'welcome_message',
        'system_prompt',
        'primary_color',
        'position',
        'is_active',
        'auto_create_leads',
        'collect_email',
        'collect_phone',
        'allowed_domains',
        'settings',
        'banners',
        'show_banners',
        'banner_rotation_seconds',
        'ai_model',
        'temperature',
        'max_tokens',
        'rate_limit_per_hour',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_create_leads' => 'boolean',
        'collect_email' => 'boolean',
        'collect_phone' => 'boolean',
        'allowed_domains' => 'array',
        'settings' => 'array',
        'banners' => 'array',
        'show_banners' => 'boolean',
        'banner_rotation_seconds' => 'integer',
        'temperature' => 'decimal:2',
        'max_tokens' => 'integer',
        'rate_limit_per_hour' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($widget) {
            if (empty($widget->widget_key)) {
                $widget->widget_key = Str::random(32);
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class, 'widget_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ChatWidgetDocument::class, 'widget_id');
    }

    /**
     * Check if domain is allowed for embedding
     */
    public function isDomainAllowed(string $domain): bool
    {
        if (empty($this->allowed_domains)) {
            return true; // No restrictions
        }

        return in_array($domain, $this->allowed_domains);
    }

    /**
     * Generate embedding script URL
     */
    public function getEmbedUrl(): string
    {
        return url("/chat/widget/{$this->widget_key}.js");
    }
}
