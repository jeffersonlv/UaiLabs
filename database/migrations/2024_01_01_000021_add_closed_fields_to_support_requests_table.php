<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('superadmin_note');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete()->after('closed_at');
            $table->unsignedTinyInteger('feedback')->nullable()->after('closed_by'); // 1-5 carinha
        });
    }

    public function down(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by');
            $table->dropColumn(['closed_at', 'feedback']);
        });
    }
};
