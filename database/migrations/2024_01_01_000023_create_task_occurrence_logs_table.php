<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_occurrence_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_occurrence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('action', ['complete', 'reopen']);
            $table->text('justification')->nullable();
            $table->timestamp('done_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_occurrence_logs');
    }
};
