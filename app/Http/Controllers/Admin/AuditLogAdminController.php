<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AuditLogAdminController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isAdminOrAbove(), 403);

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::today()->subDays(6)->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::today()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $query = AuditLog::with('user')
            ->whereBetween('timestamp', [$dateFrom, $dateTo])
            ->orderByDesc('timestamp');

        if ($user->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
        } else {
            $query->where('company_id', $user->company_id)
                  ->whereHas('user', fn($q) => $q->where('role', '!=', 'superadmin'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('action')) {
            $query->where('action', 'like', $request->action . '%');
        }
        if ($request->filled('entity')) {
            $query->where('entity', $request->entity);
        }

        $logs = $query->paginate(30)->withQueryString();

        $companies = $user->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        $usersQuery = User::whereNotIn('role', ['superadmin'])->orderBy('name');
        if (! $user->isSuperAdmin()) {
            $usersQuery->where('company_id', $user->company_id);
        }
        $users = $usersQuery->get();

        $actions = AuditLog::when(! $user->isSuperAdmin(), fn($q) => $q->where('company_id', $user->company_id))
            ->distinct()->pluck('action')->sort()->values();

        return view('admin.audit-logs.index', compact('logs', 'companies', 'users', 'actions'));
    }
}