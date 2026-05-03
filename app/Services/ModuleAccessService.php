<?php
namespace App\Services;

use App\Models\ModulePermission;
use App\Models\User;
use App\Models\UserModulePermission;

class ModuleAccessService
{
    public function canAccess(User $user, string $moduleKey): bool
    {
        // Superadmin always has full access
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 1. User-level override takes highest priority
        $userPerm = UserModulePermission::where('user_id', $user->id)
            ->where('module_key', $moduleKey)
            ->first();

        if ($userPerm !== null) {
            return $userPerm->enabled;
        }

        // 2. Role-level permission for this company
        if ($user->company_id) {
            $rolePerm = ModulePermission::where('company_id', $user->company_id)
                ->where('role', $user->role)
                ->where('module_key', $moduleKey)
                ->first();

            if ($rolePerm !== null) {
                return $rolePerm->enabled;
            }
        }

        // 3. Default: open unless explicitly restricted
        return true;
    }
}
