<?php

namespace App\Services\AI;

use App\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAIProvider
 * ──────────────
 * OpenAI / GPT API provider implementation.
 */
class OpenAIProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $defaultModel;
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.openai.api_key', '');
        $this->defaultModel = $model ?? (string) config('services.openai.model', 'gpt-4o-mini');
    }

    public function chat(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 4096;
        $systemPrompt = $options['system_prompt'] ?? 'You are a helpful assistant.';

        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        if (($options['response_format'] ?? 'text') === 'json') {
            $body['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout($options['timeout'] ?? 60)->post("{$this->baseUrl}/chat/completions", $body);

            if (!$response->successful()) {
                Log::error('OpenAIProvider: API error', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);
                throw new \RuntimeException("OpenAI API error: {$response->status()}");
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            return [
                'content' => trim($content),
                'metadata' => [
                    'model' => $model,
                    'provider' => 'openai',
                    'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                    'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                ],
            ];
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('OpenAIProvider: Exception', ['message' => $e->getMessage()]);
            throw new \RuntimeException("OpenAI request failed: {$e->getMessage()}");
        }
    }

    public function embed(string $text, array $options = []): ?array
    {
        $model = $options['model'] ?? 'text-embedding-3-small';

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->post("{$this->baseUrl}/embeddings", [
                'model' => $model,
                'input' => $text,
            ]);

            if (!$response->successful()) {
                Log::error('OpenAIProvider: Embedding error', ['status' => $response->status()]);
                return null;
            }

            return $response->json('data.0.embedding');
        } catch (\Exception $e) {
            Log::error('OpenAIProvider: Embedding exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function name(): string
    {
        return 'openai';
    }

    public function models(): array
    {
        return [
            'gpt-4o' => 'GPT-4o (Most capable)',
            'gpt-4o-mini' => 'GPT-4o Mini (Fast & affordable)',
            'gpt-4-turbo' => 'GPT-4 Turbo',
        ];
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
