<?php

namespace App\Services\AI;

use App\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GroqProvider
 * ────────────
 * Groq API provider (OpenAI-compatible, ultra-fast inference).
 */
class GroqProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $defaultModel;
    private string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $model = null, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.groq.api_key', '');
        $this->defaultModel = $model ?? 'llama-3.3-70b-versatile';
        $this->baseUrl = $baseUrl ?? 'https://api.groq.com/openai/v1';
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
            ])->timeout($options['timeout'] ?? 30)->post("{$this->baseUrl}/chat/completions", $body);

            if (!$response->successful()) {
                throw new \RuntimeException("Groq API error: {$response->status()}");
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            return [
                'content' => trim($content),
                'metadata' => [
                    'model' => $model,
                    'provider' => 'groq',
                    'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                    'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                ],
            ];
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \RuntimeException("Groq request failed: {$e->getMessage()}");
        }
    }

    public function embed(string $text, array $options = []): ?array
    {
        return null; // Groq doesn't natively support embeddings
    }

    public function name(): string { return 'groq'; }

    public function models(): array
    {
        return [
            'llama-3.3-70b-versatile' => 'Llama 3.3 70B (Versatile)',
            'llama-3.1-8b-instant' => 'Llama 3.1 8B (Instant)',
            'mixtral-8x7b-32768' => 'Mixtral 8x7B',
        ];
    }

    public function isConfigured(): bool { return !empty($this->apiKey); }
}
