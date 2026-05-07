<?php

namespace App\Http\Controllers;

use App\Models\WorkSchedule;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class WorkScheduleController extends Controller
{
    public function index()
    {
        $schedules = WorkSchedule::orderBy('name')->get();
        return view('work-schedules.index', compact('schedules'));
    }

    public function create()
    {
        return view('work-schedules.form', ['schedule' => new WorkSchedule]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:100',
            'weekly_hours' => 'required|numeric|min:1|max:168',
        ]);

        $schedule = WorkSchedule::create($request->only('name', 'weekly_hours') + [
            'company_id' => auth()->user()->company_id,
            'is_default' => $request->boolean('is_default'),
            'active'     => true,
        ]);

        AuditLogger::crud('work_schedule.created', 'work_schedule', $schedule->id, $schedule->name);
        return redirect()->route('work-schedules.index')->with('success', 'Tipo de turno criado.');
    }

    public function edit(WorkSchedule $workSchedule)
    {
        abort_if($workSchedule->company_id !== auth()->user()->company_id, 403);
        return view('work-schedules.form', ['schedule' => $workSchedule]);
    }

    public function update(Request $request, WorkSchedule $workSchedule)
    {
        abort_if($workSchedule->company_id !== auth()->user()->company_id, 403);
        $request->validate([
            'name'         => 'required|string|max:100',
            'weekly_hours' => 'required|numeric|min:1|max:168',
        ]);

        $workSchedule->update($request->only('name', 'weekly_hours') + [
            'is_default' => $request->boolean('is_default'),
            'active'     => $request->boolean('active'),
        ]);

        AuditLogger::crud('work_schedule.updated', 'work_schedule', $workSchedule->id, $workSchedule->name);
        return redirect()->route('work-schedules.index')->with('success', 'Tipo de turno atualizado.');
    }
}