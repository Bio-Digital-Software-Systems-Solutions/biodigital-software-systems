<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_instance_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('step_instance_id')->nullable()->constrained('workflow_step_instances')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action'); // started, completed, failed, cancelled, approved, rejected, escalated, etc.
            $table->string('entity_type'); // workflow_instance, step_instance, approval, etc.
            $table->unsignedBigInteger('entity_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['workflow_instance_id', 'created_at']);
            $table->index(['step_instance_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_activity_logs');
    }
};
