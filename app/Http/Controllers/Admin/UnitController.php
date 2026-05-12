<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Unit;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Company $company)
    {
        $units = Unit::where('company_id', $company->id)->orderBy('name')->get();
        return view('admin.units.index', compact('company', 'units'));
    }

    public function create(Company $company)
    {
        return view('admin.units.form', ['company' => $company, 'unit' => new Unit]);
    }

    public function store(Request $request, Company $company)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'type'    => 'required|in:matriz,filial,quiosque,dark_kitchen',
            'address' => 'nullable|string|max:255',
        ]);
        $data['company_id'] = $company->id;
        $data['active']     = true;
        $unit = Unit::create($data);
        AuditLogger::crud('unit.created', 'unit', $unit->id, $unit->name);
        return redirect()->route('admin.units.index', $company)->with('success', 'Unidade criada.');
    }

    public function edit(Company $company, Unit $unit)
    {
        abort_if($unit->company_id !== $company->id, 403);
        return view('admin.units.form', compact('company', 'unit'));
    }

    public function update(Request $request, Company $company, Unit $unit)
    {
        abort_if($unit->company_id !== $company->id, 403);
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'type'    => 'required|in:matriz,filial,quiosque,dark_kitchen',
            'address' => 'nullable|string|max:255',
            'active'  => 'boolean',
        ]);
        $data['active'] = $request->boolean('active');
        $unit->update($data);
        AuditLogger::crud('unit.updated', 'unit', $unit->id, $unit->name);
        return redirect()->route('admin.units.index', $company)->with('success', 'Unidade atualizada.');
    }

    public function destroy(Company $company, Unit $unit)
    {
        abort_if($unit->company_id !== $company->id, 403);

        $blocks = [];

        $userCount = $unit->users()->count();
        if ($userCount) {
            $blocks[] = "{$userCount} usuário(s) atribuído(s) — remova em Usuários → Editar";
        }

        $activityCount = $unit->activities()->count();
        if ($activityCount) {
            $blocks[] = "{$activityCount} atividade(s) vinculada(s) — remova em Atividades → Unidades";
        }

        $occurrenceCount = \App\Models\TaskOccurrence::where('unit_id', $unit->id)->count();
        if ($occurrenceCount) {
            $blocks[] = "{$occurrenceCount} ocorrência(s) de tarefas registradas";
        }

        $timeCount = \App\Models\TimeEntry::where('unit_id', $unit->id)->count();
        if ($timeCount) {
            $blocks[] = "{$timeCount} registro(s) de ponto vinculados";
        }

        $shiftCount = \App\Models\Shift::where('unit_id', $unit->id)->count();
        if ($shiftCount) {
            $blocks[] = "{$shiftCount} turno(s) de escala vinculados";
        }

        if ($blocks) {
            $msg = 'Não é possível excluir "' . $unit->name . '". Vínculos pendentes: ' . implode('; ', $blocks) . '.';
            return back()->with('error', $msg);
        }

        AuditLogger::crud('unit.deleted', 'unit', $unit->id, $unit->name);
        $unit->delete();
        return redirect()->route('admin.units.index', $company)->with('success', 'Unidade excluída.');
    }
}
