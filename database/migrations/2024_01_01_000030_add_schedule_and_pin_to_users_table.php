<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('work_schedule_id')->nullable()->after('company_id');
            $table->string('pin')->nullable()->after('password');
            $table->boolean('pin_reset_required')->default(false)->after('pin');

            $table->foreign('work_schedule_id')->references('id')->on('work_schedules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['work_schedule_id']);
            $table->dropColumn(['work_schedule_id', 'pin', 'pin_reset_required']);
        });
    }
};