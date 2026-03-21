<?php

namespace App\Services\AI;

use App\Contracts\AiProviderInterface;
use App\Models\AiProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * AiGateway
 * ─────────
 * The single entry point for ALL AI operations in the application.
 *
 * Supports 5 providers: Gemini, OpenAI, Claude, DeepSeek, Groq.
 * Loads configuration from both .env and database (ai_providers table).
 *
 * Usage:
 *   $ai = app(AiGateway::class);
 *
 *   // Use default provider
 *   $result = $ai->chat('Analyze this...');
 *
 *   // Use specific provider
 *   $result = $ai->driver('gemini')->chat('...');
 *
 *   // With fallback chain
 *   $result = $ai->withFallback(['gemini', 'openai', 'claude'])->chat('...');
 *
 *   // Embedding
 *   $vector = $ai->embed('Text to embed');
 */
class AiGateway
{
    /** @var array<string, AiProviderInterface> */
    private array $providers = [];

    /** Default provider name. */
    private string $defaultDriver;

    /** Fallback chain (provider names in priority order). */
    private array $fallbackChain = [];

    /** Whether DB config has been loaded. */
    private bool $dbLoaded = false;

    public function __construct()
    {
        $this->defaultDriver = config('services.ai.default', 'gemini');

        // Register all providers from .env config
        $this->registerProvider('gemini', new GeminiProvider());
        $this->registerProvider('openai', new OpenAIProvider());
        $this->registerProvider('claude', new ClaudeProvider());
        $this->registerProvider('deepseek', new DeepSeekProvider());
        $this->registerProvider('groq', new GroqProvider());
    }

    /**
     * Load provider configs from database (ai_providers table).
     * This overrides .env config with DB values for the current account.
     */
    public function loadFromDatabase(?int $accountId = null): self
    {
        if ($this->dbLoaded) return $this;

        try {
            $accountId = $accountId ?? Auth::user()?->account_id;
            if (!$accountId) return $this;

            $dbProviders = AiProvider::forAccount($accountId)->active()->get();

            foreach ($dbProviders as $dbProvider) {
                $slug = $dbProvider->slug;

                if (!$dbProvider->has_api_key) continue;

                // Create provider with DB-stored API key and model
                $provider = $this->createProviderFromDb($dbProvider);

                if ($provider) {
                    $this->providers[$slug] = $provider;
                }

                // If marked as default, update gateway default
                if ($dbProvider->is_default) {
                    $this->defaultDriver = $slug;
                }
            }

            $this->dbLoaded = true;
        } catch (\Exception $e) {
            // DB might not have the table yet (before migration)
            Log::debug('AiGateway: Could not load from DB', ['error' => $e->getMessage()]);
        }

        return $this;
    }

    /**
     * Create a provider instance from a DB record.
     */
    private function createProviderFromDb(AiProvider $record): ?AiProviderInterface
    {
        $apiKey = $record->api_key;
        $model = $record->model;
        $config = $record->config ?? [];

        return match ($record->slug) {
            'gemini' => new GeminiProvider($apiKey, $model),
            'openai' => new OpenAIProvider($apiKey, $model),
            'claude' => new ClaudeProvider($apiKey, $model),
            'deepseek' => new DeepSeekProvider($apiKey, $model, $config['base_url'] ?? null),
            'groq' => new GroqProvider($apiKey, $model, $config['base_url'] ?? null),
            default => null,
        };
    }

    /**
     * Register a provider.
     */
    public function registerProvider(string $name, AiProviderInterface $provider): self
    {
        $this->providers[$name] = $provider;
        return $this;
    }

    /**
     * Get a specific provider (driver).
     */
    public function driver(string $name): AiProviderInterface
    {
        if (!isset($this->providers[$name])) {
            throw new \InvalidArgumentException("AI provider '{$name}' is not registered.");
        }

        return $this->providers[$name];
    }

    /**
     * Set fallback chain and return self for chaining.
     */
    public function withFallback(array $providerNames): self
    {
        $clone = clone $this;
        $clone->fallbackChain = $providerNames;
        return $clone;
    }

    /**
     * Send a chat request using default provider or fallback chain.
     */
    public function chat(string $prompt, array $options = []): array
    {
        $this->loadFromDatabase();

        if (!empty($this->fallbackChain)) {
            return $this->chatWithFallback($prompt, $options);
        }

        $provider = $this->resolveDefaultProvider();

        if (!$provider) {
            throw new \RuntimeException('No AI provider is configured. Go to Settings > AI Providers to add one.');
        }

        $result = $provider->chat($prompt, $options);

        $this->trackUsage($provider->name(), $result['metadata']['tokens_used'] ?? 0);

        return $result;
    }

    /**
     * Generate embeddings using the best available provider.
     */
    public function embed(string $text, array $options = []): ?array
    {
        $this->loadFromDatabase();

        // Priority for embeddings: openai > gemini
        $embeddingProviders = ['openai', 'gemini'];

        foreach ($embeddingProviders as $name) {
            $provider = $this->providers[$name] ?? null;
            if ($provider && $provider->isConfigured()) {
                $result = $provider->embed($text, $options);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        Log::warning('AiGateway: No embedding provider available');
        return null;
    }

    /**
     * Chat with fallback chain.
     */
    private function chatWithFallback(string $prompt, array $options): array
    {
        $lastError = null;

        foreach ($this->fallbackChain as $name) {
            $provider = $this->providers[$name] ?? null;

            if (!$provider || !$provider->isConfigured()) {
                continue;
            }

            try {
                $result = $provider->chat($prompt, $options);

                Log::info("AiGateway: Used provider '{$name}' successfully");
                $this->trackUsage($name, $result['metadata']['tokens_used'] ?? 0);

                return $result;
            } catch (\Exception $e) {
                Log::warning("AiGateway: Provider '{$name}' failed, trying next", [
                    'error' => $e->getMessage(),
                ]);
                $lastError = $e;
            }
        }

        if ($lastError) {
            throw new \RuntimeException("All AI providers failed. Last error: {$lastError->getMessage()}");
        }

        throw new \RuntimeException('No configured AI provider in fallback chain');
    }

    /**
     * Resolve the default provider.
     */
    private function resolveDefaultProvider(): ?AiProviderInterface
    {
        $provider = $this->providers[$this->defaultDriver] ?? null;
        if ($provider && $provider->isConfigured()) {
            return $provider;
        }

        foreach ($this->providers as $name => $p) {
            if ($p->isConfigured()) {
                Log::info("AiGateway: Default '{$this->defaultDriver}' unavailable, using '{$name}'");
                return $p;
            }
        }

        return null;
    }

    /**
     * Track usage in database.
     */
    private function trackUsage(string $providerName, int $tokensUsed): void
    {
        try {
            $accountId = Auth::user()?->account_id;
            if (!$accountId) return;

            AiProvider::where('account_id', $accountId)
                ->where('slug', $providerName)
                ->first()
                ?->recordRequest($tokensUsed);
        } catch (\Exception $e) {
            // Silently fail — usage tracking shouldn't break the main flow
        }
    }

    // ── Info Methods ──

    public function availableProviders(): array
    {
        return array_keys($this->providers);
    }

    public function configuredProviders(): array
    {
        $this->loadFromDatabase();
        return array_keys(array_filter(
            $this->providers,
            fn (AiProviderInterface $p) => $p->isConfigured()
        ));
    }

    public function availableModels(): array
    {
        $models = [];
        foreach ($this->providers as $name => $provider) {
            if ($provider->isConfigured()) {
                $models[$name] = $provider->models();
            }
        }
        return $models;
    }

    public function getDefaultDriver(): string
    {
        return $this->defaultDriver;
    }
}
