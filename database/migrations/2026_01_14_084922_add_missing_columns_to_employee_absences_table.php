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
            // Add missing columns for Absence model compatibility
            if (!Schema::hasColumn('employee_absences', 'is_half_day_start')) {
                $table->boolean('is_half_day_start')->default(false)->after('is_full_day');
            }
            if (!Schema::hasColumn('employee_absences', 'is_half_day_end')) {
                $table->boolean('is_half_day_end')->default(false)->after('is_half_day_start');
            }
            if (!Schema::hasColumn('employee_absences', 'days_count')) {
                $table->decimal('days_count', 5, 2)->nullable()->after('end_time');
            }
            if (!Schema::hasColumn('employee_absences', 'document_path')) {
                $table->string('document_path')->nullable()->after('reason');
            }
            if (!Schema::hasColumn('employee_absences', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_absences', function (Blueprint $table) {
            $table->dropColumn([
                'is_half_day_start',
                'is_half_day_end',
                'days_count',
                'document_path',
                'rejected_at',
            ]);
        });
    }
};
