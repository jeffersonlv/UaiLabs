<?php
namespace App\Http\Middleware;

use App\Services\SecurityLogger;
use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isAdminOrAbove()) {
            SecurityLogger::forbiddenAccess($request->path(), auth()->id(), $request->ip());
            abort(403, 'Acesso restrito.');
        }
        return $next($request);
    }
}
