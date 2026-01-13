<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('step_approvals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('step_instance_id')->constrained('workflow_step_instances')->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users')->onDelete('cascade');
            $table->string('decision')->nullable(); // approved, rejected, abstained, delegated, requested_changes
            $table->text('comments')->nullable();
            $table->json('requested_changes')->nullable(); // Details of requested changes
            $table->foreignId('delegated_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('delegation_reason')->nullable();
            $table->integer('order')->default(0); // For sequential approvals
            $table->boolean('is_required')->default(true);
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();

            $table->unique(['step_instance_id', 'approver_id']);
            $table->index(['approver_id', 'decision']);
            $table->index(['step_instance_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('step_approvals');
    }
};
