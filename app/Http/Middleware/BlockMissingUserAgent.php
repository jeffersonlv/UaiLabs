<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockMissingUserAgent
{
    public function handle(Request $request, Closure $next)
    {
        if (empty($request->userAgent())) {
            abort(403, 'Bad request.');
        }
        return $next($request);
    }
}
