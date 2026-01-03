<?php

namespace App\Jobs;

use App\Models\SocialPost;
use App\Services\SocialPostingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishScheduledPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SocialPost $post
    ) {
    }

    public function handle(SocialPostingService $postingService): void
    {
        // Reload post to ensure we have latest data
        $this->post->refresh();

        // Check if post is still scheduled
        if ($this->post->status !== SocialPost::STATUS_SCHEDULED) {
            Log::info('Post is no longer scheduled', ['post_id' => $this->post->id]);
            return;
        }

        // Check if scheduled time has passed
        if ($this->post->scheduled_at && $this->post->scheduled_at->isFuture()) {
            // Reschedule if time hasn't come yet
            $this->release($this->post->scheduled_at->diffInSeconds(now()));
            return;
        }

        // Update status to posting
        $this->post->update(['status' => SocialPost::STATUS_POSTING]);

        // Publish
        try {
            $postingService->publishPost($this->post);
        } catch (\Exception $e) {
            Log::error('Scheduled post publishing failed', [
                'post_id' => $this->post->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->post->update([
            'status' => SocialPost::STATUS_FAILED,
            'error_message' => $exception->getMessage(),
        ]);
    }
}
