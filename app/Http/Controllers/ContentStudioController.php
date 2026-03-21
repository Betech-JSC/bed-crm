<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\AiContentStudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ContentStudioController extends Controller
{
    public function __construct(
        private AiContentStudioService $studio,
    ) {}

    /**
     * Show the Content Studio page.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $accountId = $user->account_id;

        // Recent generated content
        $recentContent = ContentItem::where('account_id', $accountId)
            ->whereNotNull('ai_model')
            ->with('creator')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'content' => substr($item->content, 0, 120) . (strlen($item->content) > 120 ? '...' : ''),
                'type' => $item->type,
                'status' => $item->status,
                'ai_model' => $item->ai_model,
                'thumbnail' => $item->metadata['media_urls'][0] ?? null,
                'platform' => $item->metadata['platform'] ?? null,
                'usage_count' => $item->usage_count,
                'created_at' => $item->created_at->diffForHumans(),
            ]);

        // Connected social accounts
        $socialAccounts = SocialAccount::where('account_id', $accountId)
            ->active()
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'platform' => $a->platform,
                'name' => $a->name,
                'username' => $a->username,
                'is_connected' => $a->is_connected,
                'is_token_expired' => $a->isTokenExpired(),
            ]);

        // Recent posts stats
        $postStats = [
            'total' => SocialPost::where('account_id', $accountId)->count(),
            'published' => SocialPost::where('account_id', $accountId)->where('status', 'published')->count(),
            'scheduled' => SocialPost::where('account_id', $accountId)->where('status', 'scheduled')->count(),
            'failed' => SocialPost::where('account_id', $accountId)->where('status', 'failed')->count(),
        ];

        return Inertia::render('ContentStudio/Index', [
            'recentContent' => $recentContent,
            'socialAccounts' => $socialAccounts,
            'postStats' => $postStats,
            'tones' => [
                'professional' => 'Chuyên nghiệp',
                'casual' => 'Thân thiện',
                'humorous' => 'Hài hước',
                'inspirational' => 'Truyền cảm hứng',
                'educational' => 'Giáo dục',
                'storytelling' => 'Kể chuyện',
            ],
            'contentTypes' => [
                'post' => 'Bài đăng',
                'article' => 'Bài viết dài',
                'announcement' => 'Thông báo',
                'promotion' => 'Quảng cáo',
                'tips' => 'Mẹo / Hướng dẫn',
                'news' => 'Tin tức',
            ],
            'thumbnailStyles' => [
                'modern' => 'Hiện đại',
                'corporate' => 'Doanh nghiệp',
                'creative' => 'Sáng tạo',
                'tech' => 'Công nghệ',
                'nature' => 'Tự nhiên',
                'bold' => 'Nổi bật',
            ],
            'platforms' => [
                'facebook' => ['label' => 'Facebook', 'icon' => 'pi pi-facebook', 'color' => '#1877F2'],
                'instagram' => ['label' => 'Instagram', 'icon' => 'pi pi-instagram', 'color' => '#E4405F'],
                'linkedin' => ['label' => 'LinkedIn', 'icon' => 'pi pi-linkedin', 'color' => '#0A66C2'],
                'twitter' => ['label' => 'Twitter/X', 'icon' => 'pi pi-twitter', 'color' => '#1DA1F2'],
            ],
        ]);
    }

    /**
     * Generate content via AI.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic' => 'required|string|max:500',
            'tone' => 'required|string|in:professional,casual,humorous,inspirational,educational,storytelling',
            'language' => 'required|string|in:vi,en',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'string|in:facebook,instagram,linkedin,twitter',
            'content_type' => 'required|string|in:post,article,announcement,promotion,tips,news',
            'instructions' => 'nullable|string|max:1000',
            'generate_thumbnail' => 'boolean',
            'thumbnail_style' => 'nullable|string',
            'hashtags' => 'boolean',
        ]);

        try {
            $result = $this->studio->generateContent($validated);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI không thể tạo nội dung: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save generated content as ContentItems.
     */
    public function saveContent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'contents' => 'required|array',
            'thumbnail_url' => 'nullable|string',
            'ai_model' => 'nullable|string',
            'tokens_used' => 'nullable|integer',
            'topic' => 'required|string',
            'tone' => 'nullable|string',
        ]);

        try {
            $results = $this->studio->createContentAndPosts([
                'contents' => $validated['contents'],
                'thumbnail_url' => $validated['thumbnail_url'] ?? null,
                'ai_model' => $validated['ai_model'] ?? 'unknown',
                'tokens_used' => $validated['tokens_used'] ?? 0,
            ], [
                'topic' => $validated['topic'],
                'tone' => $validated['tone'] ?? 'professional',
            ]);

            $items = [];
            foreach ($results as $platform => $data) {
                $items[$platform] = [
                    'id' => $data['content_item']->id,
                    'title' => $data['content_item']->title,
                ];
            }

            return response()->json([
                'success' => true,
                'items' => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi lưu nội dung: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publish content to social platforms.
     */
    public function publish(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content_item_ids' => 'required|array',
            'content_item_ids.*' => 'integer|exists:content_items,id',
            'social_account_ids' => 'required|array',
            'social_account_ids.*' => 'integer|exists:social_accounts,id',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        try {
            $scheduledAt = $validated['scheduled_at'] ? new \DateTime($validated['scheduled_at']) : null;

            $results = [];
            foreach ($validated['content_item_ids'] as $contentItemId) {
                $contentItem = ContentItem::findOrFail($contentItemId);

                $posts = $this->studio->publishContent(
                    ['default' => ['content_item' => $contentItem]],
                    $validated['social_account_ids'],
                    $scheduledAt
                );

                $results[] = $posts;
            }

            return response()->json([
                'success' => true,
                'message' => $scheduledAt ? 'Bài đăng đã được lên lịch!' : 'Bài đăng đã được đăng!',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi đăng bài: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Regenerate thumbnail for a topic.
     */
    public function regenerateThumbnail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic' => 'required|string|max:500',
            'style' => 'nullable|string',
        ]);

        try {
            $url = $this->studio->generateThumbnail($validated['topic'], $validated['style'] ?? 'modern');

            return response()->json([
                'success' => true,
                'thumbnail_url' => $url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo hình ảnh: ' . $e->getMessage(),
            ], 500);
        }
    }
}
