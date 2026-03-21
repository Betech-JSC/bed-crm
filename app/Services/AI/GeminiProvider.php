<?php

namespace App\Services\AI;

use App\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeminiProvider
 * ──────────────
 * Google Gemini API provider implementation.
 */
class GeminiProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $defaultModel;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.gemini.api_key', '');
        $this->defaultModel = $model ?? (string) config('services.gemini.model', 'gemini-2.0-flash');
    }

    public function chat(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 4096;
        $systemPrompt = $options['system_prompt'] ?? null;

        $url = "{$this->baseUrl}/{$model}:generateContent?key={$this->apiKey}";

        // Build contents
        $contents = [];

        // Gemini uses systemInstruction separately
        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
            ],
        ];

        if ($systemPrompt) {
            $body['systemInstruction'] = [
                'parts' => [['text' => $systemPrompt]],
            ];
        }

        // If JSON format requested
        if (($options['response_format'] ?? 'text') === 'json') {
            $body['generationConfig']['responseMimeType'] = 'application/json';
        }

        try {
            $response = Http::timeout($options['timeout'] ?? 60)->post($url, $body);

            if (!$response->successful()) {
                Log::error('GeminiProvider: API error', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);
                throw new \RuntimeException("Gemini API error: {$response->status()}");
            }

            $data = $response->json();
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $tokensUsed = $data['usageMetadata']['totalTokenCount'] ?? 0;

            return [
                'content' => trim($content),
                'metadata' => [
                    'model' => $model,
                    'provider' => 'gemini',
                    'tokens_used' => $tokensUsed,
                    'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
                    'completion_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
                ],
            ];
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('GeminiProvider: Exception', ['message' => $e->getMessage()]);
            throw new \RuntimeException("Gemini request failed: {$e->getMessage()}");
        }
    }

    public function embed(string $text, array $options = []): ?array
    {
        $model = $options['model'] ?? 'text-embedding-004';
        $url = "{$this->baseUrl}/{$model}:embedContent?key={$this->apiKey}";

        try {
            $response = Http::timeout(30)->post($url, [
                'model' => "models/{$model}",
                'content' => [
                    'parts' => [['text' => $text]],
                ],
            ]);

            if (!$response->successful()) {
                Log::error('GeminiProvider: Embedding error', ['status' => $response->status()]);
                return null;
            }

            return $response->json('embedding.values');
        } catch (\Exception $e) {
            Log::error('GeminiProvider: Embedding exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function name(): string
    {
        return 'gemini';
    }

    public function models(): array
    {
        return [
            'gemini-2.0-flash' => 'Gemini 2.0 Flash (Balanced)',
            'gemini-2.0-flash-lite' => 'Gemini 2.0 Flash Lite (Fast)',
            'gemini-2.5-pro-preview-05-06' => 'Gemini 2.5 Pro Preview (Most capable)',
        ];
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
