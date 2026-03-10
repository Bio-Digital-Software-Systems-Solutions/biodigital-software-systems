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
        Schema::table('appointments', function (Blueprint $table): void {
            $table->enum('meeting_mode', ['in_person', 'online', 'hybrid'])->default('in_person')->after('location');
            $table->string('meeting_link')->nullable()->after('meeting_mode');
            $table->enum('meeting_platform', ['zoom', 'google_meet', 'ms_teams', 'other'])->nullable()->after('meeting_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn(['meeting_mode', 'meeting_link', 'meeting_platform']);
        });
    }
};
