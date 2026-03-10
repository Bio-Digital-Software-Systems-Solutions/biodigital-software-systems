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
        Schema::create('training_class_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_class_id')->constrained()->onDelete('cascade');
            $table->enum('day_of_week', ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche']);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure unique schedule per class per day
            $table->unique(['training_class_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_class_schedules');
    }
};
