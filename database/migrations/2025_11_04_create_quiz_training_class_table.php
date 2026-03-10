<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create a pivot table to associate quizzes with specific training classes.
     * This allows for more granular control over which classes have access to which quizzes.
     */
    public function up(): void
    {
        Schema::create('quiz_training_class', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->foreignId('training_class_id')->constrained()->onDelete('cascade');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure unique combination
            $table->unique(['quiz_id', 'training_class_id'], 'quiz_class_unique');

            // Indexes for performance
            $table->index('quiz_id');
            $table->index('training_class_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_training_class');
    }
};