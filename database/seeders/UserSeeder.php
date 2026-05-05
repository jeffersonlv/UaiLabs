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

        User::create(['name' => 'Super Admin', 'username' => 'super',  'email' => 'jeffersonlv@gmail.com',  'password' => Hash::make('0F3b8123!@#'), 'role' => 'superadmin', 'company_id' => null]);
        User::create(['name' => 'Jonatas',     'username' => 'jonatas',  'email' => 'jonatas@craicandcoffee.com',  'password' => Hash::make('testeteste'), 'role' => 'admin',      'company_id' => $company->id]);
        User::create(['name' => 'Manager Craic', 'username' => 'managercraic',    'email' => 'manager@craicandcoffee.com',    'password' => Hash::make('testeteste'), 'role' => 'manager',    'company_id' => $company->id]);
        User::create(['name' => 'Staff Craic', 'username' => 'staffcraic',  'email' => 'staff@craicandcoffee.com',  'password' => Hash::make('testeteste'), 'role' => 'staff',      'company_id' => $company->id]);
    }
}
