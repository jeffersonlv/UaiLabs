<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ModulePermission;
use App\Models\User;
use App\Models\UserModulePermission;
use App\Modules\ModuleRegistry;
use Illuminate\Http\Request;

class ModulePermissionController extends Controller
{
    public function index(Company $company)
    {
        $modules = ModuleRegistry::active();
        $roles   = ['admin', 'manager', 'staff'];

        $rolePerms = ModulePermission::where('company_id', $company->id)
            ->get()
            ->keyBy(fn($p) => "{$p->module_key}.{$p->role}");

        $users = User::where('company_id', $company->id)
            ->whereIn('role', $roles)
            ->orderBy('name')
            ->get();

        $userPerms = UserModulePermission::where('company_id', $company->id)
            ->get()
            ->keyBy(fn($p) => "{$p->user_id}.{$p->module_key}");

        return view('admin.module-permissions.index',
            compact('company', 'modules', 'roles', 'rolePerms', 'users', 'userPerms'));
    }

    public function updateRole(Request $request, Company $company)
    {
        $data = $request->validate(['permissions' => 'required|array']);

        foreach ($data['permissions'] as $moduleKey => $roles) {
            foreach ($roles as $role => $enabled) {
                ModulePermission::updateOrCreate(
                    ['company_id' => $company->id, 'role' => $role, 'module_key' => $moduleKey],
                    ['enabled' => (bool)(int)$enabled]
                );
            }
        }

        return back()->with('success', 'Permissões por papel atualizadas.');
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate(['permissions' => 'required|array']);

        foreach ($data['permissions'] as $moduleKey => $value) {
            if ($value === '' || $value === null) {
                UserModulePermission::where('user_id', $user->id)
                    ->where('module_key', $moduleKey)
                    ->delete();
            } else {
                UserModulePermission::updateOrCreate(
                    ['user_id' => $user->id, 'module_key' => $moduleKey],
                    ['company_id' => $user->company_id, 'enabled' => (bool)(int)$value]
                );
            }
        }

        return back()->with('success', "Permissões de {$user->name} atualizadas.");
    }
}
