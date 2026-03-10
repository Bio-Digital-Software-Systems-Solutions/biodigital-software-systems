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
        Schema::create('event_checkins', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('registration_id')->constrained('event_registrations')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('event_sessions')->cascadeOnDelete();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('check_type')->default('entry'); // entry, exit, session
            $table->string('method')->default('qr_code'); // qr_code, manual, nfc
            $table->string('device_id')->nullable();
            $table->string('location')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('checked_in_at');
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();

            $table->index(['registration_id', 'check_type']);
            $table->index('checked_in_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_checkins');
    }
};
