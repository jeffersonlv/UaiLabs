<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('user_id');
            $table->string('product_name');
            $table->decimal('quantity', 10, 3);
            $table->enum('unit_of_measure', ['un', 'kg', 'g', 'l', 'ml', 'cx'])->default('un');
            $table->text('notes')->nullable();
            $table->enum('status', ['requested', 'ordered', 'purchased', 'cancelled'])->default('requested');
            $table->unsignedBigInteger('status_changed_by')->nullable();
            $table->dateTime('status_changed_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('status_changed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};