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
        Schema::create('care_services', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relations
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('pastor_id')->constrained('users')->onDelete('cascade');

            // Appointment details
            $table->date('appointment_date');
            $table->datetime('appointment_time');
            $table->integer('duration_minutes')->default(60);

            // Status management
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->enum('location_type', ['in_person', 'zoom', 'hybrid'])->default('in_person');
            $table->text('zoom_link')->nullable();

            // Client information
            $table->string('client_name');
            $table->string('client_email');
            $table->string('client_phone')->nullable();
            $table->text('notes')->nullable();

            // Notification tracking
            $table->timestamp('confirmation_sent_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();

            // Cancellation details
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['pastor_id', 'appointment_date']);
            $table->index(['appointment_date', 'appointment_time']);
            $table->index(['status']);
            $table->index(['client_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_services');
    }
};
