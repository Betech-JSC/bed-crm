<?php

namespace App\Services\AI;

use App\Contracts\AIContentServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeContentService implements AIContentServiceInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.anthropic.com/v1';
    private string $defaultModel = 'claude-3-opus-20240229';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key', '');
    }

    public function generateContent(string $prompt, array $variables = [], array $options = []): array
    {
        $processedPrompt = $this->processPrompt($prompt, $variables);
        
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 1000;

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(60)->post("{$this->baseUrl}/messages", [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'system' => $options['system_prompt'] ?? 'You are a professional content writer specializing in social media and marketing content.',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $processedPrompt,
                    ],
                ],
            ]);

            if (!$response->successful()) {
                throw new \Exception('Anthropic API error: ' . $response->body());
            }

            $data = $response->json();
            $content = $data['content'][0]['text'] ?? '';

            return [
                'content' => trim($content),
                'metadata' => [
                    'model' => $model,
                    'tokens_used' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
                    'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
                    'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
                    'service' => 'claude',
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Claude content generation failed', [
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
                'temperature' => ($options['temperature'] ?? 0.7) + ($i * 0.1),
            ]);
            
            $result = $this->generateContent($prompt, $variables, $variationOptions);
            $variations[] = $result;
        }

        return $variations;
    }

    public function optimizeForPlatform(string $content, string $platform, array $options = []): array
    {
        $platformPrompts = [
            'twitter' => 'Rewrite this content for Twitter. Keep it concise, engaging, and under 280 characters.',
            'linkedin' => 'Rewrite this content for LinkedIn. Make it professional and thought-provoking.',
            'facebook' => 'Rewrite this content for Facebook. Make it engaging and conversational.',
            'instagram' => 'Rewrite this content for Instagram. Make it visually appealing with relevant hashtags.',
        ];

        $prompt = ($platformPrompts[$platform] ?? 'Optimize this content for ' . $platform) . "\n\nContent:\n" . $content;

        return $this->generateContent($prompt, [], $options);
    }

    public function getAvailableModels(): array
    {
        return [
            'claude-3-opus-20240229' => 'Claude 3 Opus (Most capable)',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (Balanced)',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku (Fastest)',
        ];
    }

    public function getServiceName(): string
    {
        return 'claude';
    }

    private function processPrompt(string $prompt, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $prompt = str_replace('{{' . $key . '}}', $value, $prompt);
            $prompt = str_replace('{' . $key . '}', $value, $prompt);
        }
        return $prompt;
    }
}



