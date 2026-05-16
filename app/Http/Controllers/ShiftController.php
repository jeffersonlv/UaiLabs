<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShiftRequest;
use App\Models\BoardAllocation;
use App\Models\Shift;
use App\Models\ShiftTemplate;
use App\Models\Station;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ModuleAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ShiftController extends Controller
{
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

        $unitId = $request->input('unit_id') ? (int) $request->input('unit_id') : null;
        if ($unitId) {
            abort_unless($units->contains('id', $unitId), 403);
        }

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
        $shifts = Shift::whereBetween('start_at', [$weekStart, $weekEnd])
            ->when($unitId, fn($q) => $q->where('unit_id', $unitId))
            ->when($unitIds !== null && !$unitId, fn($q) => $q->whereIn('unit_id', $unitIds))
            ->get()
            ->groupBy(fn($s) => $s->user_id . '_' . $s->start_at->toDateString());

        // Dias da semana
        $days = collect();
        $d    = $weekStart->copy();
        while ($d->lte($weekEnd)) {
            $days->push($d->copy());
            $d->addDay();
        }

        $isManager = $user->isManagerOrAbove();
        $templates = ShiftTemplate::where('company_id', $user->company_id)->orderBy('name')->get();
        $stations  = Station::where('active', true)->orderBy('order')->orderBy('name')->get();
        $canBoard  = app(ModuleAccessService::class)->canAccess($user, 'board_allocation');

        $boardAllocations = [];
        if ($canBoard) {
            $allocs = BoardAllocation::with(['user'])
                ->where('company_id', $user->company_id)
                ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->when($unitId, fn($q) => $q->where('unit_id', $unitId))
                ->get();
            foreach ($allocs as $alloc) {
                $boardAllocations[$alloc->period][$alloc->station_id][$alloc->date->toDateString()][] = [
                    'id'      => $alloc->id,
                    'name'    => $alloc->user->name,
                    'user_id' => $alloc->user_id,
                ];
            }
        }

        return view('shifts.timesheet', compact(
            'users', 'days', 'shifts', 'templates',
            'units', 'unitId', 'weekParam', 'weekStart', 'weekEnd', 'isManager',
            'stations', 'canBoard', 'boardAllocations'
        ));
    }

    public function saveWeekAsTemplate(Request $request)
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);

        $request->validate([
            'name'   => 'required|string|max:100',
            'period' => 'required|in:weekly,biweekly,monthly',
            'week'   => 'required|string',
        ]);

        $user      = auth()->user();
        $weekStart = Carbon::now()->setISODate(...explode('-W', $request->week))->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $unitId    = $request->input('unit_id');
        $unitIds   = $user->visibleUnitIds();

        $weekShifts = Shift::whereBetween('start_at', [$weekStart, $weekEnd])
            ->when($unitId, fn($q) => $q->where('unit_id', $unitId))
            ->when($unitIds !== null && !$unitId, fn($q) => $q->whereIn('unit_id', $unitIds))
            ->get();

        // Converte shifts para config do template
        // day_of_week: 0=Seg ... 6=Dom (baseado no startOfWeek Monday)
        $config = $weekShifts->map(fn($s) => [
            'day_of_week' => ($s->start_at->dayOfWeek + 6) % 7,
            'user_id'     => $s->user_id,
            'start_time'  => $s->start_at->format('H:i'),
            'end_time'    => $s->end_at->format('H:i'),
            'station_id'  => $s->station_id,
            'type'        => $s->type,
        ])->values()->all();

        ShiftTemplate::create([
            'company_id' => $user->company_id,
            'unit_id'    => $unitId ?: null,
            'name'       => $request->name,
            'period'     => $request->period,
            'config'     => $config,
        ]);

        return back()->with('success', 'Template "' . $request->name . '" salvo com ' . count($config) . ' turno(s).');
    }

    public function board(Request $request)
    {
        $user    = auth()->user();
        $unitIds = $user->visibleUnitIds();

        $weekParam = $request->input('week', Carbon::today()->format('o-\WW'));
        $weekStart = Carbon::now()->setISODate(...explode('-W', $weekParam))->startOfWeek(Carbon::MONDAY);
        $units  = $unitIds !== null
            ? Unit::whereIn('id', $unitIds)->where('active', true)->orderBy('name')->get()
            : Unit::where('company_id', $user->company_id)->where('active', true)->orderBy('name')->get();

        $unitId = $request->input('unit_id') ? (int) $request->input('unit_id') : null;
        if ($unitId) {
            abort_unless($units->contains('id', $unitId), 403);
        }
        $stations  = Station::where('active', true)->orderBy('order')->orderBy('name')->get();
        $isManager = $user->isManagerOrAbove();
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $days = collect();
        $d    = $weekStart->copy();
        while ($d->lte($weekEnd)) {
            $days->push($d->copy());
            $d->addDay();
        }

        return view('shifts.board', compact(
            'stations', 'days', 'units', 'unitId', 'weekParam', 'weekStart', 'weekEnd', 'isManager'
        ));
    }

    public function boardData(Request $request)
    {
        $user    = auth()->user();
        $unitIds = $user->visibleUnitIds();

        $weekParam = $request->input('week', Carbon::today()->format('o-\WW'));
        $weekStart = Carbon::now()->setISODate(...explode('-W', $weekParam))->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $unitId = $request->input('unit_id') ? (int) $request->input('unit_id') : null;
        if ($unitId) {
            $this->authorizeUnit($unitId);
            abort_unless(Unit::where('id', $unitId)->where('company_id', $user->company_id)->where('active', true)->exists(), 403);
        }

        $shifts = Shift::with(['user'])
            ->where('type', 'work')
            ->whereBetween('start_at', [$weekStart, $weekEnd])
            ->when($unitId, fn($q) => $q->where('unit_id', $unitId))
            ->when($unitIds !== null && !$unitId, fn($q) => $q->whereIn('unit_id', $unitIds))
            ->get();

        // period → { assigned: {station_id → date → [emp]}, unassigned: {date → [emp]} }
        $data = [];
        foreach ($shifts as $shift) {
            $hour   = (int) $shift->start_at->format('H');
            $period = $hour < 12 ? 'manha' : ($hour < 18 ? 'tarde' : 'noite');
            $date   = $shift->start_at->toDateString();
            $emp    = ['shift_id' => $shift->id, 'name' => $shift->user->name];

            if ($shift->station_id) {
                $data[$period]['assigned'][$shift->station_id][$date][] = $emp;
            } else {
                $data[$period]['unassigned'][$date][] = $emp;
            }
        }

        return response()->json($data);
    }

    public function assignStation(Request $request, Shift $shift)
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);
        if ($shift->unit_id) $this->authorizeUnit($shift->unit_id);

        $request->validate(['station_id' => 'nullable|exists:stations,id']);
        $shift->update(['station_id' => $request->station_id ?: null]);

        AuditLogger::crud('shift.updated', 'shift', $shift->id, $shift->user->name);
        return response()->json(['ok' => true]);
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
        $unitId  = $request->input('unit_id') ? (int) $request->input('unit_id') : null;
        $month   = $request->input('month', Carbon::today()->format('Y-m'));

        $units = $unitIds !== null
            ? Unit::whereIn('id', $unitIds)->where('active', true)->orderBy('name')->get()
            : Unit::where('company_id', $user->company_id)->where('active', true)->orderBy('name')->get();

        if ($unitId) {
            abort_unless($units->contains('id', $unitId), 403);
        }

        if (! $unitId && $units->isNotEmpty()) {
            $unitId = $units->first()->id;
        }

        $start = Carbon::parse($month)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $shifts = Shift::with('user')
            ->where('unit_id', $unitId)
            ->when(! $user->isManagerOrAbove(), fn($q) => $q->where('user_id', $user->id))
            ->where(fn($q) => $q->whereBetween('start_at', [$start, $end])
                ->orWhereBetween('end_at', [$start, $end]))
            ->get();

        // Usuários da unidade para o modal "Novo Turno"
        $unitUsers = $unitId
            ? User::whereHas('units', fn($q) => $q->where('units.id', $unitId))
                ->where('company_id', $user->company_id)
                ->where('active', true)
                ->orderBy('name')
                ->get()
            : collect();

        // Dados do quadro de alocação (semana embutida)
        $boardWeek  = $request->input('board_week', Carbon::today()->format('o-\WW'));
        $boardStart = Carbon::now()->setISODate(...explode('-W', $boardWeek))->startOfWeek(Carbon::MONDAY);
        $boardEnd   = $boardStart->copy()->endOfWeek(Carbon::SUNDAY);
        $stations   = Station::where('active', true)->orderBy('order')->orderBy('name')->get();
        $boardDays  = collect();
        $d = $boardStart->copy();
        while ($d->lte($boardEnd)) { $boardDays->push($d->copy()); $d->addDay(); }

        return view('shifts.calendar', compact(
            'units', 'unitId', 'month', 'shifts', 'start',
            'unitUsers', 'boardWeek', 'boardStart', 'boardEnd', 'boardDays', 'stations'
        ));
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
            'unit_id' => 'nullable|exists:units,id',
            'name'    => 'required|string|max:100',
            'period'  => 'required|in:weekly,biweekly,monthly',
        ]);
        if ($request->unit_id) $this->authorizeUnit((int) $request->unit_id);

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
        if ($template->unit_id) $this->authorizeUnit($template->unit_id);

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
                    'station_id' => $entry['station_id'] ?? null,
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
        if ($template->unit_id) $this->authorizeUnit($template->unit_id);
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
        if ($template->unit_id) $this->authorizeUnit($template->unit_id);
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