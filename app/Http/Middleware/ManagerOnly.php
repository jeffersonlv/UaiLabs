<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ManagerOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isManagerOrAbove()) {
            abort(403, 'Acesso restrito.');
        }
        return $next($request);
    }
}
