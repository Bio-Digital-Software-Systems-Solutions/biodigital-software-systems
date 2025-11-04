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
            $table->string('confirmation_token')->nullable()->unique();
            $table->timestamp('notification_sent_at')->nullable();
            $table->text('response_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_user', function (Blueprint $table) {
            $table->dropColumn([
                'confirmation_token',
                'notification_sent_at',
                'response_message'
            ]);
        });
    }
};
