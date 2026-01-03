<?php

namespace App\Services\Social;

use App\Contracts\SocialPlatformInterface;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookPlatform implements SocialPlatformInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.facebook.client_id', '');
        $this->clientSecret = config('services.facebook.client_secret', '');
        $this->redirectUri = config('services.facebook.redirect_uri', '');
    }

    public function getPlatformName(): string
    {
        return 'facebook';
    }

    public function connectAccount(array $credentials): array
    {
        $response = Http::asForm()->post('https://graph.facebook.com/v18.0/oauth/access_token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $credentials['code'],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Facebook authentication failed: ' . $response->body());
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'];

        // Get long-lived token
        $longLived = $this->getLongLivedToken($accessToken);
        $accessToken = $longLived['access_token'];

        // Get user profile
        $profile = $this->getUserProfile($accessToken);

        return [
            'account_id' => $profile['id'],
            'access_token' => $accessToken,
            'refresh_token' => null,
            'expires_at' => now()->addSeconds($longLived['expires_in'] ?? 5184000),
            'metadata' => [
                'name' => $profile['name'],
                'username' => $profile['username'] ?? null,
                'profile_data' => $profile,
            ],
        ];
    }

    public function refreshToken(SocialAccount $account): array
    {
        // Facebook long-lived tokens don't need refresh if still valid
        // But we can extend them
        $accessToken = $this->getValidAccessToken($account);
        $extended = $this->extendToken($accessToken);

        return [
            'access_token' => $extended['access_token'],
            'expires_at' => now()->addSeconds($extended['expires_in'] ?? 5184000),
        ];
    }

    public function publishPost(SocialPost $post): array
    {
        $account = $post->socialAccount;
        $accessToken = $this->getValidAccessToken($account);

        $payload = ['message' => $post->content];

        // Handle media
        if (!empty($post->media_urls)) {
            // Facebook requires separate API calls for media
            $photoIds = [];
            foreach ($post->media_urls as $mediaUrl) {
                $photoId = $this->uploadPhoto($accessToken, $account->platform_account_id, $mediaUrl);
                if ($photoId) {
                    $photoIds[] = $photoId;
                }
            }
            if (!empty($photoIds)) {
                $payload['attached_media'] = array_map(fn($id) => ['media_fbid' => $id], $photoIds);
            }
        }

        $response = Http::post("https://graph.facebook.com/v18.0/{$account->platform_account_id}/feed", [
            'access_token' => $accessToken,
            ...$payload,
        ]);

        if (!$response->successful()) {
            Log::error('Facebook post failed', [
                'error' => $response->body(),
                'post_id' => $post->id,
            ]);
            throw new \Exception('Facebook post failed: ' . $response->body());
        }

        $data = $response->json();
        $postId = $data['id'] ?? null;

        return [
            'post_id' => $postId,
            'metadata' => $data,
        ];
    }

    public function updatePost(SocialPost $post): array
    {
        // Facebook doesn't support updating posts
        throw new \Exception('Facebook does not support updating posts');
    }

    public function deletePost(SocialPost $post): bool
    {
        if (!$post->platform_post_id) {
            return false;
        }

        $account = $post->socialAccount;
        $accessToken = $this->getValidAccessToken($account);

        $response = Http::delete("https://graph.facebook.com/v18.0/{$post->platform_post_id}", [
            'access_token' => $accessToken,
        ]);

        return $response->successful();
    }

    public function getPostAnalytics(SocialPost $post): array
    {
        if (!$post->platform_post_id) {
            return [];
        }

        $account = $post->socialAccount;
        $accessToken = $this->getValidAccessToken($account);

        $response = Http::get("https://graph.facebook.com/v18.0/{$post->platform_post_id}", [
            'access_token' => $accessToken,
            'fields' => 'likes.summary(true),comments.summary(true),shares,reactions.summary(true)',
        ]);

        if (!$response->successful()) {
            return [];
        }

        $data = $response->json();

        return [
            'likes' => $data['likes']['summary']['total_count'] ?? 0,
            'comments' => $data['comments']['summary']['total_count'] ?? 0,
            'shares' => $data['shares']['count'] ?? 0,
            'reactions' => $data['reactions']['summary']['total_count'] ?? 0,
        ];
    }

    public function validateConnection(SocialAccount $account): bool
    {
        try {
            $this->getUserProfile($this->getValidAccessToken($account));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getContentRequirements(): array
    {
        return [
            'max_length' => 5000,
            'allowed_media_types' => ['image', 'video'],
            'max_media_count' => 10,
        ];
    }

    private function getUserProfile(string $accessToken): array
    {
        $response = Http::get('https://graph.facebook.com/v18.0/me', [
            'access_token' => $accessToken,
            'fields' => 'id,name,username',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to get Facebook profile');
        }

        return $response->json();
    }

    private function getLongLivedToken(string $shortLivedToken): array
    {
        $response = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if (!$response->successful()) {
            return ['access_token' => $shortLivedToken, 'expires_in' => 3600];
        }

        return $response->json();
    }

    private function extendToken(string $accessToken): array
    {
        $response = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'fb_exchange_token' => $accessToken,
        ]);

        if (!$response->successful()) {
            return ['access_token' => $accessToken, 'expires_in' => 5184000];
        }

        return $response->json();
    }

    private function getValidAccessToken(SocialAccount $account): string
    {
        $accessToken = decrypt($account->access_token);

        if ($account->token_expires_at && $account->token_expires_at->isPast()) {
            $refreshed = $this->refreshToken($account);
            $account->update([
                'access_token' => encrypt($refreshed['access_token']),
                'token_expires_at' => $refreshed['expires_at'],
            ]);
            return $refreshed['access_token'];
        }

        return $accessToken;
    }

    private function uploadPhoto(string $accessToken, string $pageId, string $photoUrl): ?string
    {
        try {
            $fileContent = file_get_contents($photoUrl);
            $tempFile = tmpfile();
            fwrite($tempFile, $fileContent);
            $tempPath = stream_get_meta_data($tempFile)['uri'];

            $response = Http::attach('source', fopen($tempPath, 'r'), basename($photoUrl))
                ->post("https://graph.facebook.com/v18.0/{$pageId}/photos", [
                    'access_token' => $accessToken,
                    'published' => false,
                ]);

            fclose($tempFile);

            if ($response->successful()) {
                $data = $response->json();
                return $data['id'] ?? null;
            }
        } catch (\Exception $e) {
            Log::error('Facebook photo upload failed', ['error' => $e->getMessage()]);
        }

        return null;
    }
}

