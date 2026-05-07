<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('unit_id');
            $table->enum('type', ['clock_in', 'clock_out', 'correction'])->default('clock_in');
            $table->dateTime('recorded_at');
            $table->unsignedBigInteger('original_entry_id')->nullable();
            $table->text('justification')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('original_entry_id')->references('id')->on('time_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};