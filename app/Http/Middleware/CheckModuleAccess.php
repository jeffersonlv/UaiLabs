<?php
namespace App\Http\Middleware;

use App\Services\ModuleAccessService;
use Closure;
use Illuminate\Http\Request;

class CheckModuleAccess
{
    public function __construct(private ModuleAccessService $moduleAccess) {}

    public function handle(Request $request, Closure $next, string $moduleKey): mixed
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $this->moduleAccess->canAccess($user, $moduleKey)) {
            return redirect()->route('dashboard')
                ->with('error', 'Você não tem acesso ao módulo solicitado.');
        }

        return $next($request);
    }
}
