<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('planning');
            $table->string('priority')->default('medium');
            $table->string('color', 7)->default('#3b82f6');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->foreignId('project_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_template')->default(false);
            $table->json('settings')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('project_manager_id');
            $table->index('reviewer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
