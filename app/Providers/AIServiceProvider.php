<?php

namespace App\Providers;

use App\Contracts\AIContentServiceInterface;
use App\Contracts\AiProviderInterface;
use App\Services\AI\AiGateway;
use App\Services\AI\AiOrchestrator;
use App\Services\AI\ClaudeContentService;
use App\Services\AI\ClaudeProvider;
use App\Services\AI\ContextBuilder;
use App\Services\AI\GeminiProvider;
use App\Services\AI\MemoryManager;
use App\Services\AI\OpenAIContentService;
use App\Services\AI\OpenAIProvider;
use App\Services\AI\PromptRegistry;
use App\Services\AI\ResponseParser;
use App\Services\AI\ToolRegistry;
use App\Services\AI\Tools\CreateLeadTool;
use App\Services\AI\Tools\DraftEmailTool;
use App\Services\AI\Tools\GetReportTool;
use App\Services\AI\Tools\ScoreLeadTool;
use App\Services\AI\Tools\SearchCrmTool;
use App\Services\AI\Tools\UpdateDealTool;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // ═══════════════════════════════════════
        // AI GATEWAY — Single entry point for AI
        // ═══════════════════════════════════════
        $this->app->singleton(AiGateway::class, function ($app) {
            return new AiGateway();
        });

        $this->app->alias(AiGateway::class, 'ai');

        $this->app->bind(AiProviderInterface::class, function ($app) {
            $gateway = $app->make(AiGateway::class);
            $default = config('services.ai.default', 'gemini');
            return $gateway->driver($default);
        });

        // ═══════════════════════════════════════
        // AI-NATIVE ORCHESTRATOR LAYER
        // ═══════════════════════════════════════
        $this->app->singleton(ContextBuilder::class);
        $this->app->singleton(PromptRegistry::class);
        $this->app->singleton(ResponseParser::class);
        $this->app->singleton(MemoryManager::class);

        $this->app->singleton(ToolRegistry::class, function () {
            $registry = new ToolRegistry();
            $registry->register(new CreateLeadTool());
            $registry->register(new UpdateDealTool());
            $registry->register(new SearchCrmTool());
            $registry->register(new GetReportTool());
            $registry->register(new DraftEmailTool());
            $registry->register(new ScoreLeadTool());
            return $registry;
        });

        $this->app->singleton(AiOrchestrator::class, function ($app) {
            return new AiOrchestrator(
                $app->make(AiGateway::class),
                $app->make(ContextBuilder::class),
                $app->make(PromptRegistry::class),
                $app->make(ResponseParser::class),
                $app->make(ToolRegistry::class),
                $app->make(MemoryManager::class),
            );
        });

        $this->app->alias(AiOrchestrator::class, 'ai.orchestrator');

        // ═══════════════════════════════════════
        // LEGACY — Keep old bindings working
        // ═══════════════════════════════════════
        $defaultService = config('services.ai.default', 'openai');

        $this->app->bind(AIContentServiceInterface::class, function ($app) use ($defaultService) {
            return match ($defaultService) {
                'openai' => new OpenAIContentService(),
                'claude' => new ClaudeContentService(),
                default => new OpenAIContentService(),
            };
        });

        $this->app->singleton('ai.openai', fn () => new OpenAIContentService());
        $this->app->singleton('ai.claude', fn () => new ClaudeContentService());
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
