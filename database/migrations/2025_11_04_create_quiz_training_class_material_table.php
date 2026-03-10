<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create a pivot table to associate quizzes with specific training class materials.
     * This allows quizzes to be linked to specific course materials for targeted assessment.
     */
    public function up(): void
    {
        Schema::create('quiz_training_class_material', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->foreignId('training_class_material_id')->constrained()->onDelete('cascade');
            $table->timestamp('assigned_at')->useCurrent();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0); // Order within the material
            $table->timestamps();

            // Ensure unique combination
            $table->unique(['quiz_id', 'training_class_material_id'], 'quiz_material_unique');

            // Indexes for performance
            $table->index('quiz_id');
            $table->index('training_class_material_id');
            $table->index('is_active');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_training_class_material');
    }
};