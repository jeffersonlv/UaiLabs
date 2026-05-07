<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * task_occurrences already has unit_id. This migration ensures
     * the column is nullable to support "Geral" activities (no unit).
     */
    public function up(): void
    {
        Schema::table('task_occurrences', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // no revert needed — nullability relaxation is safe
    }
};