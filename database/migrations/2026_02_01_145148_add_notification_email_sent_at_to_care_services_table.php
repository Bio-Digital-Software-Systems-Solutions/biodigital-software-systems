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
            $table->timestamp('notification_email_sent_at')->nullable()->after('reminder_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('care_services', function (Blueprint $table): void {
            $table->dropColumn('notification_email_sent_at');
        });
    }
};
