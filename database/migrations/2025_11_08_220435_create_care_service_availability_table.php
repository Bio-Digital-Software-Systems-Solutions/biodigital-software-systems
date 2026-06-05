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
        Schema::create('care_service_availability', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pastor_id')->constrained('users')->onDelete('cascade');

            // Support for recurring availability patterns
            $table->enum('type', ['weekly', 'specific_date'])->default('weekly');

            // For weekly patterns (0=Sunday, 1=Monday, ... 6=Saturday)
            $table->tinyInteger('day_of_week')->nullable();

            // For specific dates
            $table->date('specific_date')->nullable();

            // Time slots
            $table->time('start_time');
            $table->time('end_time');

            // Duration of each appointment slot in minutes
            $table->integer('slot_duration')->default(60);

            // Whether this availability is active
            $table->boolean('is_active')->default(true);

            // Additional notes
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['pastor_id', 'type', 'is_active']);
            $table->index(['day_of_week', 'is_active']);
            $table->index(['specific_date', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_service_availability');
    }
};
