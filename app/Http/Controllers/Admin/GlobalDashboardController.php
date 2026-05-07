<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\ModulePermission;
use App\Models\User;
use App\Modules\ModuleRegistry;
use Illuminate\Support\Carbon;

class GlobalDashboardController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $totalCompanies  = Company::count();
        $activeCompanies = Company::where('active', true)->count();

        $usersByRole = User::whereNotIn('role', ['superadmin'])
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $recentActivity = AuditLog::withoutGlobalScopes()
            ->with('user')
            ->orderByDesc('timestamp')
            ->limit(10)
            ->get();

        $moduleUsage = AuditLog::withoutGlobalScopes()
            ->whereIn('action', ['task.complete', 'task.reopen'])
            ->where('timestamp', '>=', Carbon::now()->subDays(7))
            ->count();

        $moduleSummary = ModuleRegistry::active();

        return view('admin.dashboard', compact(
            'totalCompanies', 'activeCompanies',
            'usersByRole', 'recentActivity',
            'moduleUsage', 'moduleSummary'
        ));
    }
}