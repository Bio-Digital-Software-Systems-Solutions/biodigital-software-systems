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
        Schema::table('event_user', function (Blueprint $table): void {
            // Add reference to new registration system
            $table->foreignId('registration_id')->nullable()->after('user_id')
                ->constrained('event_registrations')->nullOnDelete();

            // Add participant role
            $table->string('role')->default('attendee')->after('attended');

            // Add notes
            $table->text('notes')->nullable()->after('role');

            // Index for performance
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_user', function (Blueprint $table): void {
            $table->dropForeign(['registration_id']);
            $table->dropIndex(['role']);
            $table->dropColumn(['registration_id', 'role', 'notes']);
        });
    }
};
