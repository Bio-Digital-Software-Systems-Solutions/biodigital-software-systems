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
        Schema::create('event_waitlist', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('event_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('position');
            $table->string('status')->default('waiting'); // waiting, notified, expired, converted, cancelled
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('registration_id')->nullable()->constrained('event_registrations')->nullOnDelete();
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index(['event_id', 'position']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_waitlist');
    }
};
