<?php

namespace App\Services\AI;

use App\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DeepSeekProvider
 * ────────────────
 * DeepSeek API provider (OpenAI-compatible endpoint).
 */
class DeepSeekProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $defaultModel;
    private string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $model = null, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.deepseek.api_key', '');
        $this->defaultModel = $model ?? 'deepseek-chat';
        $this->baseUrl = $baseUrl ?? 'https://api.deepseek.com/v1';
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
                throw new \RuntimeException("DeepSeek API error: {$response->status()}");
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            return [
                'content' => trim($content),
                'metadata' => [
                    'model' => $model,
                    'provider' => 'deepseek',
                    'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                    'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                ],
            ];
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \RuntimeException("DeepSeek request failed: {$e->getMessage()}");
        }
    }

    public function embed(string $text, array $options = []): ?array
    {
        return null; // DeepSeek doesn't support embeddings
    }

    public function name(): string { return 'deepseek'; }

    public function models(): array
    {
        return [
            'deepseek-chat' => 'DeepSeek Chat (V3)',
            'deepseek-reasoner' => 'DeepSeek Reasoner (R1)',
        ];
    }

    public function isConfigured(): bool { return !empty($this->apiKey); }
}
