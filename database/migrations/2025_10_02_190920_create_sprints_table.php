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
        Schema::create('sprints', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('goal')->nullable();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('planned'); // planned, active, completed, cancelled
            $table->integer('capacity')->nullable(); // story points capacity
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sprints');
    }
};
