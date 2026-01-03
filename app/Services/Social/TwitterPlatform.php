<?php

namespace App\Services\Social;

use App\Contracts\SocialPlatformInterface;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwitterPlatform implements SocialPlatformInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.twitter.client_id', '');
        $this->clientSecret = config('services.twitter.client_secret', '');
        $this->redirectUri = config('services.twitter.redirect_uri', '');
    }

    public function getPlatformName(): string
    {
        return 'twitter';
    }

    public function connectAccount(array $credentials): array
    {
        // OAuth 2.0 flow for Twitter API v2
        $response = Http::asForm()->withBasicAuth($this->clientId, $this->clientSecret)
            ->post('https://api.twitter.com/2/oauth2/token', [
                'code' => $credentials['code'],
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri,
                'code_verifier' => $credentials['code_verifier'] ?? '',
            ]);

        if (!$response->successful()) {
            throw new \Exception('Twitter authentication failed: ' . $response->body());
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'];

        // Get user profile
        $profile = $this->getUserProfile($accessToken);

        return [
            'account_id' => $profile['id'],
            'access_token' => $accessToken,
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 7200),
            'metadata' => [
                'name' => $profile['name'],
                'username' => $profile['username'],
                'profile_data' => $profile,
            ],
        ];
    }

    public function refreshToken(SocialAccount $account): array
    {
        if (!$account->refresh_token) {
            throw new \Exception('No refresh token available');
        }

        $response = Http::asForm()->withBasicAuth($this->clientId, $this->clientSecret)
            ->post('https://api.twitter.com/2/oauth2/token', [
                'refresh_token' => decrypt($account->refresh_token),
                'grant_type' => 'refresh_token',
            ]);

        if (!$response->successful()) {
            throw new \Exception('Token refresh failed: ' . $response->body());
        }

        $tokenData = $response->json();

        return [
            'access_token' => $tokenData['access_token'],
            'expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 7200),
        ];
    }

    public function publishPost(SocialPost $post): array
    {
        $account = $post->socialAccount;
        $accessToken = $this->getValidAccessToken($account);

        $payload = [
            'text' => $post->content,
        ];

        // Handle media uploads
        if (!empty($post->media_urls)) {
            $mediaIds = [];
            foreach ($post->media_urls as $mediaUrl) {
                $mediaId = $this->uploadMedia($accessToken, $mediaUrl);
                if ($mediaId) {
                    $mediaIds[] = $mediaId;
                }
            }
            if (!empty($mediaIds)) {
                $payload['media'] = ['media_ids' => $mediaIds];
            }
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post('https://api.twitter.com/2/tweets', $payload);

        if (!$response->successful()) {
            Log::error('Twitter post failed', [
                'error' => $response->body(),
                'post_id' => $post->id,
            ]);
            throw new \Exception('Twitter post failed: ' . $response->body());
        }

        $data = $response->json();
        $postId = $data['data']['id'] ?? null;

        return [
            'post_id' => $postId,
            'metadata' => $data,
        ];
    }

    public function updatePost(SocialPost $post): array
    {
        // Twitter doesn't support updating posts
        throw new \Exception('Twitter does not support updating posts');
    }

    public function deletePost(SocialPost $post): bool
    {
        if (!$post->platform_post_id) {
            return false;
        }

        $account = $post->socialAccount;
        $accessToken = $this->getValidAccessToken($account);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->delete('https://api.twitter.com/2/tweets/' . $post->platform_post_id);

        return $response->successful();
    }

    public function getPostAnalytics(SocialPost $post): array
    {
        if (!$post->platform_post_id) {
            return [];
        }

        $account = $post->socialAccount;
        $accessToken = $this->getValidAccessToken($account);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://api.twitter.com/2/tweets/' . $post->platform_post_id . '?tweet.fields=public_metrics');

        if (!$response->successful()) {
            return [];
        }

        $data = $response->json();
        $metrics = $data['data']['public_metrics'] ?? [];

        return [
            'likes' => $metrics['like_count'] ?? 0,
            'retweets' => $metrics['retweet_count'] ?? 0,
            'replies' => $metrics['reply_count'] ?? 0,
            'quotes' => $metrics['quote_count'] ?? 0,
            'views' => $metrics['impression_count'] ?? 0,
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
            'max_length' => 280,
            'allowed_media_types' => ['image', 'video'],
            'max_media_count' => 4,
        ];
    }

    private function getUserProfile(string $accessToken): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://api.twitter.com/2/users/me?user.fields=username,name');

        if (!$response->successful()) {
            throw new \Exception('Failed to get Twitter profile');
        }

        $data = $response->json();
        return $data['data'] ?? [];
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

    private function uploadMedia(string $accessToken, string $mediaUrl): ?string
    {
        // Download and upload media to Twitter
        // This is a simplified version - production should handle file uploads properly
        try {
            $fileContent = file_get_contents($mediaUrl);
            $tempFile = tmpfile();
            fwrite($tempFile, $fileContent);
            $tempPath = stream_get_meta_data($tempFile)['uri'];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->attach('media', fopen($tempPath, 'r'))
                ->post('https://upload.twitter.com/1.1/media/upload.json');

            fclose($tempFile);

            if ($response->successful()) {
                $data = $response->json();
                return $data['media_id_string'] ?? null;
            }
        } catch (\Exception $e) {
            Log::error('Twitter media upload failed', ['error' => $e->getMessage()]);
        }

        return null;
    }
}

