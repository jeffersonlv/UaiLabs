<?php

namespace App\Providers;

use App\Services\ModuleAccessService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleAccessService::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrap();

        // A02: enforce HTTPS in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // A07: enforce minimum 12-char password globally
        Password::defaults(function () {
            return Password::min(12);
        });
    }
}
