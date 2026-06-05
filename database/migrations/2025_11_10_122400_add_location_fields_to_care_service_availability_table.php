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
        Schema::table('care_service_availability', function (Blueprint $table): void {
            $table->string('location')->nullable()->after('meeting_link');
            $table->string('room')->nullable()->after('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('care_service_availability', function (Blueprint $table): void {
            $table->dropColumn(['location', 'room']);
        });
    }
};
