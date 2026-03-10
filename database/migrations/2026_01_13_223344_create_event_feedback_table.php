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
        Schema::create('event_feedback', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('event_sessions')->cascadeOnDelete();
            $table->foreignId('registration_id')->nullable()->constrained('event_registrations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('overall_rating')->nullable(); // 1-5
            $table->integer('content_rating')->nullable();
            $table->integer('speaker_rating')->nullable();
            $table->integer('venue_rating')->nullable();
            $table->integer('organization_rating')->nullable();
            $table->integer('nps_score')->nullable(); // 0-10
            $table->text('positive_feedback')->nullable();
            $table->text('improvement_suggestions')->nullable();
            $table->text('additional_comments')->nullable();
            $table->json('custom_answers')->nullable();
            $table->boolean('would_recommend')->nullable();
            $table->boolean('would_attend_again')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();

            $table->index(['event_id', 'overall_rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_feedback');
    }
};
