<?php

namespace App\Providers;

use App\Contracts\SocialPlatformInterface;
use App\Services\Social\FacebookPlatform;
use App\Services\Social\InstagramPlatform;
use App\Services\Social\LinkedInPlatform;
use App\Services\Social\TwitterPlatform;
use Illuminate\Support\ServiceProvider;

class SocialServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register platform services
        $this->app->bind('social.linkedin', function () {
            return new LinkedInPlatform();
        });

        $this->app->bind('social.twitter', function () {
            return new TwitterPlatform();
        });

        $this->app->bind('social.facebook', function () {
            return new FacebookPlatform();
        });

        $this->app->bind('social.instagram', function () {
            return new InstagramPlatform();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get platform service by name.
     */
    public static function getPlatformService(string $platform): ?SocialPlatformInterface
    {
        $serviceKey = 'social.' . strtolower($platform);
        
        if (app()->bound($serviceKey)) {
            return app($serviceKey);
        }

        return null;
    }
}

