<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class SystemConfig extends Model
{
    protected $fillable = [
        'account_id', 'group', 'key', 'value', 'type', 'description', 'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // ── Relationships ──
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }

    // ════════════════════════════════════════════
    //  TYPED VALUE ACCESSORS
    // ════════════════════════════════════════════

    /**
     * Get the typed value based on `type` column.
     */
    public function getTypedValueAttribute()
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            'float' => (float) $this->value,
            default => $this->value,
        };
    }

    // ════════════════════════════════════════════
    //  STATIC HELPERS (cached)
    // ════════════════════════════════════════════

    /**
     * Get a config value (cached for 1 hour).
     */
    public static function getValue(int $accountId, string $group, string $key, $default = null)
    {
        $cacheKey = "sysconfig:{$accountId}:{$group}:{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($accountId, $group, $key, $default) {
            $config = static::where('account_id', $accountId)
                ->where('group', $group)
                ->where('key', $key)
                ->first();

            return $config ? $config->typed_value : $default;
        });
    }

    /**
     * Set a config value (upsert + cache bust).
     */
    public static function setValue(int $accountId, string $group, string $key, $value, string $type = 'string', ?string $description = null): self
    {
        $storedValue = match ($type) {
            'json' => is_string($value) ? $value : json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        $config = static::updateOrCreate(
            ['account_id' => $accountId, 'group' => $group, 'key' => $key],
            ['value' => $storedValue, 'type' => $type, 'description' => $description]
        );

        Cache::forget("sysconfig:{$accountId}:{$group}:{$key}");
        Cache::forget("sysconfig:group:{$accountId}:{$group}");

        return $config;
    }

    /**
     * Get all configs in a group (cached).
     */
    public static function getGroup(int $accountId, string $group): array
    {
        $cacheKey = "sysconfig:group:{$accountId}:{$group}";

        return Cache::remember($cacheKey, 3600, function () use ($accountId, $group) {
            return static::where('account_id', $accountId)
                ->where('group', $group)
                ->get()
                ->mapWithKeys(fn ($c) => [$c->key => $c->typed_value])
                ->toArray();
        });
    }

    /**
     * Get all public configs for frontend (cached).
     */
    public static function getPublicConfigs(int $accountId): array
    {
        return Cache::remember("sysconfig:public:{$accountId}", 3600, function () use ($accountId) {
            return static::where('account_id', $accountId)
                ->where('is_public', true)
                ->get()
                ->mapWithKeys(fn ($c) => ["{$c->group}.{$c->key}" => $c->typed_value])
                ->toArray();
        });
    }

    /**
     * Bulk set multiple configs at once.
     */
    public static function setBulk(int $accountId, string $group, array $configs): void
    {
        foreach ($configs as $key => $value) {
            $type = match (true) {
                is_bool($value) => 'boolean',
                is_int($value) => 'integer',
                is_float($value) => 'float',
                is_array($value) => 'json',
                default => 'string',
            };
            static::setValue($accountId, $group, $key, $value, $type);
        }
    }

    /**
     * Seed default configs for a new account.
     */
    public static function seedDefaults(int $accountId): void
    {
        $defaults = [
            'general' => [
                ['key' => 'company_name', 'value' => '', 'type' => 'string', 'is_public' => true, 'description' => 'Company display name'],
                ['key' => 'tagline', 'value' => '', 'type' => 'string', 'is_public' => true, 'description' => 'Company tagline/slogan'],
            ],
            'regional' => [
                ['key' => 'number_format', 'value' => '1.000,00', 'type' => 'string', 'is_public' => true, 'description' => 'Number format pattern'],
                ['key' => 'currency_position', 'value' => 'after', 'type' => 'string', 'is_public' => true, 'description' => 'Currency symbol position: before/after'],
                ['key' => 'week_starts_on', 'value' => '1', 'type' => 'integer', 'is_public' => true, 'description' => 'Week start day: 0=Sun, 1=Mon'],
            ],
            'crm' => [
                ['key' => 'lead_auto_assign', 'value' => '0', 'type' => 'boolean', 'is_public' => false, 'description' => 'Auto-assign leads to sales reps'],
                ['key' => 'deal_stages', 'value' => '["qualified","proposal","negotiation","won","lost"]', 'type' => 'json', 'is_public' => true, 'description' => 'Deal pipeline stages'],
                ['key' => 'lead_statuses', 'value' => '["new","contacted","qualified","unqualified"]', 'type' => 'json', 'is_public' => true, 'description' => 'Lead status options'],
                ['key' => 'default_deal_currency', 'value' => 'VND', 'type' => 'string', 'is_public' => true, 'description' => 'Default deal currency'],
            ],
            'notifications' => [
                ['key' => 'email_enabled', 'value' => '1', 'type' => 'boolean', 'is_public' => false, 'description' => 'Enable email notifications globally'],
                ['key' => 'digest_frequency', 'value' => 'daily', 'type' => 'string', 'is_public' => false, 'description' => 'Email digest frequency: none/daily/weekly'],
            ],
            'appearance' => [
                ['key' => 'primary_color', 'value' => '#6366f1', 'type' => 'string', 'is_public' => true, 'description' => 'Primary brand color'],
                ['key' => 'sidebar_theme', 'value' => 'dark', 'type' => 'string', 'is_public' => true, 'description' => 'Sidebar theme: light/dark'],
            ],
        ];

        foreach ($defaults as $group => $items) {
            foreach ($items as $item) {
                static::firstOrCreate(
                    ['account_id' => $accountId, 'group' => $group, 'key' => $item['key']],
                    [
                        'value' => $item['value'],
                        'type' => $item['type'],
                        'is_public' => $item['is_public'],
                        'description' => $item['description'],
                    ]
                );
            }
        }
    }
}
