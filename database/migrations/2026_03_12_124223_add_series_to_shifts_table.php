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
        if (Schema::hasColumn('shifts', 'series_id')) {
            return;
        }

        Schema::table('shifts', function (Blueprint $table): void {
            $table->foreignId('series_id')->nullable()->constrained('shift_series')->nullOnDelete()->after('weekly_schedule_id');
            $table->index('series_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table): void {
            $table->dropIndex(['series_id']);
            $table->dropConstrainedForeignId('series_id');
        });
    }
};
