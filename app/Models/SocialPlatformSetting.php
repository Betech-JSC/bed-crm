<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SocialPlatformSetting extends Model
{
    protected $fillable = [
        'account_id',
        'platform',
        'client_id',
        'client_secret',
        'redirect_uri',
        'scopes',
        'extra_config',
        'is_active',
    ];

    protected $casts = [
        'scopes' => 'array',
        'extra_config' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['client_secret'];

    // Encrypt client_secret
    public function setClientSecretAttribute($value): void
    {
        $this->attributes['client_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getClientSecretAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Check if this platform config has required fields.
     */
    public function isConfigured(): bool
    {
        return !empty($this->client_id) && !empty($this->attributes['client_secret']);
    }

    /**
     * Get the OAuth authorization URL.
     */
    public function getAuthUrl(): string
    {
        $redirectUri = urlencode($this->redirect_uri);
        $clientId = $this->client_id;

        return match ($this->platform) {
            'facebook' => "https://www.facebook.com/v18.0/dialog/oauth?client_id={$clientId}&redirect_uri={$redirectUri}&state=facebook&scope=" . implode(',', $this->scopes ?? ['pages_manage_posts', 'pages_read_engagement', 'pages_show_list']),
            'instagram' => "https://api.instagram.com/oauth/authorize?client_id={$clientId}&redirect_uri={$redirectUri}&scope=" . implode(',', $this->scopes ?? ['user_profile', 'user_media']) . "&response_type=code&state=instagram",
            'linkedin' => "https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id={$clientId}&redirect_uri={$redirectUri}&state=linkedin&scope=" . urlencode(implode(' ', $this->scopes ?? ['r_liteprofile', 'r_emailaddress', 'w_member_social'])),
            'twitter' => "https://twitter.com/i/oauth2/authorize?response_type=code&client_id={$clientId}&redirect_uri={$redirectUri}&scope=" . urlencode(implode(' ', $this->scopes ?? ['tweet.read', 'tweet.write', 'users.read'])) . "&state=twitter&code_challenge=challenge&code_challenge_method=plain",
            default => '#',
        };
    }

    /**
     * Get for account, keyed by platform.
     */
    public static function forAccount(int $accountId): array
    {
        return static::where('account_id', $accountId)
            ->get()
            ->keyBy('platform')
            ->toArray();
    }
}
