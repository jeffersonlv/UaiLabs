<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('body');
            $table->enum('status', ['avaliar', 'fazer', 'perguntar', 'feito'])->default('avaliar');
            $table->boolean('important')->default(false);
            $table->unsignedTinyInteger('priority')->nullable(); // 1=Alta 2=Média 3=Baixa
            $table->text('superadmin_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
    }
};
