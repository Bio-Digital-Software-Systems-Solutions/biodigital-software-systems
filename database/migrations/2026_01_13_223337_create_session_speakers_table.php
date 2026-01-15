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
        Schema::create('session_speakers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('session_id')->constrained('event_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('title')->nullable(); // Job title
            $table->string('company')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo')->nullable();
            $table->string('role')->default('speaker'); // speaker, moderator, panelist
            $table->json('social_links')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();

            $table->index(['session_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_speakers');
    }
};
