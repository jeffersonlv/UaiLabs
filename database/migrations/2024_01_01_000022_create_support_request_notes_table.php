<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_request_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->unsignedTinyInteger('intensity')->nullable(); // 1=😠 … 5=😄
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_request_notes');
    }
};
