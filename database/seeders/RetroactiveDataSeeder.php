<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\PurchaseRequest;
use App\Models\Shift;
use App\Models\TaskOccurrence;
use App\Models\TimeEntry;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RetroactiveDataSeeder extends Seeder
{
    public function run(): void
    {
        $companies = \App\Models\Company::with(['units' => fn($q) => $q->where('active', true)])->get();

        foreach ($companies as $company) {
            $units = $company->units;
            if ($units->isEmpty()) continue;

            $users = User::where('company_id', $company->id)->where('active', true)->get();
            if ($users->isEmpty()) continue;

            $managers = $users->filter(fn($u) => $u->isManagerOrAbove());
            $staff    = $users->filter(fn($u) => ! $u->isManagerOrAbove());
            $allUsers = $users;

            $firstManager = $managers->first() ?? $users->first();

            $start = Carbon::today()->subMonths(2)->startOfDay();
            $today = Carbon::today()->endOfDay();

            $this->seedShifts($company, $units, $allUsers, $firstManager, $start, $today);
            $this->seedPurchaseRequests($company, $units, $allUsers, $firstManager, $start, $today);
            $this->seedTimeEntries($company, $units, $allUsers, $start, $today);
            $this->seedTaskOccurrences($company, $units, $allUsers, $firstManager, $start, $today);
        }
    }

    // -------------------------------------------------------------------------
    // Shifts — escala de funcionários
    // -------------------------------------------------------------------------
    private function seedShifts($company, $units, $users, $createdBy, Carbon $start, Carbon $end): void
    {
        $types = ['work', 'work', 'work', 'work', 'vacation', 'leave', 'holiday'];

        $current = $start->copy()->startOfWeek();
        while ($current->lte($end)) {
            foreach ($units as $unit) {
                $unitUsers = $users->filter(fn($u) =>
                    $u->units->pluck('id')->contains($unit->id)
                );
                if ($unitUsers->isEmpty()) {
                    $unitUsers = $users;
                }

                foreach ($unitUsers as $user) {
                    // ~80% chance of having a shift on any given weekday
                    if ($current->isWeekend() || rand(1, 10) > 8) continue;

                    $type = $types[array_rand($types)];

                    [$startH, $endH] = match (rand(0, 2)) {
                        0 => [8, 17],
                        1 => [9, 18],
                        2 => [12, 21],
                    };

                    $shiftStart = $current->copy()->setHour($startH)->setMinute(0)->setSecond(0);
                    $shiftEnd   = $current->copy()->setHour($endH)->setMinute(0)->setSecond(0);

                    // Skip if overlap already exists
                    $exists = Shift::withoutGlobalScopes()
                        ->where('company_id', $company->id)
                        ->where('unit_id', $unit->id)
                        ->where('user_id', $user->id)
                        ->where('start_at', '<', $shiftEnd)
                        ->where('end_at', '>', $shiftStart)
                        ->exists();

                    if ($exists) continue;

                    Shift::withoutGlobalScopes()->create([
                        'company_id' => $company->id,
                        'unit_id'    => $unit->id,
                        'user_id'    => $user->id,
                        'start_at'   => $shiftStart,
                        'end_at'     => $shiftEnd,
                        'type'       => $type,
                        'created_by' => $createdBy->id,
                    ]);
                }
            }
            $current->addDay();
        }
    }

    // -------------------------------------------------------------------------
    // Purchase requests — pedidos de compra
    // -------------------------------------------------------------------------
    private function seedPurchaseRequests($company, $units, $users, $manager, Carbon $start, Carbon $end): void
    {
        $products = [
            ['Café 500g', '3 pacotes'],
            ['Açúcar refinado', '5kg'],
            ['Leite integral', '12 unidades'],
            ['Papel toalha', '2 pacotes'],
            ['Detergente', '6 unidades'],
            ['Guardanapos', '4 pacotes'],
            ['Óleo de cozinha', '2 galões'],
            ['Farinha de trigo', '10kg'],
            ['Embalagens descartáveis', '200 unidades'],
            ['Luvas descartáveis', '1 caixa'],
            ['Água mineral 500ml', '2 fardos'],
            ['Refrigerante 2L', '6 unidades'],
        ];

        $statusFlow = ['requested', 'ordered', 'purchased', 'received'];

        foreach ($units as $unit) {
            $unitUsers = $users->filter(fn($u) =>
                $u->units->pluck('id')->contains($unit->id)
            );
            $requesters = $unitUsers->isNotEmpty() ? $unitUsers : $users;

            // ~3 a 6 pedidos por semana por unidade nos últimos 2 meses
            $current = $start->copy();
            while ($current->lte($end)) {
                $perWeek = rand(3, 6);
                for ($i = 0; $i < $perWeek; $i++) {
                    $requester = $requesters->random();
                    $product   = $products[array_rand($products)];
                    $createdAt = $current->copy()->addDays(rand(0, 6))->setHour(rand(8, 17));

                    if ($createdAt->gt($end)) break;

                    // Decide status based on age — older = more advanced
                    $daysAgo = $createdAt->diffInDays(Carbon::today());
                    if ($daysAgo > 30) {
                        $maxIdx = 3; // pode ser qualquer status incluindo received
                    } elseif ($daysAgo > 14) {
                        $maxIdx = rand(1, 3);
                    } elseif ($daysAgo > 7) {
                        $maxIdx = rand(0, 2);
                    } else {
                        $maxIdx = rand(0, 1);
                    }

                    // 10% chance cancelled
                    if (rand(1, 10) === 1) {
                        $status = 'cancelled';
                        $cancelReasons = array_keys(PurchaseRequest::CANCEL_REASONS);
                        $cancelReason  = $cancelReasons[array_rand($cancelReasons)];
                    } else {
                        $status = $statusFlow[$maxIdx];
                        $cancelReason = null;
                    }

                    PurchaseRequest::withoutGlobalScopes()->create([
                        'company_id'          => $company->id,
                        'unit_id'             => $unit->id,
                        'user_id'             => $requester->id,
                        'product_name'        => $product[0],
                        'quantity_text'       => $product[1],
                        'status'              => $status,
                        'status_changed_by'   => $manager->id,
                        'status_changed_at'   => $createdAt,
                        'cancellation_reason' => $cancelReason,
                        'created_at'          => $createdAt,
                        'updated_at'          => $createdAt,
                    ]);
                }
                $current->addWeek();
            }
        }
    }

    // -------------------------------------------------------------------------
    // Time entries — registros de ponto
    // -------------------------------------------------------------------------
    private function seedTimeEntries($company, $units, $users, Carbon $start, Carbon $end): void
    {
        foreach ($users as $user) {
            $unit = $user->units->first();

            $current = $start->copy()->startOfDay();
            while ($current->lte($end)) {
                if ($current->isWeekend()) {
                    $current->addDay();
                    continue;
                }

                // 90% chance of clocking in on any weekday
                if (rand(1, 10) > 9) {
                    $current->addDay();
                    continue;
                }

                // Clock in: between 07:45 and 08:30
                $clockIn  = $current->copy()->setHour(8)->setMinute(0)->addMinutes(rand(-15, 30));

                // Clock out: between 17:00 and 18:15
                $clockOut = $current->copy()->setHour(17)->addMinutes(rand(0, 75));

                $entries = [
                    ['clock_in',  $clockIn],
                    ['clock_out', $clockOut],
                ];

                foreach ($entries as [$type, $time]) {
                    TimeEntry::withoutGlobalScopes()->create([
                        'company_id'  => $company->id,
                        'user_id'     => $user->id,
                        'unit_id'     => $unit?->id,
                        'type'        => $type,
                        'recorded_at' => $time,
                        'created_at'  => $time,
                        'updated_at'  => $time,
                    ]);
                }

                $current->addDay();
            }
        }
    }

    // -------------------------------------------------------------------------
    // Task occurrences — checklist
    // -------------------------------------------------------------------------
    private function seedTaskOccurrences($company, $units, $users, $manager, Carbon $start, Carbon $end): void
    {
        $activities = Activity::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('active', true)
            ->with('units')
            ->get();

        if ($activities->isEmpty()) return;

        $periodicityMap = [
            'daily'   => 1,
            'weekly'  => 7,
            'monthly' => 30,
        ];

        foreach ($activities as $activity) {
            $targetUnits = $activity->units->isNotEmpty()
                ? $activity->units
                : $units;

            $stepDays = $periodicityMap[$activity->periodicity] ?? 1;

            foreach ($targetUnits as $unit) {
                $unitUsers = $users->filter(fn($u) =>
                    $u->units->pluck('id')->contains($unit->id)
                );
                $doers = $unitUsers->isNotEmpty() ? $unitUsers : $users;

                $current = $start->copy()->startOfDay();
                while ($current->lte($end)) {
                    $periodStart = $current->copy();
                    $periodEnd   = $current->copy()->addDays($stepDays - 1)->endOfDay();

                    // Skip weekends for daily tasks
                    if ($stepDays === 1 && $current->isWeekend()) {
                        $current->addDay();
                        continue;
                    }

                    // Check if occurrence already exists
                    $exists = TaskOccurrence::withoutGlobalScopes()
                        ->where('company_id', $company->id)
                        ->where('unit_id', $unit->id)
                        ->where('activity_id', $activity->id)
                        ->where('period_start', $periodStart)
                        ->exists();

                    if ($exists) {
                        $current->addDays($stepDays);
                        continue;
                    }

                    // 85% completed, 10% missed (pending), 5% justification
                    $rand = rand(1, 100);
                    if ($rand <= 85) {
                        $status      = 'DONE';
                        $completedBy = $doers->random()->id;
                        $completedAt = $periodStart->copy()->setHour(rand(8, 11))->addMinutes(rand(0, 59));
                    } elseif ($rand <= 90) {
                        $status      = 'OVERDUE';
                        $completedBy = null;
                        $completedAt = null;
                    } else {
                        $status      = 'DONE';
                        $completedBy = $doers->random()->id;
                        $completedAt = $periodStart->copy()->setHour(rand(8, 11));
                    }

                    TaskOccurrence::withoutGlobalScopes()->create([
                        'company_id'   => $company->id,
                        'unit_id'      => $unit->id,
                        'activity_id'  => $activity->id,
                        'period_start' => $periodStart,
                        'period_end'   => $periodEnd,
                        'status'       => $status,
                        'completed_by' => $completedBy,
                        'completed_at' => $completedAt,
                        'created_at'   => $periodStart,
                        'updated_at'   => $completedAt ?? $periodStart,
                    ]);

                    $current->addDays($stepDays);
                }
            }
        }
    }
}
