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
        Schema::create('appointment_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'declined', 'cancelled'])->default('pending');
            $table->string('confirmation_token')->nullable()->unique();
            $table->text('response_message')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('notification_sent_at')->nullable();
            $table->boolean('attended')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate entries
            $table->unique(['appointment_id', 'user_id']);

            // Indexes
            $table->index(['appointment_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('confirmation_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_user');
    }
};