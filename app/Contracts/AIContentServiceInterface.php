<?php

namespace App\Contracts;

interface AIContentServiceInterface
{
    /**
     * Generate content based on prompt and parameters.
     *
     * @param string $prompt The prompt/template with placeholders
     * @param array $variables Variables to replace in prompt
     * @param array $options Additional options (model, temperature, max_tokens, etc.)
     * @return array ['content' => string, 'metadata' => array]
     */
    public function generateContent(string $prompt, array $variables = [], array $options = []): array;

    /**
     * Generate multiple content variations.
     *
     * @param string $prompt
     * @param array $variables
     * @param int $count Number of variations
     * @param array $options
     * @return array Array of generated content
     */
    public function generateVariations(string $prompt, array $variables = [], int $count = 3, array $options = []): array;

    /**
     * Optimize content for specific platform.
     *
     * @param string $content Original content
     * @param string $platform Target platform
     * @param array $options Platform-specific options
     * @return array ['content' => string, 'metadata' => array]
     */
    public function optimizeForPlatform(string $content, string $platform, array $options = []): array;

    /**
     * Get available AI models.
     *
     * @return array
     */
    public function getAvailableModels(): array;

    /**
     * Get service name/identifier.
     *
     * @return string
     */
    public function getServiceName(): string;
}

