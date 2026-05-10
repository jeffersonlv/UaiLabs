<?php
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            ALTER TABLE task_occurrence_logs
            MODIFY action ENUM('complete','reopen','complete_bulk','complete_overdue') NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE task_occurrence_logs
            MODIFY action ENUM('complete','reopen') NOT NULL
        ");
    }
};
