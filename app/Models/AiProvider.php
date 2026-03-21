<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProvider extends Model
{
    protected $table = 'ai_providers';

    protected $fillable = [
        'account_id',
        'slug',
        'display_name',
        'api_key',
        'model',
        'available_models',
        'is_active',
        'is_default',
        'config',
        'status',
        'last_tested_at',
        'last_error',
        'total_requests',
        'total_tokens',
    ];

    protected $casts = [
        'available_models' => 'array',
        'config' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'last_tested_at' => 'datetime',
        'total_requests' => 'integer',
        'total_tokens' => 'integer',
    ];

    /**
     * Hidden fields (API key should not leak to frontend).
     */
    protected $hidden = [
        'api_key',
    ];

    /**
     * Known provider registry with defaults.
     */
    public const PROVIDERS = [
        'gemini' => [
            'display_name' => 'Google Gemini',
            'icon' => 'google',
            'color' => '#4285F4',
            'models' => [
                'gemini-2.5-flash' => 'Gemini 2.5 Flash (Best value)',
                'gemini-2.5-pro-preview-05-06' => 'Gemini 2.5 Pro (Most capable)',
                'gemini-2.0-flash' => 'Gemini 2.0 Flash',
                'gemini-2.0-flash-lite' => 'Gemini 2.0 Flash Lite (Fastest)',
            ],
            'default_model' => 'gemini-2.5-flash',
            'supports_embed' => true,
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta/models',
        ],
        'openai' => [
            'display_name' => 'OpenAI',
            'icon' => 'openai',
            'color' => '#10A37F',
            'models' => [
                'gpt-4o' => 'GPT-4o (Most capable)',
                'gpt-4o-mini' => 'GPT-4o Mini (Fast & affordable)',
                'gpt-4-turbo' => 'GPT-4 Turbo',
                'gpt-4.1' => 'GPT-4.1',
                'gpt-4.1-mini' => 'GPT-4.1 Mini',
                'o3-mini' => 'o3 Mini (Reasoning)',
            ],
            'default_model' => 'gpt-4o-mini',
            'supports_embed' => true,
            'base_url' => 'https://api.openai.com/v1',
        ],
        'claude' => [
            'display_name' => 'Anthropic Claude',
            'icon' => 'anthropic',
            'color' => '#D97706',
            'models' => [
                'claude-sonnet-4-20250514' => 'Claude Sonnet 4 (Latest)',
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku (Fastest)',
            ],
            'default_model' => 'claude-sonnet-4-20250514',
            'supports_embed' => false,
            'base_url' => 'https://api.anthropic.com/v1',
        ],
        'deepseek' => [
            'display_name' => 'DeepSeek',
            'icon' => 'deepseek',
            'color' => '#6366F1',
            'models' => [
                'deepseek-chat' => 'DeepSeek Chat (V3)',
                'deepseek-reasoner' => 'DeepSeek Reasoner (R1)',
            ],
            'default_model' => 'deepseek-chat',
            'supports_embed' => false,
            'base_url' => 'https://api.deepseek.com/v1',
        ],
        'groq' => [
            'display_name' => 'Groq',
            'icon' => 'groq',
            'color' => '#F97316',
            'models' => [
                'llama-3.3-70b-versatile' => 'Llama 3.3 70B (Versatile)',
                'llama-3.1-8b-instant' => 'Llama 3.1 8B (Instant)',
                'mixtral-8x7b-32768' => 'Mixtral 8x7B',
            ],
            'default_model' => 'llama-3.3-70b-versatile',
            'supports_embed' => false,
            'base_url' => 'https://api.groq.com/openai/v1',
        ],
    ];

    // ── Accessors ──

    /**
     * Check if API key is set (without revealing it).
     */
    public function getHasApiKeyAttribute(): bool
    {
        return !empty($this->api_key);
    }

    /**
     * Get masked API key for display.
     */
    public function getMaskedKeyAttribute(): ?string
    {
        if (empty($this->api_key)) return null;
        $key = $this->api_key;
        if (strlen($key) <= 8) return str_repeat('•', strlen($key));
        return substr($key, 0, 4) . str_repeat('•', min(20, strlen($key) - 8)) . substr($key, -4);
    }

    /**
     * Get provider meta from registry.
     */
    public function getProviderMetaAttribute(): array
    {
        return self::PROVIDERS[$this->slug] ?? [
            'display_name' => $this->display_name,
            'icon' => 'default',
            'color' => '#6B7280',
            'models' => [],
            'supports_embed' => false,
        ];
    }

    // ── Relationships ──

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    // ── Helpers ──

    /**
     * Mark as default (and unset others).
     */
    public function makeDefault(): void
    {
        static::where('account_id', $this->account_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true, 'is_active' => true]);
    }

    /**
     * Record a successful request.
     */
    public function recordRequest(int $tokensUsed = 0): void
    {
        $this->increment('total_requests');
        if ($tokensUsed > 0) {
            $this->increment('total_tokens', $tokensUsed);
        }
    }

    /**
     * Update connection status after test.
     */
    public function markConnected(): void
    {
        $this->update([
            'status' => 'connected',
            'last_tested_at' => now(),
            'last_error' => null,
        ]);
    }

    public function markError(string $error): void
    {
        $this->update([
            'status' => 'error',
            'last_tested_at' => now(),
            'last_error' => $error,
        ]);
    }
}
