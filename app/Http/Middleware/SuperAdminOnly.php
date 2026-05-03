<?php
namespace App\Http\Middleware;

use App\Services\SecurityLogger;
use Closure;
use Illuminate\Http\Request;

class SuperAdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
            SecurityLogger::forbiddenAccess($request->path(), auth()->id(), $request->ip());
            abort(403, 'Acesso restrito ao Super Admin.');
        }
        return $next($request);
    }
}
