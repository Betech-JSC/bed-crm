<?php

namespace App\Contracts;

/**
 * AiProviderInterface
 * ────────────────────
 * Every AI provider (Gemini, OpenAI, Claude) implements this.
 * This ensures a unified API across all providers.
 */
interface AiProviderInterface
{
    /**
     * Send a chat/completion request.
     *
     * @param string $prompt   The user prompt
     * @param array  $options  [
     *   'system_prompt' => string,    // System instruction
     *   'temperature'   => float,     // 0.0 – 2.0
     *   'max_tokens'    => int,       // Max output tokens
     *   'model'         => string,    // Override default model
     *   'response_format' => string,  // 'text' | 'json'
     * ]
     * @return array ['content' => string, 'metadata' => array]
     */
    public function chat(string $prompt, array $options = []): array;

    /**
     * Generate text embeddings.
     *
     * @param string $text
     * @param array  $options ['model' => string]
     * @return array|null  Embedding vector or null on failure
     */
    public function embed(string $text, array $options = []): ?array;

    /**
     * Get provider name.
     */
    public function name(): string;

    /**
     * Get available models for this provider.
     */
    public function models(): array;

    /**
     * Check if the provider is configured (has API key).
     */
    public function isConfigured(): bool;
}
