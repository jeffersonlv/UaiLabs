<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PurchaseItem;
use App\Models\SupportRequest;
use App\Models\TaskOccurrence;
use App\Models\Unit;
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
            if ($dateFrom->gt($dateTo)) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }
        } else {
            $date = $request->filled('date')
                ? Carbon::parse($request->date)->startOfDay()
                : Carbon::today();
        }

        // ── Filtros de empresa/unidade ────────────────────────────
        $unitIds           = $user->visibleUnitIds();
        $allCompanies      = collect();
        $companyUnits      = collect();
        $selectedCompanyId = null;
        $selectedUnitId    = null;
        $filterCompany     = $company;

        if ($user->isSuperAdmin()) {
            $allCompanies      = Company::orderBy('name')->get();
            $selectedCompanyId = $request->input('company_id');
            $selectedUnitId    = $request->input('unit_id');

            if ($selectedCompanyId) {
                $filterCompany = $allCompanies->firstWhere('id', $selectedCompanyId);
                $companyUnits  = Unit::where('company_id', $selectedCompanyId)->orderBy('name')->get();
            }
        } elseif ($user->isAdmin() && $company) {
            $companyUnits   = Unit::where('company_id', $company->id)->orderBy('name')->get();
            $selectedUnitId = $request->input('unit_id');
        }

        // ── Query base ───────────────────────────────────────────
        $base = TaskOccurrence::query();
        if ($filterCompany) {
            $base->where('company_id', $filterCompany->id);
        }
        if ($unitIds !== null) {
            $base->whereIn('unit_id', $unitIds);
        } elseif ($selectedUnitId) {
            $base->where('unit_id', $selectedUnitId);
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
        $byUser = User::when($filterCompany, fn($q) => $q->where('company_id', $filterCompany->id))
            ->when($unitIds !== null, fn($q) => $q->whereHas('units', fn($u) => $u->whereIn('units.id', $unitIds)))
            ->when($selectedUnitId, fn($q) => $q->whereHas('units', fn($u) => $u->where('units.id', $selectedUnitId)))
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

        // ── Compras pendentes ─────────────────────────────────────
        $purchaseStats = null;
        if ($user->isManagerOrAbove()) {
            $purchaseStats = [
                'today'   => PurchaseItem::where('status', 'pending')->where('requested_at', Carbon::today())->count(),
                'old'     => PurchaseItem::where('status', 'pending')->where('requested_at', '<', Carbon::today()->subDays(6))->count(),
                'pending' => PurchaseItem::where('status', 'pending')->count(),
            ];
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

        $visibleUnits = $unitIds !== null
            ? Unit::whereIn('id', $unitIds)->orderBy('name')->get()
            : ($selectedUnitId
                ? Unit::where('id', $selectedUnitId)->get()
                : ($filterCompany ? Unit::where('company_id', $filterCompany->id)->orderBy('name')->get() : collect()));

        return view('dashboard', compact(
            'total', 'done', 'pending', 'overdue', 'rate',
            'byUser', 'date', 'dateFrom', 'dateTo', 'isRange',
            'days', 'avgDone', 'avgNot', 'daily', 'supportStats',
            'visibleUnits', 'companyUnits', 'selectedUnitId',
            'allCompanies', 'selectedCompanyId', 'filterCompany',
            'purchaseStats'
        ));
    }

    /** Chart.js data endpoint. */
    public function completionChart(Request $request)
    {
        $user    = auth()->user();
        $company = $user->company;

        $dateFrom = Carbon::parse($request->input('date_from', Carbon::today()->subDays(6)))->startOfDay();
        $dateTo   = Carbon::parse($request->input('date_to', Carbon::today()))->endOfDay();
        $groupBy  = $request->input('group', 'geral'); // geral | empresa | filial

        $unitIds          = $user->visibleUnitIds();
        $selectedCompanyId = $request->input('company_id');
        $filterCompany    = $company;

        if ($user->isSuperAdmin() && $selectedCompanyId) {
            $filterCompany = Company::find($selectedCompanyId);
        }

        $datasets = [];
        $palette  = ['#0d6efd','#198754','#dc3545','#ffc107','#6f42c1','#0dcaf0','#fd7e14'];

        if ($groupBy === 'filial') {
            $units = $unitIds !== null
                ? Unit::whereIn('id', $unitIds)->get()
                : ($filterCompany ? Unit::where('company_id', $filterCompany->id)->get() : collect());

            foreach ($units as $idx => $unit) {
                $data = $this->dailyRates(TaskOccurrence::where('unit_id', $unit->id), $dateFrom, $dateTo);
                $datasets[] = ['label' => $unit->name, 'data' => array_values($data), 'borderColor' => $palette[$idx % count($palette)], 'fill' => false];
            }
        } elseif ($groupBy === 'empresa' && $user->isSuperAdmin()) {
            $companies = Company::orderBy('name')->get();
            foreach ($companies as $idx => $c) {
                $data = $this->dailyRates(TaskOccurrence::withoutGlobalScopes()->where('company_id', $c->id), $dateFrom, $dateTo);
                $datasets[] = ['label' => $c->name, 'data' => array_values($data), 'borderColor' => $palette[$idx % count($palette)], 'fill' => false];
            }
        } else {
            $q = TaskOccurrence::query();
            if ($filterCompany) $q->where('company_id', $filterCompany->id);
            if ($unitIds !== null) $q->whereIn('unit_id', $unitIds);
            $data = $this->dailyRates($q, $dateFrom, $dateTo);
            $datasets[] = ['label' => 'Geral', 'data' => array_values($data), 'borderColor' => '#0d6efd', 'fill' => false];
        }

        $labels = [];
        $d = $dateFrom->copy();
        while ($d->lte($dateTo)) {
            $labels[] = $d->format('d/m');
            $d->addDay();
        }

        return response()->json(compact('labels', 'datasets'));
    }

    private function dailyRates($query, Carbon $from, Carbon $to): array
    {
        $rows = (clone $query)
            ->whereBetween('period_start', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("period_start, COUNT(*) as total, SUM(status='DONE') as done")
            ->groupBy('period_start')
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->period_start)->format('Y-m-d'));

        $result = [];
        $d = $from->copy();
        while ($d->lte($to)) {
            $key = $d->format('Y-m-d');
            $row = $rows->get($key);
            $result[$key] = $row && $row->total > 0 ? round(($row->done / $row->total) * 100, 1) : null;
            $d->addDay();
        }
        return $result;
    }
}