<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('duration');
            $table->enum('level', ['beginner', 'intermediate', 'advanced']);
            $table->decimal('price', 10, 2)->default(0);
            $table->string('image')->nullable();
            $table->string('category')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('students_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('training_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('training_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('training_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->decimal('progress', 5, 2)->default(0);
            $table->decimal('grade', 5, 2)->nullable();
            $table->decimal('attendance_rate', 5, 2)->default(0);
            $table->text('motivation')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'training_id']);
        });

        Schema::create('training_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->enum('type', ['pdf', 'powerpoint', 'video', 'audio']);
            $table->string('duration')->nullable();
            $table->string('url');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('training_material_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('training_material_id')->constrained()->onDelete('cascade');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'training_material_id'], 'user_material_unique');
        });

        Schema::create('training_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('max_score');
            $table->date('due_date');
            $table->timestamps();
        });

        Schema::create('training_evaluation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('training_evaluation_id')->constrained()->onDelete('cascade');
            $table->integer('score')->nullable();
            $table->boolean('passed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'training_evaluation_id'], 'user_evaluation_unique');
        });

        Schema::create('training_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_classes');
        Schema::dropIfExists('training_evaluation_results');
        Schema::dropIfExists('training_evaluations');
        Schema::dropIfExists('training_material_progress');
        Schema::dropIfExists('training_materials');
        Schema::dropIfExists('training_enrollments');
        Schema::dropIfExists('training_topics');
        Schema::dropIfExists('trainings');
    }
};
