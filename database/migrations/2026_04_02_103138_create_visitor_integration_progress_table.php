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
        Schema::create('visitor_integration_progress', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('visitor_visit_id')->constrained('visitor_visits')->cascadeOnDelete();
            $table->foreignId('step_id')->constrained('integration_pathway_steps')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->decimal('progress_value', 5, 2)->default(0);
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['visitor_visit_id', 'step_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_integration_progress');
    }
};
