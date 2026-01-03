<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\SocialPost;
use App\Services\SocialPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class SocialPostsController extends Controller
{
    public function __construct(
        private SocialPostingService $postingService
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('SocialPosts/Index', [
            'posts' => Auth::user()->account->socialPosts()
                ->with(['contentItem', 'socialAccount', 'creator'])
                ->orderBy('created_at', 'desc')
                ->paginate(20)
                ->through(fn ($post) => [
                    'id' => $post->id,
                    'platform' => $post->platform,
                    'content' => substr($post->content, 0, 100) . '...',
                    'status' => $post->status,
                    'scheduled_at' => $post->scheduled_at?->format('Y-m-d H:i'),
                    'posted_at' => $post->posted_at?->format('Y-m-d H:i'),
                    'social_account' => $post->socialAccount ? [
                        'id' => $post->socialAccount->id,
                        'name' => $post->socialAccount->name,
                    ] : null,
                    'content_item' => $post->contentItem ? [
                        'id' => $post->contentItem->id,
                        'title' => $post->contentItem->title,
                    ] : null,
                    'analytics' => $post->analytics,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('SocialPosts/Create', [
            'contentItems' => Auth::user()->account->contentItems()
                ->where('status', ContentItem::STATUS_APPROVED)
                ->get()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'content' => substr($item->content, 0, 200),
                ]),
            'socialAccounts' => Auth::user()->account->socialAccounts()
                ->active()
                ->get()
                ->map(fn ($account) => [
                    'id' => $account->id,
                    'platform' => $account->platform,
                    'name' => $account->name,
                ]),
        ]);
    }

    public function store(): RedirectResponse
    {
        $validated = Request::validate([
            'content_item_id' => ['required', 'exists:content_items,id'],
            'social_account_ids' => ['required', 'array', 'min:1'],
            'social_account_ids.*' => ['exists:social_accounts,id'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'content' => ['nullable', 'string'], // Override content if needed
        ]);

        $contentItem = ContentItem::findOrFail($validated['content_item_id']);
        $scheduledAt = $validated['scheduled_at'] ? new \DateTime($validated['scheduled_at']) : null;

        $posts = $this->postingService->publishToMultiplePlatforms(
            $contentItem,
            $validated['social_account_ids'],
            $scheduledAt
        );

        $message = $scheduledAt 
            ? count($posts) . ' posts scheduled successfully.'
            : count($posts) . ' posts published successfully.';

        return Redirect::route('social-posts')->with('success', $message);
    }

    public function show(SocialPost $socialPost): Response
    {
        return Inertia::render('SocialPosts/Show', [
            'post' => [
                'id' => $socialPost->id,
                'platform' => $socialPost->platform,
                'content' => $socialPost->content,
                'media_urls' => $socialPost->media_urls,
                'status' => $socialPost->status,
                'scheduled_at' => $socialPost->scheduled_at?->format('Y-m-d H:i'),
                'posted_at' => $socialPost->posted_at?->format('Y-m-d H:i'),
                'platform_post_id' => $socialPost->platform_post_id,
                'error_message' => $socialPost->error_message,
                'analytics' => $socialPost->analytics,
                'analytics_synced_at' => $socialPost->analytics_synced_at?->format('Y-m-d H:i'),
                'social_account' => $socialPost->socialAccount ? [
                    'id' => $socialPost->socialAccount->id,
                    'name' => $socialPost->socialAccount->name,
                    'platform' => $socialPost->socialAccount->platform,
                ] : null,
                'content_item' => $socialPost->contentItem ? [
                    'id' => $socialPost->contentItem->id,
                    'title' => $socialPost->contentItem->title,
                ] : null,
            ],
        ]);
    }

    public function destroy(SocialPost $socialPost): RedirectResponse
    {
        // Delete from platform if published
        if ($socialPost->status === SocialPost::STATUS_PUBLISHED && $socialPost->platform_post_id) {
            try {
                $platformService = \App\Providers\SocialServiceProvider::getPlatformService($socialPost->platform);
                if ($platformService) {
                    $platformService->deletePost($socialPost);
                }
            } catch (\Exception $e) {
                // Log error but continue with deletion
            }
        }

        $socialPost->delete();

        return Redirect::route('social-posts')->with('success', 'Post deleted.');
    }

    public function retry(SocialPost $socialPost): RedirectResponse
    {
        try {
            $this->postingService->retryPost($socialPost);
            return Redirect::back()->with('success', 'Post retry initiated.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Retry failed: ' . $e->getMessage());
        }
    }

    public function syncAnalytics(SocialPost $socialPost): RedirectResponse
    {
        $this->postingService->syncAnalytics($socialPost);

        return Redirect::back()->with('success', 'Analytics synced.');
    }
}
