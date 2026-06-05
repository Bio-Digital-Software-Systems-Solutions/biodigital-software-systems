<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('care_services', function (Blueprint $table): void {
            $table->foreignId('transferred_from_id')->nullable()->after('pastor_id')->constrained('users')->nullOnDelete();
            $table->foreignId('transferred_to_id')->nullable()->after('transferred_from_id')->constrained('users')->nullOnDelete();
            $table->timestamp('transferred_at')->nullable()->after('transferred_to_id');
            $table->text('transfer_reason')->nullable()->after('transferred_at');

            $table->index('transferred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('care_services', function (Blueprint $table): void {
            $table->dropIndex(['transferred_at']);
            $table->dropForeign(['transferred_from_id']);
            $table->dropForeign(['transferred_to_id']);
            $table->dropColumn(['transferred_from_id', 'transferred_to_id', 'transferred_at', 'transfer_reason']);
        });
    }
};
