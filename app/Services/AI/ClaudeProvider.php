<?php

namespace App\Services\AI;

use App\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ClaudeProvider
 * ──────────────
 * Anthropic Claude API provider implementation.
 */
class ClaudeProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $defaultModel;
    private string $baseUrl = 'https://api.anthropic.com/v1';

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.anthropic.api_key', '');
        $this->defaultModel = $model ?? (string) config('services.anthropic.model', 'claude-sonnet-4-20250514');
    }

    public function chat(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 4096;
        $systemPrompt = $options['system_prompt'] ?? 'You are a helpful assistant.';

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout($options['timeout'] ?? 60)->post("{$this->baseUrl}/messages", [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if (!$response->successful()) {
                Log::error('ClaudeProvider: API error', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);
                throw new \RuntimeException("Claude API error: {$response->status()}");
            }

            $data = $response->json();
            $content = $data['content'][0]['text'] ?? '';

            return [
                'content' => trim($content),
                'metadata' => [
                    'model' => $model,
                    'provider' => 'claude',
                    'tokens_used' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
                    'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
                    'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
                ],
            ];
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('ClaudeProvider: Exception', ['message' => $e->getMessage()]);
            throw new \RuntimeException("Claude request failed: {$e->getMessage()}");
        }
    }

    public function embed(string $text, array $options = []): ?array
    {
        // Claude doesn't support embeddings natively.
        // Fall back to null — AiGateway can delegate to another provider.
        return null;
    }

    public function name(): string
    {
        return 'claude';
    }

    public function models(): array
    {
        return [
            'claude-sonnet-4-20250514' => 'Claude Sonnet 4 (Balanced)',
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku (Fast)',
        ];
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
