<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $search    = $request->input('search');
        $companies = Company::withCount('users')
            ->when($search, fn($q) => $q->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
        return view('admin.companies.index', compact('companies', 'search'));
    }

    public function create()
    {
        return view('admin.companies.form', ['company' => new Company]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'slug'   => 'required|string|max:255|unique:companies',
            'email'  => 'nullable|email|max:255',
            'phone'  => 'nullable|string|max:50',
            'active' => 'boolean',
        ]);
        $data['active'] = $request->boolean('active');
        $company = Company::create($data);
        AuditLogger::crud('company.created', 'company', $company->id, $company->name);
        return redirect()->route('admin.companies.index')->with('success', 'Empresa criada com sucesso.');
    }

    public function edit(Company $company)
    {
        return view('admin.companies.form', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'slug'   => 'required|string|max:255|unique:companies,slug,' . $company->id,
            'email'  => 'nullable|email|max:255',
            'phone'  => 'nullable|string|max:50',
            'active' => 'boolean',
        ]);
        $data['active'] = $request->boolean('active');
        $company->update($data);
        AuditLogger::crud('company.updated', 'company', $company->id, $company->name);
        return redirect()->route('admin.companies.index')->with('success', 'Empresa atualizada.');
    }

    public function toggle(Company $company)
    {
        $company->update(['active' => !$company->active]);
        AuditLogger::crud('company.toggled', 'company', $company->id, $company->name, ['ativo' => $company->active]);
        return back()->with('success', 'Status da empresa atualizado.');
    }

    public function destroy(Company $company)
    {
        if ($company->users()->exists()) {
            return back()->with('error', 'Não é possível excluir uma empresa com usuários vinculados.');
        }
        AuditLogger::crud('company.deleted', 'company', $company->id, $company->name);
        $company->delete();
        return redirect()->route('admin.companies.index')->with('success', 'Empresa excluída.');
    }
}
