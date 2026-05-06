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

        $occurrences = TaskOccurrence::with(['activity.category', 'activity.unit', 'completedBy', 'logs.user'])
            ->where('company_id', $user->company_id)
            ->whereDate('period_start', $today)
            ->when($unitIds !== null, fn($q) => $q->whereIn('unit_id', $unitIds))
            ->get()
            ->sortBy(fn($o) => [
                $o->activity->unit->name ?? 'ZZZ',
                $o->activity->category->name ?? 'ZZZ',
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

    private function generateDailyOccurrences(int $companyId, Carbon $today): void
    {
        Activity::where('company_id', $companyId)->where('active', true)->where('periodicity', 'diario')
            ->each(fn($act) => TaskOccurrence::firstOrCreate(
                ['activity_id' => $act->id, 'period_start' => $today],
                ['company_id' => $companyId, 'unit_id' => $act->unit_id, 'period_end' => $today, 'status' => 'PENDING']
            ));
    }
}
