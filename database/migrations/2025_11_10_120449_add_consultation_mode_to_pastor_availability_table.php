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
        Schema::table('pastor_availability', function (Blueprint $table): void {
            $table->enum('consultation_mode', ['in_person', 'online', 'hybrid'])->default('in_person')->after('is_active');
            $table->string('meeting_link', 500)->nullable()->after('consultation_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pastor_availability', function (Blueprint $table): void {
            $table->dropColumn(['consultation_mode', 'meeting_link']);
        });
    }
};
