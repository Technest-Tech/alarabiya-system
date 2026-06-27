<?php

namespace App\Providers;

use App\Models\Lesson;
use App\Observers\LessonObserver;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Lesson::observe(LessonObserver::class);

        // Authenticated users hitting a guest route (e.g. /login) go straight to
        // their dashboard. Without this the framework default sends them to '/',
        // which redirects to /login -> infinite redirect loop.
        RedirectIfAuthenticated::redirectUsing(function ($request) {
            return $request->user()?->dashboardPath() ?? '/';
        });

        // Trust proxies for HTTPS detection (important for Hostinger and similar hosting)
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
