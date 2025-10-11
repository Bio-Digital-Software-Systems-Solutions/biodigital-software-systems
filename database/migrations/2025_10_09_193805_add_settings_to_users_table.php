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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_notifications')->default(true)->after('remember_token');
            $table->boolean('sms_notifications')->default(false)->after('email_notifications');
            $table->boolean('push_notifications')->default(true)->after('sms_notifications');
            $table->boolean('newsletter')->default(false)->after('push_notifications');
            $table->boolean('event_reminders')->default(true)->after('newsletter');
            $table->boolean('training_updates')->default(true)->after('event_reminders');
            $table->boolean('message_notifications')->default(true)->after('training_updates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_notifications',
                'sms_notifications',
                'push_notifications',
                'newsletter',
                'event_reminders',
                'training_updates',
                'message_notifications',
            ]);
        });
    }
};
