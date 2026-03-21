<?php

namespace App\Services;

use App\Models\ContentItem;
use App\Models\ContentTemplate;
use App\Services\AI\AiGateway;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * AiContentStudioService
 * ──────────────────────
 * Generates social media content (text + thumbnail image) via AI,
 * then creates ContentItem + SocialPost records for publishing.
 */
class AiContentStudioService
{
    public function __construct(
        private AiGateway $ai,
        private SocialPostingService $postingService,
    ) {}

    /**
     * Generate content from a prompt configuration.
     */
    public function generateContent(array $config): array
    {
        $topic = $config['topic'] ?? '';
        $tone = $config['tone'] ?? 'professional';
        $language = $config['language'] ?? 'vi';
        $platforms = $config['platforms'] ?? ['facebook'];
        $contentType = $config['content_type'] ?? 'post';
        $additionalInstructions = $config['instructions'] ?? '';
        $generateThumbnail = $config['generate_thumbnail'] ?? true;
        $hashtags = $config['hashtags'] ?? true;

        // Build the AI prompt
        $prompt = $this->buildContentPrompt($topic, $tone, $language, $platforms, $contentType, $additionalInstructions, $hashtags);

        // Generate text content via AI
        $result = $this->ai->withFallback(['gemini', 'openai', 'claude'])->chat($prompt, [
            'temperature' => 0.8,
            'max_tokens' => 4096,
        ]);

        $rawContent = $result['content'] ?? '';

        // Parse multi-platform content
        $parsedContent = $this->parseMultiPlatformContent($rawContent, $platforms);

        // Generate thumbnail if requested
        $thumbnailUrl = null;
        if ($generateThumbnail) {
            $thumbnailUrl = $this->generateThumbnail($topic, $config['thumbnail_style'] ?? 'modern');
        }

        return [
            'contents' => $parsedContent,
            'thumbnail_url' => $thumbnailUrl,
            'ai_model' => $result['metadata']['model'] ?? 'unknown',
            'tokens_used' => $result['metadata']['tokens_used'] ?? 0,
        ];
    }

    /**
     * Build the content generation prompt.
     */
    private function buildContentPrompt(
        string $topic,
        string $tone,
        string $language,
        array $platforms,
        string $contentType,
        string $additionalInstructions,
        bool $hashtags
    ): string {
        $langName = $language === 'vi' ? 'Tiếng Việt' : 'English';
        $platformList = implode(', ', array_map('ucfirst', $platforms));

        $toneGuide = match ($tone) {
            'professional' => 'chuyên nghiệp, đáng tin cậy, chuyên gia',
            'casual' => 'thân thiện, gần gũi, dễ hiểu',
            'humorous' => 'hài hước, dí dỏm, vui vẻ',
            'inspirational' => 'truyền cảm hứng, động lực, tích cực',
            'educational' => 'giáo dục, thông tin, hướng dẫn',
            'storytelling' => 'kể chuyện, cuốn hút, có cách dẫn dắt hay',
            default => 'chuyên nghiệp',
        };

        $contentTypeGuide = match ($contentType) {
            'post' => 'Bài đăng social media thông thường',
            'article' => 'Bài viết dài, chuyên sâu (800-1500 từ)',
            'announcement' => 'Thông báo chính thức, ngắn gọn',
            'promotion' => 'Bài quảng cáo sản phẩm/dịch vụ',
            'tips' => 'Bài chia sẻ mẹo, hướng dẫn',
            'news' => 'Bài tin tức, cập nhật',
            default => 'Bài đăng social media',
        };

        $prompt = "Bạn là một Content Creator chuyên nghiệp. Hãy tạo nội dung bài đăng social media với yêu cầu sau:\n\n";
        $prompt .= "📌 CHỦ ĐỀ: {$topic}\n";
        $prompt .= "🎯 LOẠI CONTENT: {$contentTypeGuide}\n";
        $prompt .= "🗣️ TONE: {$toneGuide}\n";
        $prompt .= "🌐 NGÔN NGỮ: {$langName}\n";
        $prompt .= "📱 NỀN TẢNG: {$platformList}\n\n";

        if ($additionalInstructions) {
            $prompt .= "📝 YÊU CẦU BỔ SUNG: {$additionalInstructions}\n\n";
        }

        $prompt .= "QUAN TRỌNG - HÃY TẠO NỘI DUNG RIÊNG CHO TỪNG NỀN TẢNG:\n\n";

        foreach ($platforms as $platform) {
            $charLimit = match ($platform) {
                'twitter' => '280 ký tự',
                'linkedin' => '3000 ký tự',
                'facebook' => '2000 ký tự',
                'instagram' => '2200 ký tự',
                default => '2000 ký tự',
            };

            $prompt .= "--- {$platform} ---\n";
            $prompt .= "Giới hạn: {$charLimit}\n";

            if ($platform === 'instagram') {
                $prompt .= "Chú ý: Instagram cần caption hấp dẫn, nhiều emoji, hashtags nổi bật\n";
            } elseif ($platform === 'linkedin') {
                $prompt .= "Chú ý: LinkedIn cần chuyên nghiệp, insight industry, call-to-action\n";
            } elseif ($platform === 'twitter') {
                $prompt .= "Chú ý: Twitter ngắn gọn, viral, hook mạnh, 1-2 hashtags\n";
            } elseif ($platform === 'facebook') {
                $prompt .= "Chú ý: Facebook cần engagement cao, hỏi opinion, visual text\n";
            }
        }

        if ($hashtags) {
            $prompt .= "\n🏷️ Thêm hashtags phù hợp cho từng nền tảng.\n";
        }

        $prompt .= "\n📋 ĐỊNH DẠNG OUTPUT:\n";
        $prompt .= "Trả về dưới dạng JSON với format:\n";
        $prompt .= "```json\n";
        $prompt .= '{"platforms": {' . "\n";
        foreach ($platforms as $i => $platform) {
            $comma = $i < count($platforms) - 1 ? ',' : '';
            $prompt .= "  \"{$platform}\": {\"content\": \"nội dung bài đăng\", \"hashtags\": [\"tag1\", \"tag2\"]}{$comma}\n";
        }
        $prompt .= "}}\n";
        $prompt .= "```\n";
        $prompt .= "CHỈ TRẢ VỀ JSON, KHÔNG CÓ TEXT KHÁC.";

        return $prompt;
    }

    /**
     * Parse AI response into multi-platform content.
     */
    private function parseMultiPlatformContent(string $rawContent, array $platforms): array
    {
        // Try to extract JSON from the response
        $jsonMatch = [];
        if (preg_match('/```json\s*(.*?)\s*```/s', $rawContent, $jsonMatch)) {
            $rawContent = $jsonMatch[1];
        }

        // Try decoding as JSON
        $decoded = json_decode($rawContent, true);

        if ($decoded && isset($decoded['platforms'])) {
            return $decoded['platforms'];
        }

        // Fallback: Use the raw content for all platforms
        $contents = [];
        foreach ($platforms as $platform) {
            $contents[$platform] = [
                'content' => $rawContent,
                'hashtags' => [],
            ];
        }

        return $contents;
    }

    /**
     * Generate thumbnail image using OpenAI DALL-E.
     */
    public function generateThumbnail(string $topic, string $style = 'modern'): ?string
    {
        $styleGuide = match ($style) {
            'modern' => 'modern minimalist design with vibrant gradients',
            'corporate' => 'clean corporate style with professional look',
            'creative' => 'creative artistic design with bold colors',
            'tech' => 'futuristic tech style with neon accents',
            'nature' => 'natural organic colors with soft lighting',
            'bold' => 'bold typography with striking contrasts',
            default => 'modern clean design',
        };

        $imagePrompt = "Create a social media thumbnail image for the topic: '{$topic}'. ";
        $imagePrompt .= "Style: {$styleGuide}. ";
        $imagePrompt .= "The image should be eye-catching, professional, and suitable for social media. ";
        $imagePrompt .= "No text in the image. 16:9 aspect ratio. High quality.";

        try {
            $apiKey = config('services.openai.api_key') ?: env('OPENAI_API_KEY');

            if (!$apiKey) {
                Log::warning('AiContentStudio: No OpenAI API key for image generation');
                return null;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->timeout(60)->post('https://api.openai.com/v1/images/generations', [
                'model' => 'dall-e-3',
                'prompt' => $imagePrompt,
                'n' => 1,
                'size' => '1792x1024', // 16:9-ish
                'quality' => 'standard',
            ]);

            if ($response->successful()) {
                $imageUrl = $response->json('data.0.url');

                if ($imageUrl) {
                    return $this->downloadAndStoreImage($imageUrl, $topic);
                }
            }

            Log::warning('AiContentStudio: Image generation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('AiContentStudio: Image generation error', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Download generated image and store locally.
     */
    private function downloadAndStoreImage(string $url, string $topic): ?string
    {
        try {
            $response = Http::get($url);

            if ($response->successful()) {
                $filename = 'thumbnails/' . date('Y/m') . '/' . Str::slug($topic) . '-' . Str::random(8) . '.png';
                Storage::disk('public')->put($filename, $response->body());
                return Storage::disk('public')->url($filename);
            }
        } catch (\Exception $e) {
            Log::error('AiContentStudio: Image download failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Create ContentItem and optional SocialPosts from generated content.
     */
    public function createContentAndPosts(array $generatedContent, array $config): array
    {
        $accountId = Auth::user()->account_id;
        $userId = Auth::id();
        $results = [];

        // Create ContentItem for each platform
        foreach ($generatedContent['contents'] as $platform => $data) {
            $content = is_array($data) ? ($data['content'] ?? '') : $data;
            $hashtags = is_array($data) ? ($data['hashtags'] ?? []) : [];

            $contentItem = ContentItem::create([
                'account_id' => $accountId,
                'created_by' => $userId,
                'type' => ContentItem::TYPE_TEXT,
                'title' => $config['topic'] . ' (' . ucfirst($platform) . ')',
                'content' => $content,
                'metadata' => [
                    'media_urls' => $generatedContent['thumbnail_url'] ? [$generatedContent['thumbnail_url']] : [],
                    'platform' => $platform,
                    'hashtags' => $hashtags,
                    'topic' => $config['topic'],
                    'tone' => $config['tone'] ?? 'professional',
                ],
                'ai_model' => $generatedContent['ai_model'],
                'ai_metadata' => [
                    'tokens_used' => $generatedContent['tokens_used'],
                    'generated_at' => now()->toISOString(),
                ],
                'status' => ContentItem::STATUS_DRAFT,
                'tags' => $hashtags,
            ]);

            $results[$platform] = [
                'content_item' => $contentItem,
                'social_post' => null,
            ];
        }

        return $results;
    }

    /**
     * Publish or schedule generated content to social platforms.
     */
    public function publishContent(array $contentItems, array $socialAccountIds, ?\DateTime $scheduledAt = null): array
    {
        $results = [];

        foreach ($contentItems as $platform => $data) {
            $contentItem = $data['content_item'];

            // Find matching social account for this platform
            $matchingAccounts = collect($socialAccountIds)
                ->filter(fn ($id) => \App\Models\SocialAccount::find($id)?->platform === $platform)
                ->values()
                ->all();

            if (empty($matchingAccounts)) continue;

            $posts = $this->postingService->publishToMultiplePlatforms(
                $contentItem,
                $matchingAccounts,
                $scheduledAt
            );

            $results[$platform] = [
                'content_item_id' => $contentItem->id,
                'posts' => collect($posts)->map(fn ($p) => [
                    'id' => $p->id,
                    'status' => $p->status,
                    'scheduled_at' => $p->scheduled_at?->toISOString(),
                ])->all(),
            ];
        }

        return $results;
    }
}
