<?php
namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $company  = Company::first();
        $abertura = Category::where('name', 'Abertura')->first();
        $limpeza  = Category::where('name', 'Limpeza')->first();
        $estoque  = Category::where('name', 'Estoque')->first();

        $activities = [
            ['title' => 'Abrir cozinha',      'category_id' => $abertura->id, 'sequence_required' => true,  'sequence_order' => 1],
            ['title' => 'Ligar equipamentos', 'category_id' => $abertura->id, 'sequence_required' => true,  'sequence_order' => 2],
            ['title' => 'Preparar estação',   'category_id' => $abertura->id, 'sequence_required' => true,  'sequence_order' => 3],
            ['title' => 'Limpeza do piso',    'category_id' => $limpeza->id,  'sequence_required' => false, 'sequence_order' => null],
            ['title' => 'Higienizar bancadas','category_id' => $limpeza->id,  'sequence_required' => false, 'sequence_order' => null],
            ['title' => 'Verificar estoque',  'category_id' => $estoque->id,  'sequence_required' => false, 'sequence_order' => null],
        ];

        foreach ($activities as $data) {
            Activity::create(array_merge($data, [
                'company_id'  => $company->id,
                'periodicity' => 'diario',
                'active'      => true,
                'created_by'  => \App\Models\User::where('role', 'admin')->first()->id,
            ]));
        }
    }
}
