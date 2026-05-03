<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $company = \App\Models\Company::first();

        if (! $company) {
            $this->call(CompanySeeder::class);
            $company = \App\Models\Company::first();
        }

        User::create(['name' => 'Super Admin', 'username' => 'super',  'email' => 'super@itech.com',  'password' => Hash::make('password'), 'role' => 'superadmin', 'company_id' => null]);
        User::create(['name' => 'Admin',       'username' => 'admin',  'email' => 'admin@sabor.com',  'password' => Hash::make('password'), 'role' => 'admin',      'company_id' => $company->id]);
        User::create(['name' => 'Gerente Ana', 'username' => 'ana',    'email' => 'ana@sabor.com',    'password' => Hash::make('password'), 'role' => 'manager',    'company_id' => $company->id]);
        User::create(['name' => 'Staff Bruno', 'username' => 'bruno',  'email' => 'bruno@sabor.com',  'password' => Hash::make('password'), 'role' => 'staff',      'company_id' => $company->id]);
    }
}
