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
        Schema::create('event_badges', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('registration_id')->constrained('event_registrations')->cascadeOnDelete();
            $table->string('badge_number')->unique();
            $table->string('status')->default('pending'); // pending, generated, printed, collected, lost, replaced
            $table->string('badge_type')->default('standard'); // standard, speaker, vip, staff, sponsor
            $table->string('file_path')->nullable();
            $table->json('badge_data')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('collected_at')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('badge_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_badges');
    }
};
