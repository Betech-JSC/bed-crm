<?php

namespace App\Providers;

use App\Events\ActivityLogged;
use App\Events\DealLost;
use App\Events\DealStageChanged;
use App\Events\DealWon;
use App\Events\LeadCreated;
use App\Events\LeadUpdated;
use App\Listeners\HandleDealLost;
use App\Listeners\HandleDealWon;
use App\Listeners\LogDealStageTransition;
use App\Listeners\RecordLeadFirstResponse;
use App\Listeners\StartLeadSLATracking;
use App\Listeners\TriggerLeadWorkflow;
use App\Models\Deal;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\User;
use App\Observers\DealObserver;
use App\Observers\ProjectExpenseObserver;
use App\Observers\ProjectObserver;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        Model::unguard();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(User::class, UserPolicy::class);

        // Register observers for cross-module data sync
        Deal::observe(DealObserver::class);
        Project::observe(ProjectObserver::class);
        ProjectExpense::observe(ProjectExpenseObserver::class);

        $this->bootRoute();
        $this->bootEvents();
    }

    public function bootRoute(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Register event listeners.
     */
    private function bootEvents(): void
    {
        // Lead events
        Event::listen(LeadCreated::class, StartLeadSLATracking::class);
        Event::listen(LeadCreated::class, TriggerLeadWorkflow::class);

        // Activity events
        Event::listen(ActivityLogged::class, RecordLeadFirstResponse::class);

        // Deal events
        Event::listen(DealStageChanged::class, LogDealStageTransition::class);
        Event::listen(DealWon::class, HandleDealWon::class);
        Event::listen(DealLost::class, HandleDealLost::class);
    }
}
