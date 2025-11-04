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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('location')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->enum('type', ['individual', 'group', 'consultation', 'meeting'])->default('individual');
            $table->enum('visibility', ['private', 'public'])->default('private');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Creator/Organizer
            $table->nullableMorphs('appointmentable'); // Can be linked to Event, Training, etc.
            $table->json('metadata')->nullable(); // Additional data (reminders, notes, etc.)
            $table->timestamps();

            // Indexes for performance
            $table->index(['start_datetime', 'end_datetime']);
            $table->index(['status', 'start_datetime']);
            $table->index(['user_id', 'status']);
            // appointmentable_type and appointmentable_id index is automatically created by nullableMorphs()
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};