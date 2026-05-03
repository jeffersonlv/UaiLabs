<?php
namespace Database\Seeders;

use App\Models\Company;
use App\Models\ModulePermission;
use App\Modules\ModuleRegistry;
use Illuminate\Database\Seeder;

class ModulePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles   = ['admin', 'manager', 'staff'];
        $modules = ModuleRegistry::active();

        foreach (Company::all() as $company) {
            foreach ($modules as $module) {
                foreach ($roles as $role) {
                    ModulePermission::updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'role'       => $role,
                            'module_key' => $module['key'],
                        ],
                        ['enabled' => true]
                    );
                }
            }
        }
    }
}
