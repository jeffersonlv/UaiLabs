<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('module_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['admin', 'manager', 'staff']);
            $table->string('module_key');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'role', 'module_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_permissions');
    }
};
