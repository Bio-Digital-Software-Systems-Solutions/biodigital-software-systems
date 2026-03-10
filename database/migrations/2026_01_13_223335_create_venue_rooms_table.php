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
        Schema::create('venue_rooms', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('venue_id')->constrained('event_venues')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('capacity')->nullable();
            $table->string('floor')->nullable();
            $table->string('room_number')->nullable();
            $table->json('equipment')->nullable();
            $table->json('layout_options')->nullable();
            $table->boolean('is_available')->default(true);
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_rooms');
    }
};
