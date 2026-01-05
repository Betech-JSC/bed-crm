<?php

namespace App\Contracts;

use App\Models\SocialAccount;
use App\Models\SocialPost;

interface SocialPlatformInterface
{
    /**
     * Get platform identifier.
     *
     * @return string
     */
    public function getPlatformName(): string;

    /**
     * Connect/authenticate a social account.
     *
     * @param array $credentials
     * @return array ['account_id' => string, 'access_token' => string, 'metadata' => array]
     */
    public function connectAccount(array $credentials): array;

    /**
     * Refresh access token.
     *
     * @param SocialAccount $account
     * @return array ['access_token' => string, 'expires_at' => Carbon]
     */
    public function refreshToken(SocialAccount $account): array;

    /**
     * Post content to the platform.
     *
     * @param SocialPost $post
     * @return array ['post_id' => string, 'metadata' => array]
     * @throws \Exception
     */
    public function publishPost(SocialPost $post): array;

    /**
     * Update an existing post.
     *
     * @param SocialPost $post
     * @return array ['post_id' => string, 'metadata' => array]
     * @throws \Exception
     */
    public function updatePost(SocialPost $post): array;

    /**
     * Delete a post from the platform.
     *
     * @param SocialPost $post
     * @return bool
     * @throws \Exception
     */
    public function deletePost(SocialPost $post): bool;

    /**
     * Get post analytics/engagement metrics.
     *
     * @param SocialPost $post
     * @return array ['likes' => int, 'shares' => int, 'comments' => int, 'views' => int, etc.]
     */
    public function getPostAnalytics(SocialPost $post): array;

    /**
     * Validate account connection.
     *
     * @param SocialAccount $account
     * @return bool
     */
    public function validateConnection(SocialAccount $account): bool;

    /**
     * Get platform-specific content requirements/limits.
     *
     * @return array ['max_length' => int, 'allowed_media_types' => array, etc.]
     */
    public function getContentRequirements(): array;
}



