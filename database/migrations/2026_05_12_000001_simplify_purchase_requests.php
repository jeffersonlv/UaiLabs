<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            // unit_id nullable
            $table->dropForeign(['unit_id']);
            $table->unsignedBigInteger('unit_id')->nullable()->change();
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();

            // quantity → text (nullable)
            $table->string('quantity_text', 100)->nullable()->after('product_name');

            // cancellation_reason
            $table->string('cancellation_reason')->nullable()->after('status_changed_at');
        });

        // Migrar quantity decimal → texto
        DB::statement("UPDATE purchase_requests SET quantity_text = CONCAT(
            TRIM(TRAILING '0' FROM TRIM(TRAILING '.' FROM CAST(quantity AS CHAR))),
            ' ', unit_of_measure
        ) WHERE quantity IS NOT NULL");

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'unit_of_measure']);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->decimal('quantity', 10, 3)->nullable()->after('product_name');
            $table->enum('unit_of_measure', ['un', 'kg', 'g', 'l', 'ml', 'cx'])->default('un')->after('quantity');
            $table->dropColumn(['quantity_text', 'cancellation_reason']);
            $table->unsignedBigInteger('unit_id')->nullable(false)->change();
        });
    }
};
