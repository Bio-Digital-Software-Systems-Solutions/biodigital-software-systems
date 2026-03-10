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
        Schema::create('evaluations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('training_id')->constrained()->onDelete('cascade');
            $table->foreignId('training_topic_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['quiz', 'exam', 'assignment', 'project']);
            $table->decimal('grade', 5, 2);
            $table->decimal('max_grade', 5, 2)->default(100);
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
