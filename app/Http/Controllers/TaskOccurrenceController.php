<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\TaskOccurrence;
use App\Models\TaskOccurrenceLog;
use App\Models\Unit;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TaskOccurrenceController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (! $user->company_id) {
            return redirect()->route('dashboard')->with('error', 'O Super Admin não está vinculado a nenhuma empresa.');
        }

        $today = Carbon::today();
        $this->generateDailyOccurrences($user->company_id, $today);

        $unitIds = $user->visibleUnitIds();

        $occurrences = TaskOccurrence::with(['activity.category', 'activity.subcategory', 'activity.units', 'completedBy', 'logs.user'])
            ->where('company_id', $user->company_id)
            ->whereDate('period_start', $today)
            ->when($unitIds !== null, fn($q) => $q->whereIn('unit_id', $unitIds))
            ->get()
            ->sortBy(fn($o) => [
                $o->unit?->name ?? ($o->activity->units->first()?->name ?? 'ZZZ'),
                $o->activity->category->name ?? 'ZZZ',
                $o->activity->subcategory?->order ?? 999,
                $o->activity->subcategory?->name ?? 'ZZZ',
                in_array($o->status, ['DONE', 'REOPENED']) ? 1 : 0,
                $o->activity->sequence_required ? 0 : 1,
                $o->activity->sequence_order ?? 999,
                $o->activity->title,
            ]);

        $visibleUnits = $unitIds !== null
            ? Unit::whereIn('id', $unitIds)->orderBy('name')->get()
            : Unit::where('company_id', $user->company_id)->orderBy('name')->get();

        return view('checklist.index', compact('occurrences', 'visibleUnits'));
    }

    public function complete(Request $request, TaskOccurrence $occurrence)
    {
        abort_if($occurrence->company_id !== auth()->user()->company_id, 403);

        $isReexecution = $occurrence->status === 'DONE';
        if ($isReexecution) {
            $request->validate(['justification' => 'required|string|min:5']);
        }

        if ($occurrence->activity->sequence_required) {
            $blocked = TaskOccurrence::whereHas('activity', fn($q) =>
                $q->where('sequence_required', true)->where('sequence_order', '<', $occurrence->activity->sequence_order)
            )->where('company_id', $occurrence->company_id)
             ->whereDate('period_start', Carbon::today())
             ->where('status', '!=', 'DONE')
             ->exists();

            if ($blocked) return back()->with('error', 'Execute as tarefas anteriores da sequência primeiro.');
        }

        $occurrence->update([
            'status'        => $isReexecution ? 'REOPENED' : 'DONE',
            'completed_by'  => auth()->id(),
            'completed_at'  => now(),
            'justification' => $request->justification ?? $occurrence->justification,
        ]);

        TaskOccurrenceLog::create([
            'task_occurrence_id' => $occurrence->id,
            'user_id'            => auth()->id(),
            'action'             => $isReexecution ? 'reopen' : 'complete',
            'justification'      => $isReexecution ? $request->justification : null,
        ]);

        if ($isReexecution) {
            AuditLogger::taskReopen($occurrence, $request->justification);
        } else {
            AuditLogger::taskComplete($occurrence);
        }

        return back()->with('success', 'Tarefa registrada.');
    }

    /** Bulk complete for multiple occurrences (from "marcar todos" button). */
    public function bulkComplete(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $user    = auth()->user();
        $today   = Carbon::today();
        $unitIds = $user->visibleUnitIds();
        $count   = 0;

        $occurrences = TaskOccurrence::whereIn('id', $request->ids)
            ->where('company_id', $user->company_id)
            ->whereDate('period_start', $today)
            ->whereIn('status', ['PENDING', 'OVERDUE'])
            ->when($unitIds !== null, fn($q) => $q->whereIn('unit_id', $unitIds))
            ->get();

        foreach ($occurrences as $occurrence) {
            $action = $occurrence->status === 'OVERDUE' ? 'complete_overdue' : 'complete_bulk';

            $occurrence->update([
                'status'       => 'DONE',
                'completed_by' => auth()->id(),
                'completed_at' => now(),
            ]);

            TaskOccurrenceLog::create([
                'task_occurrence_id' => $occurrence->id,
                'user_id'            => auth()->id(),
                'action'             => $action,
                'justification'      => null,
            ]);

            $count++;
        }

        return response()->json(['completed' => $count]);
    }

    /** Occurrence history (JSON) for the modal. */
    public function history(TaskOccurrence $occurrence)
    {
        abort_if($occurrence->company_id !== auth()->user()->company_id, 403);

        $logs = $occurrence->logs()->with('user')->get()->map(fn($log) => [
            'action'        => $log->action,
            'user'          => $log->user?->name,
            'justification' => $log->justification,
            'done_at'       => $log->done_at?->format('d/m/Y H:i'),
        ]);

        return response()->json([
            'activity' => $occurrence->activity->title,
            'status'   => $occurrence->status,
            'logs'     => $logs,
        ]);
    }

    private function generateDailyOccurrences(int $companyId, Carbon $today): void
    {
        // Load all active daily activities with their units (N:N)
        $activities = Activity::where('company_id', $companyId)
            ->where('active', true)
            ->where('periodicity', 'diario')
            ->with('units')
            ->get();

        foreach ($activities as $act) {
            if ($act->units->isEmpty()) {
                // Geral — one occurrence, no unit
                TaskOccurrence::firstOrCreate(
                    ['activity_id' => $act->id, 'period_start' => $today, 'unit_id' => null],
                    ['company_id' => $companyId, 'unit_id' => null, 'period_end' => $today, 'status' => 'PENDING']
                );
            } else {
                foreach ($act->units as $unit) {
                    TaskOccurrence::firstOrCreate(
                        ['activity_id' => $act->id, 'period_start' => $today, 'unit_id' => $unit->id],
                        ['company_id' => $companyId, 'unit_id' => $unit->id, 'period_end' => $today, 'status' => 'PENDING']
                    );
                }
            }
        }
    }
}