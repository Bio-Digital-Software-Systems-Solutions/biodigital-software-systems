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
        Schema::create('profile_skills', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->enum('category', ['soft', 'hard', 'technical'])->default('technical');
            $table->timestamps();

            $table->unique(['name', 'category']);
        });

        Schema::create('profile_skill_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profile_skill_id')->constrained()->cascadeOnDelete();
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'expert'])->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'profile_skill_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_skill_user');
        Schema::dropIfExists('profile_skills');
    }
};
