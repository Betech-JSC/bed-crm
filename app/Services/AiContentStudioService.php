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
        $json = $this->extractJson($rawContent);

        if ($json) {
            // Try known structures
            if (isset($json['platforms'])) return $json['platforms'];
            if (isset($json['contents'])) return $json['contents'];

            // Direct platform keys
            $found = [];
            foreach ($platforms as $p) {
                if (isset($json[$p])) $found[$p] = $json[$p];
            }
            if (!empty($found)) return $found;
        }

        // Fallback: Use the raw content for all platforms
        $contents = [];
        foreach ($platforms as $platform) {
            $contents[$platform] = [
                'content' => $this->cleanContent($rawContent),
                'hashtags' => [],
            ];
        }

        return $contents;
    }

    /**
     * Extract JSON from AI response (handles multiple formats).
     */
    private function extractJson(string $raw): ?array
    {
        // Try code block formats: ```json ... ```, ``` ... ```
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $raw, $m)) {
            $raw = $m[1];
        }

        // Try to find raw JSON object
        if (!str_starts_with(trim($raw), '{')) {
            if (preg_match('/\{[\s\S]*\}/', $raw, $m)) {
                $raw = $m[0];
            }
        }

        // Clean: remove control characters, fix common AI mistakes
        $raw = trim($raw);
        $raw = preg_replace('/[\x00-\x1F\x7F]/', '', $raw); // remove control chars except what json_decode handles
        $raw = str_replace(['\\n', '\n'], '\n', $raw); // normalize newlines within strings

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Last resort: try to fix truncated JSON by adding closing brackets
        $fixed = $raw;
        $openBraces = substr_count($fixed, '{') - substr_count($fixed, '}');
        $openBrackets = substr_count($fixed, '[') - substr_count($fixed, ']');
        $fixed .= str_repeat(']', max(0, $openBrackets));
        $fixed .= str_repeat('}', max(0, $openBraces));

        $decoded = json_decode($fixed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return null;
    }

    /**
     * Clean raw AI content of markdown artifacts.
     */
    private function cleanContent(string $content): string
    {
        // Remove ```json blocks
        $content = preg_replace('/```(?:json)?\s*/', '', $content);
        $content = preg_replace('/```/', '', $content);
        return trim($content);
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
            $response = Http::timeout(30)->get($url);

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
     * Generate an inline article image with given prompt.
     */
    public function generateArticleImage(string $topic, string $context = '', string $style = 'modern'): ?string
    {
        $styleGuide = match ($style) {
            'modern' => 'modern minimalist design with vibrant gradients',
            'corporate' => 'clean corporate style with professional look',
            'creative' => 'creative artistic design with bold colors',
            'tech' => 'futuristic tech style with neon accents',
            'infographic' => 'infographic style with data visualization elements',
            'illustration' => 'hand-drawn illustration style, minimal and clean',
            'photo' => 'photorealistic high quality photo',
            default => 'modern clean design',
        };

        $imagePrompt = "Create a blog article illustration image. ";
        $imagePrompt .= "Topic: '{$topic}'. ";
        if ($context) {
            $imagePrompt .= "Context: {$context}. ";
        }
        $imagePrompt .= "Style: {$styleGuide}. ";
        $imagePrompt .= "The image should be suitable for a blog article section. ";
        $imagePrompt .= "No text in the image. Landscape 16:9 aspect ratio. High quality.";

        try {
            $apiKey = config('services.openai.api_key') ?: env('OPENAI_API_KEY');
            if (!$apiKey) return null;

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->timeout(60)->post('https://api.openai.com/v1/images/generations', [
                'model' => 'dall-e-3',
                'prompt' => $imagePrompt,
                'n' => 1,
                'size' => '1792x1024',
                'quality' => 'standard',
            ]);

            if ($response->successful()) {
                $imageUrl = $response->json('data.0.url');
                if ($imageUrl) {
                    return $this->downloadAndStoreImage($imageUrl, $topic . '-article');
                }
            }
        } catch (\Exception $e) {
            Log::error('AiContentStudio: Article image generation error', ['error' => $e->getMessage()]);
        }

        return null;
    }

    // ═══════════════════════════════════════════════════════════
    // SEO ARTICLE GENERATION
    // ═══════════════════════════════════════════════════════════

    /**
     * Generate an SEO-optimized blog article.
     */
    public function generateSeoArticle(array $config): array
    {
        $topic = $config['topic'] ?? '';
        $tone = $config['tone'] ?? 'professional';
        $language = $config['language'] ?? 'vi';
        $articleLength = $config['article_length'] ?? 'medium';
        $focusKeyword = $config['focus_keyword'] ?? '';
        $instructions = $config['instructions'] ?? '';
        $generateThumbnail = $config['generate_thumbnail'] ?? true;

        $prompt = $this->buildSeoPrompt($topic, $tone, $language, $articleLength, $focusKeyword, $instructions);

        $result = $this->ai->withFallback(['gemini', 'openai', 'claude'])->chat($prompt, [
            'temperature' => 0.7,
            'max_tokens' => 8192,
        ]);

        $rawContent = $result['content'] ?? '';
        $parsed = $this->parseSeoArticle($rawContent);

        // Generate thumbnail if requested
        $thumbnailUrl = null;
        if ($generateThumbnail) {
            $thumbnailUrl = $this->generateThumbnail($topic, $config['thumbnail_style'] ?? 'modern');
        }

        return [
            'article' => $parsed,
            'thumbnail_url' => $thumbnailUrl,
            'ai_model' => $result['metadata']['model'] ?? 'unknown',
            'tokens_used' => $result['metadata']['tokens_used'] ?? 0,
        ];
    }

    /**
     * Build the SEO article prompt.
     */
    private function buildSeoPrompt(
        string $topic,
        string $tone,
        string $language,
        string $articleLength,
        string $focusKeyword,
        string $instructions
    ): string {
        $langName = $language === 'vi' ? 'Tiếng Việt' : 'English';

        $toneGuide = match ($tone) {
            'professional' => 'chuyên nghiệp, đáng tin cậy',
            'casual' => 'thân thiện, dễ hiểu',
            'educational' => 'giáo dục, chi tiết, giàu thông tin',
            'storytelling' => 'kể chuyện, cuốn hút',
            default => 'chuyên nghiệp',
        };

        $wordRange = match ($articleLength) {
            'short' => '800-1200 từ',
            'medium' => '1500-2500 từ',
            'long' => '3000-5000 từ',
            'pillar' => '5000-8000 từ (pillar content)',
            default => '1500-2500 từ',
        };

        $prompt = "Bạn là một SEO Content Strategist chuyên nghiệp với 10+ năm kinh nghiệm viết blog SEO. ";
        $prompt .= "Hãy tạo một bài viết blog SEO-optimized hoàn chỉnh.\n\n";

        $prompt .= "📌 CHỦ ĐỀ: {$topic}\n";
        $prompt .= "🔑 FOCUS KEYWORD: " . ($focusKeyword ?: '(tự động chọn phù hợp nhất)') . "\n";
        $prompt .= "🗣️ TONE: {$toneGuide}\n";
        $prompt .= "🌐 NGÔN NGỮ: {$langName}\n";
        $prompt .= "📏 ĐỘ DÀI: {$wordRange}\n\n";

        if ($instructions) {
            $prompt .= "📝 YÊU CẦU BỔ SUNG: {$instructions}\n\n";
        }

        $prompt .= "📋 YÊU CẦU SEO:\n";
        $prompt .= "- Meta title: 50-60 ký tự, chứa focus keyword, hấp dẫn click\n";
        $prompt .= "- Meta description: 150-160 ký tự, tóm lược nội dung, chứa keyword\n";
        $prompt .= "- URL slug: ngắn gọn, chứa keyword, dùng dấu gạch ngang\n";
        $prompt .= "- H1 duy nhất, H2/H3 phân cấp rõ ràng\n";
        $prompt .= "- Focus keyword density 1-2% tự nhiên\n";
        $prompt .= "- Đoạn mở đầu hook mạnh, chứa keyword trong 100 từ đầu\n";
        $prompt .= "- Sử dụng bullet points, numbered lists, bold text\n";
        $prompt .= "- Internal linking placeholders: {{internal_link:anchor text}}\n";
        $prompt .= "- FAQ section (3-5 câu hỏi) cuối bài\n";
        $prompt .= "- CTA (Call to Action) cuối bài\n";
        $prompt .= "- Alt text suggestions cho images\n\n";

        $prompt .= "📋 ĐỊNH DẠNG OUTPUT (JSON):\n";
        $prompt .= "```json\n";
        $prompt .= "{\n";
        $prompt .= "  \"meta_title\": \"SEO title 50-60 chars\",\n";
        $prompt .= "  \"meta_description\": \"SEO description 150-160 chars\",\n";
        $prompt .= "  \"slug\": \"url-slug-here\",\n";
        $prompt .= "  \"focus_keyword\": \"main keyword\",\n";
        $prompt .= "  \"secondary_keywords\": [\"kw1\", \"kw2\", \"kw3\"],\n";
        $prompt .= "  \"content\": \"<h1>Title</h1><p>Full HTML content...</p>\",\n";
        $prompt .= "  \"excerpt\": \"Short excerpt 2-3 sentences\",\n";
        $prompt .= "  \"faq\": [{\"question\": \"Q1?\", \"answer\": \"A1\"}],\n";
        $prompt .= "  \"image_alt_suggestions\": [\"alt text 1\", \"alt text 2\"],\n";
        $prompt .= "  \"internal_links\": [{\"anchor\": \"text\", \"suggested_topic\": \"related topic\"}],\n";
        $prompt .= "  \"word_count\": 2000,\n";
        $prompt .= "  \"reading_time\": 8\n";
        $prompt .= "}\n";
        $prompt .= "```\n";
        $prompt .= "CHỈ TRẢ VỀ JSON, KHÔNG CÓ TEXT KHÁC.";

        return $prompt;
    }

    /**
     * Parse SEO article AI response.
     */
    private function parseSeoArticle(string $rawContent): array
    {
        $json = $this->extractJson($rawContent);

        if ($json && (isset($json['content']) || isset($json['meta_title']))) {
            // Ensure all fields exist
            $defaults = [
                'meta_title' => '', 'meta_description' => '', 'slug' => '',
                'focus_keyword' => '', 'secondary_keywords' => [],
                'content' => '', 'excerpt' => '', 'faq' => [],
                'image_alt_suggestions' => [], 'internal_links' => [],
                'word_count' => 0, 'reading_time' => 0,
            ];

            $article = array_merge($defaults, $json);

            // Auto-calculate word count / reading time if missing
            $plainText = strip_tags($article['content']);
            if (empty($article['word_count'])) {
                $article['word_count'] = str_word_count($plainText);
            }
            if (empty($article['reading_time'])) {
                $article['reading_time'] = max(1, intval($article['word_count'] / 250));
            }

            $article['seo_score'] = $this->calculateSeoScore($article);
            return $article;
        }

        // Fallback
        $plainText = strip_tags($rawContent);
        return [
            'meta_title' => '',
            'meta_description' => '',
            'slug' => '',
            'focus_keyword' => '',
            'secondary_keywords' => [],
            'content' => $this->cleanContent($rawContent),
            'excerpt' => '',
            'faq' => [],
            'image_alt_suggestions' => [],
            'internal_links' => [],
            'word_count' => str_word_count($plainText),
            'reading_time' => max(1, intval(str_word_count($plainText) / 250)),
            'seo_score' => 30,
        ];
    }

    /**
     * Calculate estimated SEO score (0-100).
     */
    private function calculateSeoScore(array $article): int
    {
        $score = 0;
        $keyword = strtolower($article['focus_keyword'] ?? '');
        $content = strtolower(strip_tags($article['content'] ?? ''));
        $metaTitle = strtolower($article['meta_title'] ?? '');
        $metaDesc = strtolower($article['meta_description'] ?? '');

        // Meta title contains keyword (15pts)
        if ($keyword && str_contains($metaTitle, $keyword)) $score += 15;
        // Meta title length (10pts)
        $titleLen = mb_strlen($article['meta_title'] ?? '');
        if ($titleLen >= 50 && $titleLen <= 60) $score += 10;
        elseif ($titleLen >= 40 && $titleLen <= 70) $score += 5;

        // Meta description contains keyword (10pts)
        if ($keyword && str_contains($metaDesc, $keyword)) $score += 10;
        // Meta description length (5pts)
        $descLen = mb_strlen($article['meta_description'] ?? '');
        if ($descLen >= 150 && $descLen <= 160) $score += 5;
        elseif ($descLen >= 120 && $descLen <= 180) $score += 3;

        // Slug contains keyword (5pts)
        if ($keyword && str_contains($article['slug'] ?? '', Str::slug($keyword))) $score += 5;

        // Content length (15pts)
        $wordCount = $article['word_count'] ?? str_word_count($content);
        if ($wordCount >= 1500) $score += 15;
        elseif ($wordCount >= 800) $score += 10;
        elseif ($wordCount >= 300) $score += 5;

        // Keyword density (10pts)
        if ($keyword && $wordCount > 0) {
            $keywordCount = substr_count($content, $keyword);
            $density = ($keywordCount / $wordCount) * 100;
            if ($density >= 0.5 && $density <= 2.5) $score += 10;
            elseif ($density > 0) $score += 5;
        }

        // Has headings H2/H3 (10pts)
        if (preg_match('/<h[23]/i', $article['content'] ?? '')) $score += 10;

        // Has FAQ (5pts)
        if (!empty($article['faq'])) $score += 5;

        // Has internal links (5pts)
        if (!empty($article['internal_links'])) $score += 5;

        // Keyword in first paragraph (5pts)
        if ($keyword) {
            $firstPara = substr($content, 0, 500);
            if (str_contains($firstPara, $keyword)) $score += 5;
        }

        // Has excerpt (5pts)
        if (!empty($article['excerpt'])) $score += 5;

        return min(100, $score);
    }

    /**
     * Save SEO article as ContentItem.
     */
    public function saveSeoArticle(array $articleData, array $config): ContentItem
    {
        $accountId = Auth::user()->account_id;
        $userId = Auth::id();

        return ContentItem::create([
            'account_id' => $accountId,
            'created_by' => $userId,
            'type' => 'seo_article',
            'title' => $articleData['meta_title'] ?? $config['topic'],
            'content' => $articleData['content'] ?? '',
            'metadata' => [
                'media_urls' => !empty($config['thumbnail_url']) ? [$config['thumbnail_url']] : [],
                'platform' => 'website',
                'topic' => $config['topic'],
                'tone' => $config['tone'] ?? 'professional',
                'seo' => [
                    'meta_title' => $articleData['meta_title'] ?? '',
                    'meta_description' => $articleData['meta_description'] ?? '',
                    'slug' => $articleData['slug'] ?? '',
                    'focus_keyword' => $articleData['focus_keyword'] ?? '',
                    'secondary_keywords' => $articleData['secondary_keywords'] ?? [],
                    'excerpt' => $articleData['excerpt'] ?? '',
                    'faq' => $articleData['faq'] ?? [],
                    'seo_score' => $articleData['seo_score'] ?? 0,
                    'word_count' => $articleData['word_count'] ?? 0,
                    'reading_time' => $articleData['reading_time'] ?? 0,
                ],
            ],
            'ai_model' => $config['ai_model'] ?? 'unknown',
            'ai_metadata' => [
                'tokens_used' => $config['tokens_used'] ?? 0,
                'generated_at' => now()->toISOString(),
            ],
            'status' => ContentItem::STATUS_DRAFT,
            'tags' => $articleData['secondary_keywords'] ?? [],
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // SHARED METHODS
    // ═══════════════════════════════════════════════════════════

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
