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
        if ($unit->users()->exists()) {
            return back()->with('error', 'Não é possível excluir uma unidade com usuários vinculados.');
        }
        AuditLogger::crud('unit.deleted', 'unit', $unit->id, $unit->name);
        $unit->delete();
        return redirect()->route('admin.units.index', $company)->with('success', 'Unidade excluída.');
    }
}
