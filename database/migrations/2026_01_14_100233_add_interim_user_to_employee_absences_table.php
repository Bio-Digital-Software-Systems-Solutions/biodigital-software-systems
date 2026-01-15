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
        Schema::table('employee_absences', function (Blueprint $table) {
            $table->foreignId('interim_user_id')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->string('interim_notes')->nullable()->after('interim_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_absences', function (Blueprint $table) {
            $table->dropForeign(['interim_user_id']);
            $table->dropColumn(['interim_user_id', 'interim_notes']);
        });
    }
};
