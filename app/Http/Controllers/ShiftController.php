<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShiftRequest;
use App\Models\Shift;
use App\Models\ShiftTemplate;
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

        $shifts = Shift::with('user')
            ->where('unit_id', $unitId)
            ->where(fn($q) => $q->whereBetween('start_at', [$start, $end])
                ->orWhereBetween('end_at', [$start, $end]))
            ->orderBy('start_at')
            ->get();

        $unitUsers = $unitId
            ? User::whereHas('units', fn($q) => $q->where('units.id', $unitId))
                ->where('company_id', $user->company_id)
                ->where('active', true)
                ->orderBy('name')
                ->get()
            : collect();

        $templates = ShiftTemplate::where('unit_id', $unitId)->orderBy('name')->get();

        return view('shifts.index', compact('units', 'unitId', 'date', 'view', 'shifts', 'unitUsers', 'templates', 'start', 'end'));
    }

    public function store(StoreShiftRequest $request)
    {
        $this->authorizeUnit((int) $request->unit_id);

        $shift = Shift::create($request->validated() + [
            'company_id' => auth()->user()->company_id,
            'created_by' => auth()->id(),
        ]);

        AuditLogger::crud('shift.created', 'shift', $shift->id, $shift->user->name . ' ' . $shift->start_at->format('d/m'));
        return response()->json(['ok' => true, 'shift' => $this->shiftJson($shift->load('user'))]);
    }

    public function show(Shift $shift)
    {
        $this->authorizeUnit($shift->unit_id);
        return view('shifts.show', compact('shift'));
    }

    public function update(StoreShiftRequest $request, Shift $shift)
    {
        $this->authorizeUnit($shift->unit_id);

        $shift->update($request->validated());
        AuditLogger::crud('shift.updated', 'shift', $shift->id, $shift->user->name);
        return response()->json(['ok' => true]);
    }

    public function destroy(Shift $shift)
    {
        $this->authorizeUnit($shift->unit_id);
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

        $service = new TimeCalculationService;
        $users   = User::whereHas('units', fn($q) => $q->where('units.id', $request->unit_id))
            ->where('company_id', auth()->user()->company_id)
            ->where('active', true)
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
        $user    = auth()->user();
        $unitIds = $user->visibleUnitIds();

        $units = $unitIds !== null
            ? Unit::whereIn('id', $unitIds)->where('active', true)->orderBy('name')->get()
            : Unit::where('company_id', $user->company_id)->where('active', true)->orderBy('name')->get();

        $templates = ShiftTemplate::with('unit')->orderBy('name')->get();

        return view('shifts.templates.index', compact('templates', 'units'));
    }

    public function storeTemplate(Request $request)
    {
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
        $request->validate(['start_date' => 'required|date']);
        $this->authorizeUnit($template->unit_id);

        $start     = Carbon::parse($request->start_date)->startOfDay();
        $companyId = auth()->user()->company_id;
        $config    = $template->config; // array of ['day_of_week' => int, 'user_id' => int, 'start_time' => 'H:i', 'end_time' => 'H:i']

        $weeks = match ($template->period) {
            'biweekly' => 2,
            'monthly'  => 4,
            default    => 1,
        };

        for ($w = 0; $w < $weeks; $w++) {
            foreach ($config as $entry) {
                $day = $start->copy()->addWeeks($w)->startOfWeek()->addDays($entry['day_of_week']);
                Shift::create([
                    'company_id' => $companyId,
                    'unit_id'    => $template->unit_id,
                    'user_id'    => $entry['user_id'],
                    'start_at'   => $day->copy()->setTimeFromTimeString($entry['start_time']),
                    'end_at'     => $day->copy()->setTimeFromTimeString($entry['end_time']),
                    'type'       => 'work',
                    'created_by' => auth()->id(),
                ]);
            }
        }

        return back()->with('success', 'Template aplicado.');
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
            'id'       => $shift->id,
            'user'     => $shift->user->name,
            'user_id'  => $shift->user_id,
            'start_at' => $shift->start_at->toIso8601String(),
            'end_at'   => $shift->end_at->toIso8601String(),
            'type'     => $shift->type,
            'color'    => $shift->typeColor(),
            'notes'    => $shift->notes,
        ];
    }
}