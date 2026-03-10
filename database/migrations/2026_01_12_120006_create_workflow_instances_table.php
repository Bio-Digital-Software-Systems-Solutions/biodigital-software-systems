<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_instances', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workflow_id')->constrained('department_workflows')->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('started_by')->constrained('users')->onDelete('cascade');
            $table->string('name')->nullable(); // Instance name (can be auto-generated)
            $table->string('status')->default('pending'); // pending, active, paused, completed, cancelled, failed
            $table->json('context')->nullable(); // Runtime context/variables
            $table->json('input_data')->nullable(); // Initial data passed when starting
            $table->json('output_data')->nullable(); // Final output data
            $table->text('cancellation_reason')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('parent_instance_id')->nullable()->constrained('workflow_instances')->onDelete('set null');
            $table->foreignId('parent_step_instance_id')->nullable(); // Will be set after step_instances table is created
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'status']);
            $table->index(['department_id', 'status']);
            $table->index(['started_by', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instances');
    }
};
