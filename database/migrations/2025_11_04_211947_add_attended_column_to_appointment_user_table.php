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
        Schema::table('appointment_user', function (Blueprint $table) {
            if (!Schema::hasColumn('appointment_user', 'attended')) {
                $table->boolean('attended')->default(false)->after('notification_sent_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_user', function (Blueprint $table) {
            if (Schema::hasColumn('appointment_user', 'attended')) {
                $table->dropColumn('attended');
            }
        });
    }
};
