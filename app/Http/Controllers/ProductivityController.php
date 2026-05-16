<?php

namespace App\Http\Controllers;

use App\Models\PurchaseItem;
use App\Models\TaskOccurrence;
use App\Models\Unit;
use App\Models\User;
use App\Services\TimeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProductivityController extends Controller
{
    public function index(Request $request)
    {
        $authUser  = auth()->user();
        abort_unless($authUser->isAdminOrAbove() && ! $authUser->isSuperAdmin(), 403);

        $companyId = $authUser->company_id;
        $unitId    = $request->input('unit_id');

        // ── Período ──────────────────────────────────────────────
        $isRange = $request->filled('date_from') && $request->filled('date_to');

        if ($isRange) {
            $start = Carbon::parse($request->date_from)->startOfDay();
            $end   = Carbon::parse($request->date_to)->endOfDay();
            if ($start->gt($end)) [$start, $end] = [$end, $start];
        } elseif ($request->filled('date')) {
            $start = Carbon::parse($request->date)->startOfDay();
            $end   = Carbon::parse($request->date)->endOfDay();
        } else {
            $start = now()->startOfMonth()->startOfDay();
            $end   = now()->endOfMonth()->endOfDay();
        }

        $days = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);

        // ── Usuários ─────────────────────────────────────────────
        $units = Unit::where('company_id', $companyId)->where('active', true)->orderBy('name')->get();

        $usersQuery = User::where('company_id', $companyId)
            ->where('active', true)
            ->where('role', '!=', 'superadmin')
            ->orderBy('name');

        if ($unitId) {
            $usersQuery->whereHas('units', fn($q) => $q->where('units.id', $unitId));
        }

        $users = $usersQuery->get();

        // ── Tarefas concluídas por usuário no período ─────────────
        $taskStats = TaskOccurrence::where('company_id', $companyId)
            ->whereNotNull('completed_by')
            ->where('status', 'DONE')
            ->whereBetween('completed_at', [$start, $end])
            ->selectRaw('completed_by as user_id, COUNT(*) as tasks_done')
            ->groupBy('completed_by')
            ->get()->keyBy('user_id');

        // ── Compras solicitadas por usuário no período ────────────
        $purchaseStats = PurchaseItem::where('company_id', $companyId)
            ->whereBetween('requested_at', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('created_by as user_id, COUNT(*) as purchase_count')
            ->groupBy('created_by')
            ->get()->keyBy('user_id');

        // ── Horas escala + ponto por usuário ─────────────────────
        $service = new TimeCalculationService;

        $employees = $users->map(function ($user) use ($service, $start, $end, $taskStats, $purchaseStats) {
            $calc          = $service->calculateForPeriod($user, $start, $end);
            $clockDays     = count(array_filter($calc['breakdown'], fn($d) => $d['worked_minutes'] > 0));
            $scheduledDays = count(array_filter($calc['breakdown'], fn($d) => $d['scheduled_minutes'] > 0));

            return [
                'user'             => $user,
                'tasks_done'       => (int) ($taskStats[$user->id]->tasks_done ?? 0),
                'purchase_count'   => (int) ($purchaseStats[$user->id]->purchase_count ?? 0),
                'scheduled_hours'  => round($calc['scheduled_minutes'] / 60, 1),
                'worked_hours'     => round($calc['worked_minutes'] / 60, 1),
                'scheduled_days'   => $scheduledDays,
                'clock_days'       => $clockDays,
                'delta_minutes'    => $calc['worked_minutes'] - $calc['scheduled_minutes'],
                'overtime_minutes' => $calc['overtime_minutes'],
                'presence_rate'    => $scheduledDays > 0 ? round($clockDays / $scheduledDays * 100) : null,
            ];
        });

        $withPresence = $employees->whereNotNull('presence_rate');

        $kpi = [
            'avg_presence'      => $withPresence->isNotEmpty() ? round($withPresence->avg('presence_rate')) : null,
            'total_tasks'       => $employees->sum('tasks_done'),
            'total_purchases'   => $employees->sum('purchase_count'),
            'total_overtime_h'  => round($employees->sum('overtime_minutes') / 60, 1),
            'total_scheduled_h' => $employees->sum('scheduled_hours'),
            'total_worked_h'    => $employees->sum('worked_hours'),
        ];

        return view('productivity.index', compact(
            'employees', 'units', 'unitId', 'kpi',
            'start', 'end', 'isRange', 'days'
        ));
    }
}
