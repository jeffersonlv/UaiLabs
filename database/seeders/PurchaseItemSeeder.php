<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurchaseItemSeeder extends Seeder
{
    private array $products = [
        'Café', 'Açúcar', 'Leite', 'Água mineral', 'Coca-Cola', 'Guaraná Antarctica',
        'Papel higiênico', 'Papel toalha', 'Detergente', 'Desinfetante', 'Álcool gel',
        'Luvas descartáveis', 'Farinha de trigo', 'Arroz', 'Feijão', 'Óleo de cozinha',
        'Sal', 'Macarrão', 'Sabão em pó', 'Esponja', 'Pano de chão', 'Saco de lixo',
        'Copo descartável', 'Prato descartável', 'Guardanapo', 'Caneta azul', 'Fita durex',
        'Pilha AA', 'Sabonete', 'Papel A4',
    ];

    public function run(): void
    {
        $companies = \App\Models\Company::with(['units' => fn($q) => $q->where('active', true)])->get();

        foreach ($companies as $company) {
            $units = $company->units;
            if ($units->isEmpty()) continue;

            $users = User::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('active', true)
                ->where('role', '!=', 'superadmin')
                ->get();

            if ($users->isEmpty()) continue;

            $start = Carbon::today()->subMonths(2)->startOfDay();
            $today = Carbon::today();

            $this->seedItems($company, $units, $users, $start, $today);
        }
    }

    private function seedItems($company, $units, $users, Carbon $start, Carbon $today): void
    {
        $rows     = [];
        $counts   = [0, 0, 1, 1, 2, 2, 3, 4];
        $unitArr  = $units->values()->all();
        $userArr  = $users->values()->all();

        for ($day = $start->copy(); $day->lte($today); $day->addDay()) {
            $count = $counts[array_rand($counts)];
            if ($count === 0) continue;

            $shuffled = $this->products;
            shuffle($shuffled);
            $dayProducts = array_slice($shuffled, 0, $count);
            $daysOld     = $today->diffInDays($day);

            foreach ($dayProducts as $product) {
                $unit    = $unitArr[array_rand($unitArr)];
                $creator = $userArr[array_rand($userArr)];

                if ($daysOld > 7) {
                    $isDone = rand(1, 100) <= 90;
                } elseif ($daysOld > 0) {
                    $isDone = rand(1, 100) <= 60;
                } else {
                    $isDone = rand(1, 100) <= 20;
                }

                $doneAt = null;
                $doneBy = null;

                if ($isDone) {
                    $doneAt = $day->copy()->addHours(rand(1, 8))->toDateTimeString();
                    $doneBy = $userArr[array_rand($userArr)]->id;
                }

                $rows[] = [
                    'company_id'   => $company->id,
                    'unit_id'      => $unit->id,
                    'created_by'   => $creator->id,
                    'name'         => $product,
                    'status'       => $isDone ? 'done' : 'pending',
                    'requested_at' => $day->toDateString(),
                    'done_at'      => $doneAt,
                    'done_by'      => $doneBy,
                    'created_at'   => $day->toDateTimeString(),
                    'updated_at'   => $doneAt ?? $day->toDateTimeString(),
                ];
            }
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('purchase_items')->insert($chunk);
        }

        $this->command->info("  [{$company->name}] " . count($rows) . " purchase items gerados.");
    }
}
