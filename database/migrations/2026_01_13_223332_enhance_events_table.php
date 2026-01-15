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
        Schema::table('events', function (Blueprint $table) {
            // Add new columns for enhanced event management
            // Note: uuid already exists via HasUuid trait migration
            $table->string('type')->default('other')->after('title');
            $table->string('visibility')->default('public')->after('is_public');
            $table->dateTime('registration_deadline')->nullable()->after('end_date');
            $table->dateTime('early_bird_deadline')->nullable()->after('registration_deadline');
            $table->integer('waitlist_capacity')->nullable()->after('max_participants');
            $table->boolean('waitlist_enabled')->default(false)->after('waitlist_capacity');
            $table->boolean('requires_approval')->default(false)->after('waitlist_enabled');
            $table->string('timezone')->default('Europe/Berlin')->after('end_date');
            $table->string('streaming_url')->nullable()->after('location');
            $table->string('streaming_platform')->nullable()->after('streaming_url');
            $table->json('settings')->nullable()->after('images');
            $table->json('metadata')->nullable()->after('settings');
            $table->foreignId('category_id')->nullable()->after('user_id');
            $table->foreignId('department_id')->nullable()->after('category_id');
            $table->softDeletes();

            // Indexes for performance
            $table->index('type');
            $table->index('visibility');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
            $table->index('registration_deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['visibility']);
            $table->dropIndex(['status']);
            $table->dropIndex(['start_date', 'end_date']);
            $table->dropIndex(['registration_deadline']);

            $table->dropColumn([
                'type',
                'visibility',
                'registration_deadline',
                'early_bird_deadline',
                'waitlist_capacity',
                'waitlist_enabled',
                'requires_approval',
                'timezone',
                'streaming_url',
                'streaming_platform',
                'settings',
                'metadata',
                'category_id',
                'department_id',
                'deleted_at',
            ]);
        });
    }
};
