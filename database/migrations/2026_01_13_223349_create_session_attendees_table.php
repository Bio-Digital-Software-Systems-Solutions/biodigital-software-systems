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
        Schema::create('session_attendees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->constrained('event_sessions')->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained('event_registrations')->cascadeOnDelete();
            $table->string('status')->default('registered'); // registered, confirmed, attended, no_show
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'registration_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_attendees');
    }
};
