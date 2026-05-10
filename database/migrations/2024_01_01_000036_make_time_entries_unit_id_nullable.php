<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE time_entries DROP FOREIGN KEY time_entries_unit_id_foreign');
        DB::statement('ALTER TABLE time_entries MODIFY unit_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE time_entries ADD CONSTRAINT time_entries_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE time_entries DROP FOREIGN KEY time_entries_unit_id_foreign');
        DB::statement('ALTER TABLE time_entries MODIFY unit_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE time_entries ADD CONSTRAINT time_entries_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE');
    }
};
