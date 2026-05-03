<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            ActivitySeeder::class,
            Company2Seeder::class,
            ModulePermissionSeeder::class,
        ]);
    }
}
