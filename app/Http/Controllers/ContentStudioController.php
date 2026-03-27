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
                'content' => mb_substr(strip_tags($item->content ?? ''), 0, 120, 'UTF-8') . (mb_strlen(strip_tags($item->content ?? ''), 'UTF-8') > 120 ? '...' : ''),
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
        set_time_limit(180);

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
        set_time_limit(120);

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

    /**
     * Generate SEO article via AI.
     */
    public function generateSeo(Request $request): JsonResponse
    {
        set_time_limit(300);

        $validated = $request->validate([
            'topic' => 'required|string|max:500',
            'tone' => 'required|string|in:professional,casual,educational,storytelling',
            'language' => 'required|string|in:vi,en',
            'article_length' => 'required|string|in:short,medium,long,pillar',
            'focus_keyword' => 'nullable|string|max:100',
            'instructions' => 'nullable|string|max:1000',
            'generate_thumbnail' => 'boolean',
            'thumbnail_style' => 'nullable|string',
        ]);

        try {
            $result = $this->studio->generateSeoArticle($validated);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI không thể tạo bài SEO: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save generated SEO article.
     */
    public function saveSeo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'article' => 'required|array',
            'article.meta_title' => 'nullable|string',
            'article.meta_description' => 'nullable|string',
            'article.slug' => 'nullable|string',
            'article.content' => 'required|string',
            'topic' => 'required|string',
            'tone' => 'nullable|string',
            'thumbnail_url' => 'nullable|string',
            'ai_model' => 'nullable|string',
            'tokens_used' => 'nullable|integer',
        ]);

        try {
            $contentItem = $this->studio->saveSeoArticle($validated['article'], [
                'topic' => $validated['topic'],
                'tone' => $validated['tone'] ?? 'professional',
                'thumbnail_url' => $validated['thumbnail_url'] ?? null,
                'ai_model' => $validated['ai_model'] ?? 'unknown',
                'tokens_used' => $validated['tokens_used'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'item' => [
                    'id' => $contentItem->id,
                    'title' => $contentItem->title,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi lưu bài SEO: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate an inline article image.
     */
    public function generateArticleImage(Request $request): JsonResponse
    {
        set_time_limit(120);

        $validated = $request->validate([
            'topic' => 'required|string|max:500',
            'context' => 'nullable|string|max:500',
            'style' => 'nullable|string',
        ]);

        try {
            $url = $this->studio->generateArticleImage(
                $validated['topic'],
                $validated['context'] ?? '',
                $validated['style'] ?? 'modern'
            );

            return response()->json([
                'success' => (bool) $url,
                'image_url' => $url,
                'message' => $url ? null : 'Không thể tạo ảnh. Kiểm tra API key.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi tạo ảnh: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze SEO quality of content.
     */
    public function analyzeSeo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'focus_keyword' => 'required|string|max:100',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
        ]);

        $content = $validated['content'];
        $keyword = strtolower($validated['focus_keyword']);
        $lowerContent = strtolower($content);
        $wordCount = str_word_count(strip_tags($content));

        // Keyword density
        $keywordCount = substr_count($lowerContent, $keyword);
        $keywordDensity = $wordCount > 0 ? round(($keywordCount / $wordCount) * 100, 2) : 0;

        // Readability (simplified Flesch-Kincaid)
        $sentences = max(1, preg_match_all('/[.!?]+/', strip_tags($content)));
        $avgWordsPerSentence = $wordCount / $sentences;
        $readabilityScore = min(100, max(0, round(100 - ($avgWordsPerSentence * 1.5))));

        // Heading analysis
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $content, $headings);
        $headingStructure = [];
        foreach ($headings[1] as $i => $level) {
            $headingStructure[] = ['level' => (int) $level, 'text' => strip_tags($headings[2][$i])];
        }

        // Meta analysis
        $metaTitle = $validated['meta_title'] ?? '';
        $metaDesc = $validated['meta_description'] ?? '';
        $titleLength = strlen($metaTitle);
        $descLength = strlen($metaDesc);

        // Score calculation
        $score = 0;
        $issues = [];
        $suggestions = [];

        // Keyword checks
        if ($keywordDensity >= 0.5 && $keywordDensity <= 2.5) { $score += 15; }
        else { $issues[] = $keywordDensity < 0.5 ? 'Keyword density quá thấp (' . $keywordDensity . '%)' : 'Keyword density quá cao (' . $keywordDensity . '%)'; }

        if (str_contains(strtolower($metaTitle), $keyword)) { $score += 10; }
        else { $suggestions[] = 'Thêm keyword vào meta title'; }

        if (str_contains(strtolower($metaDesc), $keyword)) { $score += 10; }
        else { $suggestions[] = 'Thêm keyword vào meta description'; }

        // Content length
        if ($wordCount >= 300) { $score += 15; }
        else { $issues[] = "Nội dung quá ngắn ({$wordCount} từ). Nên từ 300 từ trở lên."; }
        if ($wordCount >= 1000) { $score += 5; }

        // Meta length
        if ($titleLength >= 30 && $titleLength <= 60) { $score += 10; }
        else { $suggestions[] = "Meta title nên từ 30-60 ký tự (hiện tại: {$titleLength})"; }

        if ($descLength >= 120 && $descLength <= 160) { $score += 10; }
        else { $suggestions[] = "Meta description nên từ 120-160 ký tự (hiện tại: {$descLength})"; }

        // Headings
        if (count($headingStructure) >= 2) { $score += 10; }
        else { $suggestions[] = 'Thêm heading H2/H3 để cấu trúc nội dung tốt hơn'; }

        // Readability
        if ($readabilityScore >= 60) { $score += 10; }
        else { $suggestions[] = 'Viết câu ngắn hơn để tăng readability'; }

        // Images
        $imageCount = preg_match_all('/<img/i', $content);
        if ($imageCount >= 1) { $score += 5; }
        else { $suggestions[] = 'Thêm hình ảnh vào nội dung'; }

        // Schema markup suggestion
        $schemaType = 'Article';
        if (preg_match('/FAQ|câu hỏi|hỏi đáp/i', $content)) $schemaType = 'FAQPage';
        if (preg_match('/hướng dẫn|how to|cách/i', $content)) $schemaType = 'HowTo';

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $schemaType,
            'headline' => $metaTitle ?: $validated['focus_keyword'],
            'description' => $metaDesc,
            'wordCount' => $wordCount,
        ];

        return response()->json([
            'success' => true,
            'analysis' => [
                'overall_score' => min(100, $score),
                'word_count' => $wordCount,
                'keyword_density' => $keywordDensity,
                'readability_score' => $readabilityScore,
                'heading_structure' => $headingStructure,
                'meta_title_length' => $titleLength,
                'meta_description_length' => $descLength,
                'image_count' => $imageCount,
                'issues' => $issues,
                'suggestions' => $suggestions,
                'schema_markup' => $schema,
                'internal_link_suggestions' => [
                    'Trang dịch vụ liên quan',
                    'Case studies phù hợp',
                    'Bài blog tương tự',
                ],
            ],
        ]);
    }
}
