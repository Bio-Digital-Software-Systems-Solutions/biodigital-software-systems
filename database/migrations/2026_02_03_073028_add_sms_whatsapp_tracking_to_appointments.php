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
            // Notification channels preference (email, sms, whatsapp)
            $table->json('notification_channels')->nullable()->after('metadata');

            // Reminder tracking timestamps
            $table->timestamp('reminder_sent_at')->nullable()->after('notification_channels');
            $table->timestamp('sms_reminder_sent_at')->nullable()->after('reminder_sent_at');
            $table->timestamp('whatsapp_reminder_sent_at')->nullable()->after('sms_reminder_sent_at');

            // Index for finding appointments needing reminders
            $table->index(['status', 'start_datetime', 'reminder_sent_at'], 'appointments_reminder_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex('appointments_reminder_index');
            $table->dropColumn([
                'notification_channels',
                'reminder_sent_at',
                'sms_reminder_sent_at',
                'whatsapp_reminder_sent_at',
            ]);
        });
    }
};
