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
        Schema::create('integration_suggestions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('visitor_visit_id')->constrained('visitor_visits')->cascadeOnDelete();
            $table->foreignId('suggested_to')->constrained('users')->cascadeOnDelete();
            $table->decimal('score_at_suggestion', 5, 2);
            $table->string('status')->default('pending');
            $table->dateTime('responded_at')->nullable();
            $table->text('response_notes')->nullable();
            $table->timestamps();

            $table->index(['suggested_to', 'status']);
            $table->index('visitor_visit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_suggestions');
    }
};
