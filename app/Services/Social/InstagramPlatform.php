<?php

namespace App\Services\Social;

use App\Contracts\SocialPlatformInterface;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramPlatform implements SocialPlatformInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.instagram.client_id', '');
        $this->clientSecret = config('services.instagram.client_secret', '');
        $this->redirectUri = config('services.instagram.redirect_uri', '');
    }

    public function getPlatformName(): string
    {
        return 'instagram';
    }

    public function connectAccount(array $credentials): array
    {
        // Instagram Basic Display API or Instagram Graph API
        $response = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code' => $credentials['code'],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Instagram authentication failed: ' . $response->body());
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'];

        // Get user profile
        $profile = $this->getUserProfile($accessToken);

        return [
            'account_id' => $profile['id'],
            'access_token' => $accessToken,
            'refresh_token' => null,
            'expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 5184000),
            'metadata' => [
                'name' => $profile['username'],
                'username' => $profile['username'],
                'profile_data' => $profile,
            ],
        ];
    }

    public function refreshToken(SocialAccount $account): array
    {
        // Instagram tokens are long-lived, but we can refresh if needed
        $accessToken = $this->getValidAccessToken($account);
        
        return [
            'access_token' => $accessToken,
            'expires_at' => now()->addDays(60),
        ];
    }

    public function publishPost(SocialPost $post): array
    {
        $account = $post->socialAccount;
        $accessToken = $this->getValidAccessToken($account);

        // Instagram requires media for posts
        if (empty($post->media_urls)) {
            throw new \Exception('Instagram posts require at least one image or video');
        }

        // Upload media container
        $mediaId = $this->createMediaContainer($accessToken, $account->platform_account_id, $post);

        // Publish the container
        $response = Http::post("https://graph.facebook.com/v18.0/{$account->platform_account_id}/media_publish", [
            'access_token' => $accessToken,
            'creation_id' => $mediaId,
        ]);

        if (!$response->successful()) {
            Log::error('Instagram post failed', [
                'error' => $response->body(),
                'post_id' => $post->id,
            ]);
            throw new \Exception('Instagram post failed: ' . $response->body());
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
        throw new \Exception('Instagram does not support updating posts');
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

        $response = Http::get("https://graph.facebook.com/v18.0/{$post->platform_post_id}/insights", [
            'access_token' => $accessToken,
            'metric' => 'impressions,reach,likes,comments,shares,saved',
        ]);

        if (!$response->successful()) {
            return [];
        }

        $data = $response->json();
        $metrics = [];
        
        foreach ($data['data'] ?? [] as $metric) {
            $metrics[$metric['name']] = $metric['values'][0]['value'] ?? 0;
        }

        return [
            'likes' => $metrics['likes'] ?? 0,
            'comments' => $metrics['comments'] ?? 0,
            'shares' => $metrics['shares'] ?? 0,
            'saved' => $metrics['saved'] ?? 0,
            'impressions' => $metrics['impressions'] ?? 0,
            'reach' => $metrics['reach'] ?? 0,
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
            'max_length' => 2200,
            'allowed_media_types' => ['image', 'video', 'carousel'],
            'max_media_count' => 10,
            'requires_media' => true,
        ];
    }

    private function getUserProfile(string $accessToken): array
    {
        $response = Http::get('https://graph.instagram.com/me', [
            'access_token' => $accessToken,
            'fields' => 'id,username',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to get Instagram profile');
        }

        return $response->json();
    }

    private function getValidAccessToken(SocialAccount $account): string
    {
        return decrypt($account->access_token);
    }

    private function createMediaContainer(string $accessToken, string $accountId, SocialPost $post): string
    {
        $mediaUrl = $post->media_urls[0]; // Instagram single media or carousel
        
        $payload = [
            'access_token' => $accessToken,
            'image_url' => $mediaUrl,
            'caption' => $post->content,
        ];

        $response = Http::post("https://graph.facebook.com/v18.0/{$accountId}/media", $payload);

        if (!$response->successful()) {
            throw new \Exception('Failed to create media container: ' . $response->body());
        }

        $data = $response->json();
        return $data['id'];
    }
}



