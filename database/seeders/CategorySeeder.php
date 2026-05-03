<?php
namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        foreach (['Abertura','Fechamento','Limpeza','Cozinha','Segurança','Estoque','Manutenção'] as $name) {
            Category::create(['company_id' => $company->id, 'name' => $name, 'active' => true]);
        }
    }
}
