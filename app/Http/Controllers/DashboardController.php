<?php
namespace App\Http\Controllers;

use App\Models\SupportRequest;
use App\Models\TaskOccurrence;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();
        $company = $user->company;

        // ── Determinar modo e datas ───────────────────────────────
        $isRange  = $request->filled('date_from') && $request->filled('date_to');
        $dateFrom = null;
        $dateTo   = null;
        $date     = null;

        if ($isRange) {
            $dateFrom = Carbon::parse($request->date_from)->startOfDay();
            $dateTo   = Carbon::parse($request->date_to)->endOfDay();

            // Garante que date_from <= date_to
            if ($dateFrom->gt($dateTo)) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }
        } else {
            $date = $request->filled('date')
                ? Carbon::parse($request->date)->startOfDay()
                : Carbon::today();
        }

        // ── Query base ───────────────────────────────────────────
        $unitIds = $user->visibleUnitIds();

        $base = TaskOccurrence::query();
        if ($company) {
            $base->where('company_id', $company->id);
        }
        if ($unitIds !== null) {
            $base->whereIn('unit_id', $unitIds);
        }

        if ($isRange) {
            $base->whereBetween('period_start', [
                $dateFrom->toDateString(),
                $dateTo->toDateString(),
            ]);
        } else {
            $base->whereDate('period_start', $date);
        }

        // ── Totais ───────────────────────────────────────────────
        $total   = (clone $base)->count();
        $done    = (clone $base)->where('status', 'DONE')->count();
        $pending = (clone $base)->where('status', 'PENDING')->count();
        $overdue = (clone $base)->where('status', 'OVERDUE')->count();
        $rate    = $total > 0 ? round(($done / $total) * 100) : 0;

        // ── Médias para modo range ────────────────────────────────
        $days    = $isRange ? max(1, $dateFrom->diffInDays($dateTo) + 1) : 1;
        $avgDone = $isRange ? round($done    / $days, 1) : null;
        $avgNot  = $isRange ? round(($overdue + $pending) / $days, 1) : null;

        // ── Performance por colaborador ───────────────────────────
        $byUser = User::when($company, fn($q) => $q->where('company_id', $company->id))
            ->when($unitIds !== null, fn($q) => $q->whereHas('units', fn($u) => $u->whereIn('units.id', $unitIds)))
            ->whereNotIn('role', ['superadmin'])
            ->withCount(['completedOccurrences as done_count' => function ($q) use ($isRange, $date, $dateFrom, $dateTo) {
                if ($isRange) {
                    $q->whereBetween('completed_at', [$dateFrom, $dateTo]);
                } else {
                    $q->whereDate('completed_at', $date);
                }
            }])
            ->orderByDesc('done_count')
            ->get();

        // ── Detalhamento diário (só no range) ────────────────────
        $daily = collect();
        if ($isRange) {
            $daily = (clone $base)
                ->selectRaw("period_start,
                    COUNT(*) as total,
                    SUM(status = 'DONE') as done,
                    SUM(status = 'OVERDUE') as overdue,
                    SUM(status = 'PENDING') as pending")
                ->groupBy('period_start')
                ->orderBy('period_start')
                ->get()
                ->map(fn($r) => [
                    'date'    => Carbon::parse($r->period_start)->format('d/m/Y'),
                    'total'   => $r->total,
                    'done'    => $r->done,
                    'overdue' => $r->overdue,
                    'pending' => $r->pending,
                    'rate'    => $r->total > 0 ? round(($r->done / $r->total) * 100) : 0,
                ]);
        }

        // ── Visão geral de solicitações (só superadmin) ──────────
        $supportStats = null;
        if ($user->isSuperAdmin()) {
            $sq = SupportRequest::query();
            $supportStats = [
                'total'     => (clone $sq)->count(),
                'avaliar'   => (clone $sq)->where('status', 'avaliar')->count(),
                'fazer'     => (clone $sq)->where('status', 'fazer')->count(),
                'perguntar' => (clone $sq)->where('status', 'perguntar')->count(),
                'feito'     => (clone $sq)->where('status', 'feito')->count(),
                'important' => (clone $sq)->where('important', true)->whereNull('closed_at')->count(),
                'open'      => (clone $sq)->whereNull('closed_at')->count(),
                'recent'    => SupportRequest::with(['user', 'company'])
                                ->whereNull('closed_at')
                                ->orderByDesc('important')
                                ->orderByRaw('CASE WHEN priority IS NULL THEN 999 ELSE priority END')
                                ->orderByDesc('created_at')
                                ->limit(5)
                                ->get(),
            ];
        }

        return view('dashboard', compact(
            'total', 'done', 'pending', 'overdue', 'rate',
            'byUser', 'date', 'dateFrom', 'dateTo', 'isRange',
            'days', 'avgDone', 'avgNot', 'daily', 'supportStats'
        ));
    }
}
