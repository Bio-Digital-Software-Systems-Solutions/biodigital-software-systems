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
        Schema::create('event_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('venue_rooms')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('format')->default('in_person'); // in_person, virtual, hybrid
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('capacity')->nullable();
            $table->string('streaming_url')->nullable();
            $table->string('recording_url')->nullable();
            $table->json('resources')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('requires_registration')->default(false);
            $table->string('status')->default('scheduled');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'start_time']);
            $table->index('format');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_sessions');
    }
};
