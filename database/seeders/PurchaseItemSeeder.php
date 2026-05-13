<?php

namespace Database\Seeders;

use App\Models\PurchaseItem;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

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
        $rows = [];
        $now  = now();

        for ($day = $start->copy(); $day->lte($today); $day->addDay()) {
            // 0-4 items per day, skip ~20% of days
            $count = fake()->randomElement([0, 0, 1, 1, 2, 2, 3, 4]);
            if ($count === 0) continue;

            $dayProducts = fake()->randomElements($this->products, min($count, count($this->products)));
            $daysOld     = $today->diffInDays($day);

            foreach ($dayProducts as $product) {
                $unit      = $units->random();
                $creator   = $users->random();
                $requestedAt = $day->toDateString();

                // Chance of being done increases with age
                if ($daysOld > 7) {
                    $isDone = fake()->boolean(90);
                } elseif ($daysOld > 0) {
                    $isDone = fake()->boolean(60);
                } else {
                    $isDone = fake()->boolean(20);
                }

                $doneAt = null;
                $doneBy = null;

                if ($isDone) {
                    $doneHoursAfter = fake()->numberBetween(1, 8);
                    $doneAt = $day->copy()->addHours($doneHoursAfter)->toDateTimeString();
                    $doneBy = $users->random()->id;
                }

                $rows[] = [
                    'company_id'   => $company->id,
                    'unit_id'      => $unit->id,
                    'created_by'   => $creator->id,
                    'name'         => $product,
                    'status'       => $isDone ? 'done' : 'pending',
                    'requested_at' => $requestedAt,
                    'done_at'      => $doneAt,
                    'done_by'      => $doneBy,
                    'created_at'   => $day->toDateTimeString(),
                    'updated_at'   => $doneAt ?? $day->toDateTimeString(),
                ];
            }
        }

        // Insert in chunks to avoid query size limits
        foreach (array_chunk($rows, 100) as $chunk) {
            \Illuminate\Support\Facades\DB::table('purchase_items')->insert($chunk);
        }

        $this->command->info("  [{$company->name}] " . count($rows) . " purchase items gerados.");
    }
}
