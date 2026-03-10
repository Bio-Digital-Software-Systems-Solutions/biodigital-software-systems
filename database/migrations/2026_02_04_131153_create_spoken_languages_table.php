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
        Schema::create('spoken_languages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->unique();
            $table->string('code', 5)->unique();
            $table->string('native_name')->nullable();
            $table->timestamps();
        });

        Schema::create('spoken_language_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spoken_language_id')->constrained()->cascadeOnDelete();
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'native'])->default('intermediate');
            $table->timestamps();

            $table->unique(['user_id', 'spoken_language_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spoken_language_user');
        Schema::dropIfExists('spoken_languages');
    }
};
