<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCompanyIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // superadmin has no company — always allow
        if (!$user->company_id) {
            return $next($request);
        }

        if ($user->company && !$user->company->active) {
            return redirect()->route('company.inactive');
        }

        return $next($request);
    }
}
