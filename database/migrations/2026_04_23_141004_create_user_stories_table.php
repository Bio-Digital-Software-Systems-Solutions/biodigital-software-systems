<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_stories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('epic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sprint_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reporter_id')->constrained('users');
            $table->string('title');
            $table->string('as_a');
            $table->text('i_want');
            $table->text('so_that');
            $table->unsignedSmallInteger('story_points')->nullable();
            $table->unsignedTinyInteger('priority')->default(3);
            $table->string('status', 32)->default('backlog');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['epic_id', 'status']);
            $table->index(['sprint_id', 'status']);
            $table->index('assignee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stories');
    }
};
