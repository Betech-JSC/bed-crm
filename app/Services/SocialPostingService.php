<?php

namespace App\Services;

use App\Contracts\SocialPlatformInterface;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Log;

class SocialPostingService
{
    /**
     * Get platform service instance.
     */
    private function getPlatformService(string $platform): SocialPlatformInterface
    {
        $services = [
            'linkedin' => \App\Services\Social\LinkedInPlatform::class,
            'twitter' => \App\Services\Social\TwitterPlatform::class,
            'facebook' => \App\Services\Social\FacebookPlatform::class,
            'instagram' => \App\Services\Social\InstagramPlatform::class,
        ];

        $serviceClass = $services[$platform] ?? null;

        if (!$serviceClass || !class_exists($serviceClass)) {
            throw new \Exception("Platform service not found for: {$platform}");
        }

        return app($serviceClass);
    }

    /**
     * Publish a post to social media.
     */
    public function publishPost(SocialPost $post): bool
    {
        try {
            $platformService = $this->getPlatformService($post->platform);

            // Validate account connection
            if (!$platformService->validateConnection($post->socialAccount)) {
                throw new \Exception('Social account connection is invalid');
            }

            // Check content requirements
            $requirements = $platformService->getContentRequirements();
            if (strlen($post->content) > $requirements['max_length']) {
                throw new \Exception('Content exceeds maximum length for ' . $post->platform);
            }

            // Publish
            $result = $platformService->publishPost($post);

            // Update post
            $post->update([
                'status' => SocialPost::STATUS_PUBLISHED,
                'platform_post_id' => $result['post_id'],
                'platform_metadata' => $result['metadata'],
                'posted_at' => now(),
                'error_message' => null,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Social post publishing failed', [
                'post_id' => $post->id,
                'platform' => $post->platform,
                'error' => $e->getMessage(),
            ]);

            $post->update([
                'status' => SocialPost::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'retry_count' => $post->retry_count + 1,
                'last_retry_at' => now(),
            ]);

            return false;
        }
    }

    /**
     * Schedule a post for later.
     */
    public function schedulePost(SocialPost $post, \DateTime $scheduledAt): bool
    {
        if ($scheduledAt->isPast()) {
            throw new \Exception('Scheduled time cannot be in the past');
        }

        $post->update([
            'status' => SocialPost::STATUS_SCHEDULED,
            'scheduled_at' => $scheduledAt,
        ]);

        // Dispatch job to process at scheduled time
        \App\Jobs\PublishScheduledPost::dispatch($post)->delay($scheduledAt);

        return true;
    }

    /**
     * Publish to multiple platforms simultaneously.
     */
    public function publishToMultiplePlatforms(ContentItem $contentItem, array $socialAccountIds, ?\DateTime $scheduledAt = null): array
    {
        $accounts = SocialAccount::whereIn('id', $socialAccountIds)
            ->where('account_id', $contentItem->account_id)
            ->where('is_active', true)
            ->where('is_connected', true)
            ->get();

        $results = [];

        foreach ($accounts as $account) {
            $post = SocialPost::create([
                'account_id' => $contentItem->account_id,
                'content_item_id' => $contentItem->id,
                'social_account_id' => $account->id,
                'created_by' => auth()->id(),
                'platform' => $account->platform,
                'content' => $contentItem->content,
                'media_urls' => $contentItem->metadata['media_urls'] ?? null,
                'scheduled_at' => $scheduledAt,
                'status' => $scheduledAt ? SocialPost::STATUS_SCHEDULED : SocialPost::STATUS_DRAFT,
            ]);

            if ($scheduledAt) {
                $this->schedulePost($post, $scheduledAt);
            } else {
                $this->publishPost($post);
            }

            $results[] = $post;
        }

        // Update content item usage count
        $contentItem->increment('usage_count');

        return $results;
    }

    /**
     * Sync analytics for a post.
     */
    public function syncAnalytics(SocialPost $post): bool
    {
        try {
            $platformService = $this->getPlatformService($post->platform);
            $analytics = $platformService->getPostAnalytics($post);

            $post->update([
                'analytics' => $analytics,
                'analytics_synced_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Analytics sync failed', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Retry failed post.
     */
    public function retryPost(SocialPost $post): bool
    {
        if ($post->retry_count >= 3) {
            throw new \Exception('Maximum retry attempts reached');
        }

        $post->update([
            'status' => SocialPost::STATUS_POSTING,
        ]);

        return $this->publishPost($post);
    }
}

