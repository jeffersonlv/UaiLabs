<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_units', function (Blueprint $table) {
            $table->unsignedBigInteger('activity_id');
            $table->unsignedBigInteger('unit_id');
            $table->primary(['activity_id', 'unit_id']);
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
        });

        // Migrar dados existentes: activities.unit_id → activity_units
        DB::statement("
            INSERT INTO activity_units (activity_id, unit_id)
            SELECT id, unit_id FROM activities
            WHERE unit_id IS NOT NULL
        ");

        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('category_id');
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();
        });

        // Restore: pega a primeira unidade associada (melhor esforço)
        DB::statement("
            UPDATE activities a
            INNER JOIN activity_units au ON au.activity_id = a.id
            SET a.unit_id = au.unit_id
            WHERE a.unit_id IS NULL
        ");

        Schema::dropIfExists('activity_units');
    }
};