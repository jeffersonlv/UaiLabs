<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Part-Time 20h',  'weekly_hours' => 20.00, 'is_default' => false],
            ['name' => 'Full-Time 40h',  'weekly_hours' => 40.00, 'is_default' => true],
            ['name' => 'Personalizado',  'weekly_hours' => 40.00, 'is_default' => false],
        ];

        Company::all()->each(function (Company $company) use ($defaults) {
            foreach ($defaults as $def) {
                WorkSchedule::firstOrCreate(
                    ['company_id' => $company->id, 'name' => $def['name']],
                    array_merge($def, ['active' => true])
                );
            }
        });
    }
}