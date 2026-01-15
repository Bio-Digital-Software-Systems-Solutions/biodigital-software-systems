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
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('registration_number')->unique();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('event_tickets')->nullOnDelete();
            $table->foreignId('promo_code_id')->nullable()->constrained('event_promo_codes')->nullOnDelete();

            // Registrant info (for guests or override user data)
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();

            // Registration details
            $table->string('status')->default('pending'); // pending, confirmed, waitlisted, cancelled, checked_in, no_show
            $table->string('participant_role')->default('attendee');
            $table->integer('quantity')->default(1);

            // Pricing
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');

            // Additional data
            $table->json('form_answers')->nullable(); // Custom form responses
            $table->json('dietary_requirements')->nullable();
            $table->json('accessibility_needs')->nullable();
            $table->text('special_requests')->nullable();
            $table->json('metadata')->nullable();

            // QR Code for check-in
            $table->string('qr_code')->nullable();

            // Timestamps
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'status']);
            $table->index(['event_id', 'user_id']);
            $table->index('registration_number');
            $table->index('email');
            $table->index('qr_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
