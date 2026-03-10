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
        // Add Telegram fields to users table
        Schema::table('users', function (Blueprint $table): void {
            $table->string('telegram_chat_id')->nullable()->after('phone_number');
            $table->string('telegram_username')->nullable()->after('telegram_chat_id');
            $table->boolean('telegram_notifications')->default(false)->after('push_notifications');
        });

        // Add Telegram reminder tracking to appointments table
        Schema::table('appointments', function (Blueprint $table): void {
            $table->timestamp('telegram_reminder_sent_at')->nullable()->after('whatsapp_reminder_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['telegram_chat_id', 'telegram_username', 'telegram_notifications']);
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('telegram_reminder_sent_at');
        });
    }
};
