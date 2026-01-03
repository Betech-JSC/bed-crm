<?php

namespace App\Services;

use App\Contracts\AIContentServiceInterface;
use App\Models\Account;
use App\Models\ContentItem;
use App\Models\ContentTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ContentManagementService
{
    private AIContentServiceInterface $aiService;

    public function __construct(AIContentServiceInterface $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate content using a template.
     */
    public function generateFromTemplate(
        ContentTemplate $template,
        array $variables = [],
        array $options = []
    ): ContentItem {
        $prompt = $template->prompt_template;
        $settings = array_merge($template->settings ?? [], $options);

        $result = $this->aiService->generateContent($prompt, $variables, $settings);

        $contentItem = ContentItem::create([
            'account_id' => $template->account_id,
            'created_by' => auth()->id(),
            'template_id' => $template->id,
            'type' => $options['type'] ?? 'text',
            'title' => $options['title'] ?? null,
            'content' => $result['content'],
            'metadata' => $options['metadata'] ?? [],
            'ai_model' => $result['metadata']['model'] ?? null,
            'ai_metadata' => $result['metadata'],
            'status' => ContentItem::STATUS_DRAFT,
            'tags' => $options['tags'] ?? [],
        ]);

        // Update template usage count
        $template->increment('usage_count');

        return $contentItem;
    }

    /**
     * Generate content variations.
     */
    public function generateVariations(
        ContentTemplate $template,
        array $variables = [],
        int $count = 3,
        array $options = []
    ): array {
        $prompt = $template->prompt_template;
        $settings = array_merge($template->settings ?? [], $options);

        $variations = $this->aiService->generateVariations($prompt, $variables, $count, $settings);

        $contentItems = [];
        foreach ($variations as $index => $variation) {
            $contentItems[] = ContentItem::create([
                'account_id' => $template->account_id,
                'created_by' => auth()->id(),
                'template_id' => $template->id,
                'type' => $options['type'] ?? 'text',
                'title' => ($options['title'] ?? 'Variation') . ' ' . ($index + 1),
                'content' => $variation['content'],
                'metadata' => $options['metadata'] ?? [],
                'ai_model' => $variation['metadata']['model'] ?? null,
                'ai_metadata' => $variation['metadata'],
                'status' => ContentItem::STATUS_DRAFT,
                'tags' => $options['tags'] ?? [],
            ]);
        }

        $template->increment('usage_count');

        return $contentItems;
    }

    /**
     * Optimize content for a specific platform.
     */
    public function optimizeForPlatform(ContentItem $contentItem, string $platform, array $options = []): ContentItem
    {
        $result = $this->aiService->optimizeForPlatform($contentItem->content, $platform, $options);

        // Create optimized version
        $optimized = $contentItem->replicate();
        $optimized->content = $result['content'];
        $optimized->metadata = array_merge($contentItem->metadata ?? [], [
            'optimized_for' => $platform,
            'original_content_id' => $contentItem->id,
        ]);
        $optimized->ai_metadata = $result['metadata'];
        $optimized->save();

        return $optimized;
    }

    /**
     * Batch generate content for multiple templates.
     */
    public function batchGenerate(array $templateIds, array $variables = [], array $options = []): array
    {
        $templates = ContentTemplate::whereIn('id', $templateIds)
            ->where('account_id', auth()->user()->account_id)
            ->get();

        $results = [];
        foreach ($templates as $template) {
            try {
                $contentItem = $this->generateFromTemplate($template, $variables, $options);
                $results[] = $contentItem;
            } catch (\Exception $e) {
                Log::error('Content generation failed', [
                    'template_id' => $template->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}

