<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acceptance_criteria', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_story_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('title');
            $table->text('description');
            $table->string('status', 16)->default('pending');
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->text('validation_notes')->nullable();
            $table->timestamps();

            $table->index(['user_story_id', 'position']);
            $table->index(['user_story_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acceptance_criteria');
    }
};
