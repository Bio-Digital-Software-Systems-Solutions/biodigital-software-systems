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
            // Notification preferences - which channels to use for reminders
            $table->json('notification_channels')->nullable()->after('reminder_sent_at')
                ->comment('JSON array of notification channels: email, sms, whatsapp');

            // Track when SMS/WhatsApp reminders were sent
            $table->timestamp('sms_reminder_sent_at')->nullable()->after('notification_channels');
            $table->timestamp('whatsapp_reminder_sent_at')->nullable()->after('sms_reminder_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('care_services', function (Blueprint $table): void {
            $table->dropColumn([
                'notification_channels',
                'sms_reminder_sent_at',
                'whatsapp_reminder_sent_at',
            ]);
        });
    }
};
