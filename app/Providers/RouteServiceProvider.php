<?php

namespace App\Providers;

use App\Services\SecurityLogger;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/dashboard';

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Max 5 login attempts per minute per IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())
                ->response(function (Request $request) {
                    SecurityLogger::rateLimitHit($request->path(), $request->ip());
                    return response()->json(['message' => 'Muitas tentativas. Aguarde 1 minuto.'], 429);
                });
        });

        // Max 120 requests per minute for authenticated users
        RateLimiter::for('web-auth', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request) {
                    SecurityLogger::rateLimitHit($request->path(), $request->ip(), $request->user()?->id);
                    return back()->with('error', 'Muitas requisições. Aguarde um momento.');
                });
        });

        // Max 30 requests per minute for unauthenticated routes
        RateLimiter::for('web-guest', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip())
                ->response(function (Request $request) {
                    SecurityLogger::rateLimitHit($request->path(), $request->ip());
                    return response()->view('errors.429', [], 429);
                });
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
