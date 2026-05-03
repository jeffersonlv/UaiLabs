<?php
namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        abort_unless($user->isManagerOrAbove(), 403);

        // ── Filtros ──────────────────────────────────────────────
        $dateFrom  = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::today()->startOfDay();

        $dateTo    = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::today()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $selectedUser    = $request->input('user_id');
        $selectedAction  = $request->input('action');
        $selectedCompany = $request->input('company_id');

        // ── Query base ───────────────────────────────────────────
        $query = AuditLog::with('user')
            ->whereBetween('timestamp', [$dateFrom, $dateTo])
            ->orderByDesc('timestamp');

        if ($user->isSuperAdmin()) {
            if ($selectedCompany) {
                $query->where('company_id', $selectedCompany);
            }
        } else {
            $query->where('company_id', $user->company_id);
        }

        if ($selectedUser) {
            $query->where('user_id', $selectedUser);
        }

        if ($selectedAction) {
            $query->where('action', $selectedAction);
        }

        $logs = $query->paginate(15)->withQueryString();

        // ── Dados para filtros ───────────────────────────────────
        $usersQuery = User::whereNotIn('role', ['superadmin'])->orderBy('name');
        if (! $user->isSuperAdmin()) {
            $usersQuery->where('company_id', $user->company_id);
        } elseif ($selectedCompany) {
            $usersQuery->where('company_id', $selectedCompany);
        }
        $users = $usersQuery->get();

        $companies = $user->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        $actions = [
            'task.complete' => 'Concluiu tarefa',
            'task.reopen'   => 'Reexecutou tarefa',
            'login'         => 'Login',
            'logout'        => 'Logout',
        ];

        return view('audit-log.index', compact(
            'logs', 'users', 'companies', 'actions',
            'dateFrom', 'dateTo', 'selectedUser', 'selectedAction', 'selectedCompany'
        ));
    }
}
