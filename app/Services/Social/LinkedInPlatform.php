<?php

namespace App\Services\Social;

use App\Contracts\SocialPlatformInterface;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkedInPlatform implements SocialPlatformInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.linkedin.client_id', '');
        $this->clientSecret = config('services.linkedin.client_secret', '');
        $this->redirectUri = config('services.linkedin.redirect_uri', '');
    }

    public function getPlatformName(): string
    {
        return 'linkedin';
    }

    public function connectAccount(array $credentials): array
    {
        // Exchange authorization code for access token
        $response = Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type' => 'authorization_code',
            'code' => $credentials['code'],
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (!$response->successful()) {
            throw new \Exception('LinkedIn authentication failed: ' . $response->body());
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'];

        // Get user profile
        $profile = $this->getUserProfile($accessToken);

        return [
            'account_id' => $profile['id'],
            'access_token' => $accessToken,
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 5184000),
            'metadata' => [
                'name' => $profile['localizedFirstName'] . ' ' . $profile['localizedLastName'],
                'username' => $profile['vanityName'] ?? null,
                'profile_data' => $profile,
            ],
        ];
    }

    public function refreshToken(SocialAccount $account): array
    {
        if (!$account->refresh_token) {
            throw new \Exception('No refresh token available');
        }

        $response = Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type' => 'refresh_token',
            'refresh_token' => decrypt($account->refresh_token),
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Token refresh failed: ' . $response->body());
        }

        $tokenData = $response->json();

        return [
            'access_token' => $tokenData['access_token'],
            'expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 5184000),
        ];
    }

    public function publishPost(SocialPost $post): array
    {
        $account = $post->socialAccount;
        $accessToken = $this->getValidAccessToken($account);

        // LinkedIn API v2 for text posts
        $payload = [
            'author' => 'urn:li:person:' . $account->platform_account_id,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $post->content,
                    ],
                    'shareMediaCategory' => 'NONE',
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        // Add media if present
        if (!empty($post->media_urls)) {
            $payload['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'ARTICLE';
            $payload['specificContent']['com.linkedin.ugc.ShareContent']['media'] = array_map(function ($url) {
                return [
                    'status' => 'READY',
                    'media' => $url,
                ];
            }, $post->media_urls);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'X-Restli-Protocol-Version' => '2.0.0',
            'Content-Type' => 'application/json',
        ])->post('https://api.linkedin.com/v2/ugcPosts', $payload);

        if (!$response->successful()) {
            Log::error('LinkedIn post failed', [
                'error' => $response->body(),
                'post_id' => $post->id,
            ]);
            throw new \Exception('LinkedIn post failed: ' . $response->body());
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
        // LinkedIn doesn't support updating posts, so we delete and repost
        $this->deletePost($post);
        return $this->publishPost($post);
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
            'X-Restli-Protocol-Version' => '2.0.0',
        ])->delete('https://api.linkedin.com/v2/ugcPosts/' . $post->platform_post_id);

        return $response->successful();
    }

    public function getPostAnalytics(SocialPost $post): array
    {
        if (!$post->platform_post_id) {
            return [];
        }

        $account = $post->socialAccount;
        $accessToken = $this->getValidAccessToken($account);

        // LinkedIn analytics API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://api.linkedin.com/v2/socialActions/' . $post->platform_post_id);

        if (!$response->successful()) {
            return [];
        }

        $data = $response->json();

        return [
            'likes' => $data['likesSummary']['totalLikes'] ?? 0,
            'comments' => $data['commentsSummary']['totalFirstLevelComments'] ?? 0,
            'shares' => $data['sharesSummary']['totalShares'] ?? 0,
            'views' => $data['viewCount'] ?? 0,
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
            'max_length' => 3000,
            'allowed_media_types' => ['image', 'article'],
            'max_media_count' => 9,
        ];
    }

    private function getUserProfile(string $accessToken): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://api.linkedin.com/v2/me?projection=(id,localizedFirstName,localizedLastName,vanityName)');

        if (!$response->successful()) {
            throw new \Exception('Failed to get LinkedIn profile');
        }

        return $response->json();
    }

    private function getValidAccessToken(SocialAccount $account): string
    {
        $accessToken = decrypt($account->access_token);

        // Check if token is expired or about to expire
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
}

