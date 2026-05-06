<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    private function company() { return auth()->user()->company; }

    public function index()
    {
        $company = $this->company();
        $units   = Unit::where('company_id', $company->id)->orderBy('name')->get();
        return view('units.index', compact('company', 'units'));
    }

    public function create()
    {
        return view('units.form', ['company' => $this->company(), 'unit' => new Unit]);
    }

    public function store(Request $request)
    {
        $company = $this->company();
        $data    = $request->validate([
            'name'    => 'required|string|max:255',
            'type'    => 'required|in:matriz,filial,quiosque,dark_kitchen',
            'address' => 'nullable|string|max:255',
        ]);
        $data['company_id'] = $company->id;
        $data['active']     = true;
        $unit = Unit::create($data);
        AuditLogger::crud('unit.created', 'unit', $unit->id, $unit->name);
        return redirect()->route('units.index')->with('success', 'Unidade criada.');
    }

    public function edit(Unit $unit)
    {
        abort_if($unit->company_id !== $this->company()->id, 403);
        return view('units.form', ['company' => $this->company(), 'unit' => $unit]);
    }

    public function update(Request $request, Unit $unit)
    {
        abort_if($unit->company_id !== $this->company()->id, 403);
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'type'    => 'required|in:matriz,filial,quiosque,dark_kitchen',
            'address' => 'nullable|string|max:255',
            'active'  => 'boolean',
        ]);
        $data['active'] = $request->boolean('active');
        $unit->update($data);
        AuditLogger::crud('unit.updated', 'unit', $unit->id, $unit->name);
        return redirect()->route('units.index')->with('success', 'Unidade atualizada.');
    }

    public function destroy(Unit $unit)
    {
        abort_if($unit->company_id !== $this->company()->id, 403);
        if ($unit->users()->exists()) {
            return back()->with('error', 'Não é possível excluir uma unidade com usuários vinculados.');
        }
        AuditLogger::crud('unit.deleted', 'unit', $unit->id, $unit->name);
        $unit->delete();
        return redirect()->route('units.index')->with('success', 'Unidade excluída.');
    }
}
