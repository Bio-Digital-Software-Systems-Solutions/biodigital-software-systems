<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_step_instances', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workflow_instance_id')->constrained()->onDelete('cascade');
            $table->foreignId('workflow_step_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, active, completed, skipped, failed, cancelled, waiting
            $table->json('input_data')->nullable(); // Data passed to this step
            $table->json('output_data')->nullable(); // Data produced by this step
            $table->json('context')->nullable(); // Step-specific context
            $table->integer('attempt_count')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->foreignId('escalated_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['workflow_instance_id', 'status']);
            $table->index(['workflow_step_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('status');
            $table->index('due_at');
        });

        // Add foreign key constraint for parent_step_instance_id in workflow_instances
        Schema::table('workflow_instances', function (Blueprint $table): void {
            $table->foreign('parent_step_instance_id')
                ->references('id')
                ->on('workflow_step_instances')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_instances', function (Blueprint $table): void {
            $table->dropForeign(['parent_step_instance_id']);
        });

        Schema::dropIfExists('workflow_step_instances');
    }
};
