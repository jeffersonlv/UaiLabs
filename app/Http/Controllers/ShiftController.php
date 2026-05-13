<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShiftRequest;
use App\Models\Shift;
use App\Models\ShiftTemplate;
use App\Models\Station;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TimeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();
        $unitIds = $user->visibleUnitIds();

        $unitId = $request->input('unit_id');
        $date   = $request->input('date', Carbon::today()->toDateString());
        $view   = $request->input('view', 'day'); // day | week | month

        $units = $unitIds !== null
            ? Unit::whereIn('id', $unitIds)->where('active', true)->orderBy('name')->get()
            : Unit::where('company_id', $user->company_id)->where('active', true)->orderBy('name')->get();

        if (! $unitId && $units->isNotEmpty()) {
            $unitId = $units->first()->id;
        }

        $start = Carbon::parse($date)->startOfDay();
        $end   = match ($view) {
            'week'  => $start->copy()->endOfWeek(),
            'month' => $start->copy()->endOfMonth(),
            default => $start->copy()->endOfDay(),
        };

        $isManager = $user->isManagerOrAbove();

        $shifts = Shift::with('user')
            ->where('unit_id', $unitId)
            ->when(! $isManager, fn($q) => $q->where('user_id', $user->id))
            ->where(fn($q) => $q->whereBetween('start_at', [$start, $end])
                ->orWhereBetween('end_at', [$start, $end]))
            ->orderBy('start_at')
            ->get();

        $unitUsers = $unitId
            ? User::whereHas('units', fn($q) => $q->where('units.id', $unitId))
                ->where('company_id', $user->company_id)
                ->where('active', true)
                ->when(! $isManager, fn($q) => $q->where('id', $user->id))
                ->orderBy('name')
                ->get()
            : collect();

        $templates = ShiftTemplate::where('unit_id', $unitId)->orderBy('name')->get();

        return view('shifts.index', compact('units', 'unitId', 'date', 'view', 'shifts', 'unitUsers', 'templates', 'start', 'end'));
    }

    public function timesheet(Request $request)
    {
        $user    = auth()->user();
        $unitIds = $user->visibleUnitIds();

        // Parseia semana: ?week=2026-W20 ou padrão = semana atual
        $weekParam  = $request->input('week', Carbon::today()->format('o-\WW'));
        $weekStart  = Carbon::now()->setISODate(...explode('-W', $weekParam))->startOfWeek(Carbon::MONDAY);
        $weekEnd    = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        // Filtro de unidade
        $units = $unitIds !== null
            ? Unit::whereIn('id', $unitIds)->where('active', true)->orderBy('name')->get()
            : Unit::where('company_id', $user->company_id)->where('active', true)->orderBy('name')->get();

        $unitId = $request->input('unit_id');

        // Usuários visíveis
        $usersQuery = User::where('company_id', $user->company_id)
            ->where('active', true)
            ->whereNotIn('role', ['superadmin'])
            ->orderBy('name');

        if ($unitId) {
            $usersQuery->whereHas('units', fn($q) => $q->where('units.id', $unitId));
        } elseif ($unitIds !== null) {
            $usersQuery->whereHas('units', fn($q) => $q->whereIn('units.id', $unitIds));
        }

        $users = $usersQuery->get();

        // Shifts da semana indexados por user_id → date
        $shifts = Shift::with(['station'])
            ->whereBetween('start_at', [$weekStart, $weekEnd])
            ->when($unitId, fn($q) => $q->where('unit_id', $unitId))
            ->when($unitIds !== null && !$unitId, fn($q) => $q->whereIn('unit_id', $unitIds))
            ->get()
            ->groupBy(fn($s) => $s->user_id . '_' . $s->start_at->toDateString());

        $stations = Station::where('active', true)->orderBy('order')->orderBy('name')->get();

        // Dias da semana
        $days = collect();
        $d    = $weekStart->copy();
        while ($d->lte($weekEnd)) {
            $days->push($d->copy());
            $d->addDay();
        }

        $isManager = $user->isManagerOrAbove();

        return view('shifts.timesheet', compact(
            'users', 'days', 'shifts', 'stations',
            'units', 'unitId', 'weekParam', 'weekStart', 'weekEnd', 'isManager'
        ));
    }

    public function board(Request $request)
    {
        $user    = auth()->user();
        $unitIds = $user->visibleUnitIds();

        $weekParam = $request->input('week', Carbon::today()->format('o-\WW'));
        $weekStart = Carbon::now()->setISODate(...explode('-W', $weekParam))->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $units  = $unitIds !== null
            ? Unit::whereIn('id', $unitIds)->where('active', true)->orderBy('name')->get()
            : Unit::where('company_id', $user->company_id)->where('active', true)->orderBy('name')->get();

        $unitId   = $request->input('unit_id');
        $stations = Station::where('active', true)->orderBy('order')->orderBy('name')->get();

        $days = collect();
        $d    = $weekStart->copy();
        while ($d->lte($weekEnd)) {
            $days->push($d->copy());
            $d->addDay();
        }

        return view('shifts.board', compact(
            'stations', 'days', 'units', 'unitId', 'weekParam', 'weekStart'
        ));
    }

    public function boardData(Request $request)
    {
        $user    = auth()->user();
        $unitIds = $user->visibleUnitIds();

        $weekParam = $request->input('week', Carbon::today()->format('o-\WW'));
        $weekStart = Carbon::now()->setISODate(...explode('-W', $weekParam))->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $unitId = $request->input('unit_id');

        $shifts = Shift::with(['user', 'station'])
            ->where('type', 'work')
            ->whereNotNull('station_id')
            ->whereBetween('start_at', [$weekStart, $weekEnd])
            ->when($unitId, fn($q) => $q->where('unit_id', $unitId))
            ->when($unitIds !== null && !$unitId, fn($q) => $q->whereIn('unit_id', $unitIds))
            ->get();

        // Agrupa: period → station_id → date → [user names]
        $data = [];
        foreach ($shifts as $shift) {
            $hour   = (int) $shift->start_at->format('H');
            $period = $hour < 12 ? 'manha' : ($hour < 18 ? 'tarde' : 'noite');
            $date   = $shift->start_at->toDateString();
            $sid    = $shift->station_id;

            $data[$period][$sid][$date][] = [
                'id'   => $shift->id,
                'name' => $shift->user->name,
            ];
        }

        return response()->json($data);
    }

    public function store(StoreShiftRequest $request)
    {
        if ($request->unit_id) {
            $this->authorizeUnit((int) $request->unit_id);
        }

        $shift = Shift::create($request->validated() + [
            'company_id' => auth()->user()->company_id,
            'created_by' => auth()->id(),
        ]);

        AuditLogger::crud('shift.created', 'shift', $shift->id, $shift->user->name . ' ' . $shift->start_at->format('d/m'));
        return response()->json(['ok' => true, 'shift' => $this->shiftJson($shift->load('user'))]);
    }

    public function show(Shift $shift)
    {
        if ($shift->unit_id) $this->authorizeUnit($shift->unit_id);
        return view('shifts.show', compact('shift'));
    }

    public function update(StoreShiftRequest $request, Shift $shift)
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);
        if ($shift->unit_id) $this->authorizeUnit($shift->unit_id);

        $shift->update($request->validated());
        AuditLogger::crud('shift.updated', 'shift', $shift->id, $shift->user->name);
        return response()->json(['ok' => true]);
    }

    public function destroy(Shift $shift)
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);
        if ($shift->unit_id) $this->authorizeUnit($shift->unit_id);
        AuditLogger::crud('shift.deleted', 'shift', $shift->id, $shift->user->name);
        $shift->delete();
        return response()->json(['ok' => true]);
    }

    public function calendar(Request $request)
    {
        $user    = auth()->user();
        $unitIds = $user->visibleUnitIds();
        $unitId  = $request->input('unit_id');
        $month   = $request->input('month', Carbon::today()->format('Y-m'));

        $units = $unitIds !== null
            ? Unit::whereIn('id', $unitIds)->where('active', true)->orderBy('name')->get()
            : Unit::where('company_id', $user->company_id)->where('active', true)->orderBy('name')->get();

        if (! $unitId && $units->isNotEmpty()) {
            $unitId = $units->first()->id;
        }

        $start  = Carbon::parse($month)->startOfMonth();
        $end    = $start->copy()->endOfMonth();

        $shifts = Shift::with('user')
            ->where('unit_id', $unitId)
            ->when(! $user->isManagerOrAbove(), fn($q) => $q->where('user_id', $user->id))
            ->where(fn($q) => $q->whereBetween('start_at', [$start, $end])
                ->orWhereBetween('end_at', [$start, $end]))
            ->get();

        return view('shifts.calendar', compact('units', 'unitId', 'month', 'shifts', 'start'));
    }

    /** Summary JSON: worked/scheduled hours per user for a period. */
    public function summary(Request $request)
    {
        $request->validate(['unit_id' => 'required', 'start_date' => 'required|date', 'end_date' => 'required|date']);
        $this->authorizeUnit((int) $request->unit_id);

        $authUser = auth()->user();
        $service  = new TimeCalculationService;
        $users    = User::whereHas('units', fn($q) => $q->where('units.id', $request->unit_id))
            ->where('company_id', $authUser->company_id)
            ->where('active', true)
            ->when(! $authUser->isManagerOrAbove(), fn($q) => $q->where('id', $authUser->id))
            ->with('workSchedule')
            ->get();

        $result = $users->map(fn($u) => [
            'user'       => $u->name,
            'weekly_h'   => $u->workSchedule?->weekly_hours ?? 40,
            'totals'     => $service->calculateForPeriod($u, $request->start_date, $request->end_date),
        ]);

        return response()->json($result);
    }

    /** Templates listing. */
    public function templates(Request $request)
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);
        $user    = auth()->user();
        $unitIds = $user->visibleUnitIds();

        $units = $unitIds !== null
            ? Unit::whereIn('id', $unitIds)->where('active', true)->orderBy('name')->get()
            : Unit::where('company_id', $user->company_id)->where('active', true)->orderBy('name')->get();

        $templates = ShiftTemplate::with(['unit', 'unit.users' => fn($q) => $q->where('active', true)->orderBy('name')])->orderBy('name')->get();

        return view('shifts.templates.index', compact('templates', 'units'));
    }

    public function storeTemplate(Request $request)
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);
        $request->validate([
            'unit_id' => 'required|exists:units,id',
            'name'    => 'required|string|max:100',
            'period'  => 'required|in:weekly,biweekly,monthly',
        ]);
        $this->authorizeUnit((int) $request->unit_id);

        ShiftTemplate::create($request->only('unit_id', 'name', 'period') + [
            'config'     => [],
            'company_id' => auth()->user()->company_id,
        ]);

        return back()->with('success', 'Template criado.');
    }

    public function applyTemplate(Request $request, ShiftTemplate $template)
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);
        $request->validate([
            'start_date' => 'required|date',
            'conflict'   => 'in:skip,replace',
        ]);
        $this->authorizeUnit($template->unit_id);

        $start     = Carbon::parse($request->start_date)->startOfDay();
        $companyId = auth()->user()->company_id;
        $config    = $template->config;
        $conflict  = $request->input('conflict', 'skip');

        $weeks = match ($template->period) {
            'biweekly' => 2,
            'monthly'  => 4,
            default    => 1,
        };

        $created = 0;
        $skipped = 0;

        for ($w = 0; $w < $weeks; $w++) {
            foreach ($config as $entry) {
                $day = $start->copy()->addWeeks($w)->startOfWeek()->addDays($entry['day_of_week']);

                $shiftStart = $day->copy()->setTimeFromTimeString($entry['start_time']);
                $shiftEnd   = $day->copy()->setTimeFromTimeString($entry['end_time']);

                // Overlap: existing.start < new.end AND existing.end > new.start
                $existing = Shift::where('user_id', $entry['user_id'])
                    ->where('unit_id', $template->unit_id)
                    ->where('start_at', '<', $shiftEnd)
                    ->where('end_at', '>', $shiftStart)
                    ->exists();

                if ($existing) {
                    if ($conflict === 'replace') {
                        Shift::where('user_id', $entry['user_id'])
                            ->where('unit_id', $template->unit_id)
                            ->where('start_at', '<', $shiftEnd)
                            ->where('end_at', '>', $shiftStart)
                            ->delete();
                    } else {
                        $skipped++;
                        continue;
                    }
                }

                Shift::create([
                    'company_id' => $companyId,
                    'unit_id'    => $template->unit_id,
                    'user_id'    => $entry['user_id'],
                    'start_at'   => $day->copy()->setTimeFromTimeString($entry['start_time']),
                    'end_at'     => $day->copy()->setTimeFromTimeString($entry['end_time']),
                    'type'       => 'work',
                    'created_by' => auth()->id(),
                ]);
                $created++;
            }
        }

        $msg = "{$created} turno(s) criado(s)";
        if ($skipped) $msg .= ", {$skipped} pulado(s) por conflito (já existiam)";

        return back()->with('success', "Template aplicado: {$msg}.");
    }

    public function updateTemplate(Request $request, ShiftTemplate $template)
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);
        $this->authorizeUnit($template->unit_id);
        $request->validate([
            'name'   => 'required|string|max:100',
            'period' => 'required|in:weekly,biweekly,monthly',
            'config' => 'nullable|string',
        ]);
        $config = json_decode($request->input('config', '[]'), true) ?? [];
        $template->update([
            'name'   => $request->name,
            'period' => $request->period,
            'config' => $config,
        ]);
        return back()->with('success', 'Template atualizado.');
    }

    public function destroyTemplate(ShiftTemplate $template)
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);
        $this->authorizeUnit($template->unit_id);
        $template->delete();
        return back()->with('success', 'Template removido.');
    }

    private function authorizeUnit(int $unitId): void
    {
        $user    = auth()->user();
        $unitIds = $user->visibleUnitIds();
        if ($unitIds !== null) {
            abort_unless(in_array($unitId, $unitIds), 403);
        }
    }

    private function shiftJson(Shift $shift): array
    {
        return [
            'id'         => $shift->id,
            'user'       => $shift->user->name,
            'user_id'    => $shift->user_id,
            'start_at'   => $shift->start_at->toIso8601String(),
            'end_at'     => $shift->end_at->toIso8601String(),
            'start_fmt'  => $shift->start_at->format('H:i'),
            'end_fmt'    => $shift->end_at->format('H:i'),
            'date_fmt'   => $shift->start_at->format('d/m/Y'),
            'type'       => $shift->type,
            'type_label' => $shift->typeLabel(),
            'color'      => $shift->typeColor(),
            'notes'      => $shift->notes,
            'edit_url'   => route('shifts.show', $shift),
            'delete_url' => route('shifts.destroy', $shift),
        ];
    }
}