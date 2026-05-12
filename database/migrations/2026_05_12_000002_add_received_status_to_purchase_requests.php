<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM('requested','ordered','purchased','received','cancelled') NOT NULL DEFAULT 'requested'");
    }

    public function down(): void
    {
        DB::statement("UPDATE purchase_requests SET status='purchased' WHERE status='received'");
        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM('requested','ordered','purchased','cancelled') NOT NULL DEFAULT 'requested'");
    }
};
