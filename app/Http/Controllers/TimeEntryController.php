<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimeEntryRequest;
use App\Models\TimeEntry;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TimeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TimeEntryController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();
        abort_unless($user->isManagerOrAbove(), 403);

        $unitIds  = $user->visibleUnitIds();
        $unitId   = $request->input('unit_id');
        $userId   = $request->input('user_id');
        $dateFrom = $request->input('date_from', Carbon::today()->toDateString());
        $dateTo   = $request->input('date_to', $dateFrom);

        $units = $unitIds !== null
            ? Unit::whereIn('id', $unitIds)->where('active', true)->orderBy('name')->get()
            : Unit::where('company_id', $user->company_id)->where('active', true)->orderBy('name')->get();

        $usersQuery = User::where('company_id', $user->company_id)->where('active', true)->orderBy('name');
        if ($unitId) {
            $usersQuery->whereHas('units', fn($q) => $q->where('units.id', $unitId));
        }
        $users = $usersQuery->get();

        $entries = TimeEntry::with('user', 'unit')
            ->when($unitIds !== null, fn($q) => $q->where(fn($q2) => $q2->whereIn('unit_id', $unitIds)->orWhereNull('unit_id')))
            ->when($unitId, fn($q) => $q->where('unit_id', $unitId))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->whereBetween('recorded_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ])
            ->orderBy('recorded_at')
            ->paginate(50)
            ->withQueryString();

        return view('time-entries.index', compact('entries', 'units', 'users', 'unitId', 'userId', 'dateFrom', 'dateTo'));
    }

    public function store(StoreTimeEntryRequest $request)
    {
        $user = auth()->user();
        abort_unless($user->isManagerOrAbove(), 403);

        $entry = TimeEntry::create($request->validated() + [
            'company_id' => $user->company_id,
            'user_id'    => $request->input('user_id', $user->id),
            'type'       => 'correction',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        AuditLogger::crud('time_entry.correction', 'time_entry', $entry->id, $user->name);
        return back()->with('success', 'Correção registrada.');
    }

    public function personalDashboard(Request $request)
    {
        $user  = auth()->user();
        $month = $request->input('month', Carbon::today()->format('Y-m'));
        $start = Carbon::parse($month)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $service = new TimeCalculationService;
        $calc    = $service->calculateForPeriod($user, $start, $end);

        $entries = TimeEntry::where('user_id', $user->id)
            ->whereBetween('recorded_at', [$start, $end])
            ->orderBy('recorded_at')
            ->get();

        return view('time-entries.dashboard', compact('user', 'month', 'calc', 'entries', 'start', 'end'));
    }

    public function monthlyReport(Request $request)
    {
        $user    = auth()->user();
        abort_unless($user->isManagerOrAbove(), 403);

        $unitIds = $user->visibleUnitIds();
        $unitId  = $request->input('unit_id');
        $month   = $request->input('month', Carbon::today()->format('Y-m'));
        $start   = Carbon::parse($month)->startOfMonth();
        $end     = $start->copy()->endOfMonth();

        $units = $unitIds !== null
            ? Unit::whereIn('id', $unitIds)->where('active', true)->orderBy('name')->get()
            : Unit::where('company_id', $user->company_id)->where('active', true)->orderBy('name')->get();

        $usersQuery = User::where('company_id', $user->company_id)->where('active', true)->orderBy('name');
        if ($unitId) {
            $usersQuery->whereHas('units', fn($q) => $q->where('units.id', $unitId));
        } elseif ($unitIds !== null) {
            $usersQuery->whereHas('units', fn($q) => $q->whereIn('units.id', $unitIds));
        }
        $reportUsers = $usersQuery->with('workSchedule')->get();

        $service = new TimeCalculationService;
        $report  = $reportUsers->map(fn($u) => [
            'user'   => $u,
            'totals' => $service->calculateForPeriod($u, $start, $end),
        ]);

        return view('time-entries.monthly-report', compact('report', 'units', 'unitId', 'month', 'start'));
    }

    public function corrections(Request $request)
    {
        $user    = auth()->user();
        abort_unless($user->isManagerOrAbove(), 403);

        $unitIds = $user->visibleUnitIds();

        $corrections = TimeEntry::with('user', 'unit', 'originalEntry')
            ->where('type', 'correction')
            ->when($unitIds !== null, fn($q) => $q->where(fn($q2) => $q2->whereIn('unit_id', $unitIds)->orWhereNull('unit_id')))
            ->orderByDesc('recorded_at')
            ->paginate(30);

        $units = $unitIds !== null
            ? Unit::whereIn('id', $unitIds)->where('active', true)->orderBy('name')->get()
            : Unit::where('company_id', $user->company_id)->where('active', true)->orderBy('name')->get();

        $users = User::where('company_id', $user->company_id)->where('active', true)->orderBy('name')->get();

        return view('time-entries.corrections', compact('corrections', 'units', 'users'));
    }
}