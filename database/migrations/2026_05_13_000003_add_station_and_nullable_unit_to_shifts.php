<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('station_id')->nullable()->after('type')
                ->constrained()->nullOnDelete();
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->change();
        });

        Schema::table('shift_templates', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['station_id']);
            $table->dropColumn('station_id');
            $table->foreignId('unit_id')->nullable(false)->change();
        });

        Schema::table('shift_templates', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable(false)->change();
        });
    }
};
