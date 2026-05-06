<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $users  = User::with('company')
            ->when($search, fn($q) => $q->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
        return view('admin.users.index', compact('users', 'search'));
    }

    public function create()
    {
        $companies = Company::where('active', true)->orderBy('name')->get();
        return view('admin.users.form', ['user' => new User, 'companies' => $companies]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|max:50|unique:users|alpha_dash',
            'email'      => 'required|email|max:255|unique:users',
            'password'   => 'required|string|min:8|confirmed',
            'role'       => 'required|in:superadmin,admin,manager,staff',
            'company_id' => 'nullable|exists:companies,id',
            'active'     => 'boolean',
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['active']   = $request->boolean('active');
        $newUser = User::create($data);
        AuditLogger::crud('user.created', 'user', $newUser->id, $newUser->name, ['role' => $newUser->role]);
        return redirect()->route('admin.users.index')->with('success', 'Usuário criado com sucesso.');
    }

    public function edit(User $user)
    {
        $companies = Company::where('active', true)->orderBy('name')->get();
        return view('admin.users.form', compact('user', 'companies'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|max:50|unique:users,username,' . $user->id . '|alpha_dash',
            'email'      => 'required|email|max:255|unique:users,email,' . $user->id,
            'password'   => 'nullable|string|min:8|confirmed',
            'role'       => 'required|in:superadmin,admin,manager,staff',
            'company_id' => 'nullable|exists:companies,id',
            'active'     => 'boolean',
        ]);
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        $data['active'] = $request->boolean('active');
        $user->update($data);
        AuditLogger::crud('user.updated', 'user', $user->id, $user->name, ['role' => $user->role]);
        return redirect()->route('admin.users.index')->with('success', 'Usuário atualizado.');
    }

    public function toggle(User $user)
    {
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Não é possível desativar o Super Admin.');
        }
        $user->update(['active' => !$user->active]);
        AuditLogger::crud('user.toggled', 'user', $user->id, $user->name, ['ativo' => $user->active]);
        return back()->with('success', 'Acesso do usuário ' . ($user->active ? 'habilitado' : 'desabilitado') . '.');
    }

    public function destroy(User $user)
    {
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Não é possível excluir o Super Admin.');
        }
        AuditLogger::crud('user.deleted', 'user', $user->id, $user->name);
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Usuário excluído.');
    }
}
