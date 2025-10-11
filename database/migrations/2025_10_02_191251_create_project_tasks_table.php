<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('key')->unique(); // ex: PROJ-123
            $table->text('description')->nullable();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('project_tasks')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('todo');
            $table->string('priority')->default('medium');
            $table->string('type')->default('task');
            $table->integer('story_points')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->datetime('due_date')->nullable();
            $table->foreignId('sprint_id')->nullable()->constrained('sprints')->nullOnDelete();
            $table->foreignId('epic_id')->nullable()->constrained('project_tasks')->nullOnDelete();
            $table->json('labels')->nullable();
            $table->json('custom_fields')->nullable();
            $table->integer('position')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['assignee_id', 'status']);
            $table->index(['sprint_id', 'status']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
