<?php

namespace App\Providers;

use App\Contracts\AIContentServiceInterface;
use App\Services\AI\ClaudeContentService;
use App\Services\AI\OpenAIContentService;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind default AI service based on config
        $defaultService = config('services.ai.default', 'openai');

        $this->app->bind(AIContentServiceInterface::class, function ($app) use ($defaultService) {
            return match ($defaultService) {
                'openai' => new OpenAIContentService(),
                'claude' => new ClaudeContentService(),
                default => new OpenAIContentService(),
            };
        });

        // Register individual services
        $this->app->singleton('ai.openai', function () {
            return new OpenAIContentService();
        });

        $this->app->singleton('ai.claude', function () {
            return new ClaudeContentService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

