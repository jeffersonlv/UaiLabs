<?php
namespace Database\Seeders;

use App\Models\Company;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::create(['name' => 'Rede Sabor & Cia', 'slug' => 'sabor-cia', 'email' => 'contato@sabor.com', 'active' => true]);
        Unit::create(['company_id' => $company->id, 'name' => 'Filial Centro', 'address' => 'Rua A, 100', 'active' => true]);
        Unit::create(['company_id' => $company->id, 'name' => 'Filial Norte',  'address' => 'Rua B, 200', 'active' => true]);
    }
}
