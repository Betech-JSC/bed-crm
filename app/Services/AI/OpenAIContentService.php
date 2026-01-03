<?php

namespace App\Services\AI;

use App\Contracts\AIContentServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIContentService implements AIContentServiceInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';
    private string $defaultModel = 'gpt-4';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', '');
    }

    public function generateContent(string $prompt, array $variables = [], array $options = []): array
    {
        // Replace variables in prompt
        $processedPrompt = $this->processPrompt($prompt, $variables);
        
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 1000;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post("{$this->baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $options['system_prompt'] ?? 'You are a professional content writer specializing in social media and marketing content.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $processedPrompt,
                    ],
                ],
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

            if (!$response->successful()) {
                throw new \Exception('OpenAI API error: ' . $response->body());
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            return [
                'content' => trim($content),
                'metadata' => [
                    'model' => $model,
                    'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                    'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                    'service' => 'openai',
                ],
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI content generation failed', [
                'error' => $e->getMessage(),
                'prompt' => substr($processedPrompt, 0, 100),
            ]);
            throw $e;
        }
    }

    public function generateVariations(string $prompt, array $variables = [], int $count = 3, array $options = []): array
    {
        $variations = [];
        
        for ($i = 0; $i < $count; $i++) {
            $variationOptions = array_merge($options, [
                'temperature' => ($options['temperature'] ?? 0.7) + ($i * 0.1), // Vary creativity
            ]);
            
            $result = $this->generateContent($prompt, $variables, $variationOptions);
            $variations[] = $result;
        }

        return $variations;
    }

    public function optimizeForPlatform(string $content, string $platform, array $options = []): array
    {
        $platformPrompts = [
            'twitter' => 'Rewrite this content for Twitter. Keep it concise, engaging, and under 280 characters. Use appropriate hashtags and mentions.',
            'linkedin' => 'Rewrite this content for LinkedIn. Make it professional, thought-provoking, and suitable for B2B audience. Include relevant industry insights.',
            'facebook' => 'Rewrite this content for Facebook. Make it engaging, conversational, and encourage interaction. Use emojis appropriately.',
            'instagram' => 'Rewrite this content for Instagram. Make it visually appealing, use relevant hashtags, and encourage engagement.',
        ];

        $prompt = ($platformPrompts[$platform] ?? 'Optimize this content for ' . $platform . '.') . "\n\nContent:\n" . $content;

        return $this->generateContent($prompt, [], $options);
    }

    public function getAvailableModels(): array
    {
        return [
            'gpt-4' => 'GPT-4 (Most capable)',
            'gpt-4-turbo' => 'GPT-4 Turbo (Faster)',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Fast & Cost-effective)',
        ];
    }

    public function getServiceName(): string
    {
        return 'openai';
    }

    /**
     * Process prompt template with variables.
     */
    private function processPrompt(string $prompt, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $prompt = str_replace('{{' . $key . '}}', $value, $prompt);
            $prompt = str_replace('{' . $key . '}', $value, $prompt);
        }
        return $prompt;
    }
}

