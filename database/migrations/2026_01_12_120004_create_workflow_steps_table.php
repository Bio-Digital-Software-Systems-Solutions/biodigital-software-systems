<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workflow_id')->constrained('department_workflows')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // start, end, approval, condition, action, wait, notification, form, subprocess, parallel_split, parallel_join
            $table->json('config')->nullable(); // Step-specific configuration

            // Position in visual editor
            $table->decimal('position_x', 10, 2)->default(0);
            $table->decimal('position_y', 10, 2)->default(0);

            // Approval settings (when type = approval)
            $table->string('approval_type')->nullable(); // any, all, majority, sequential
            $table->json('approvers')->nullable(); // User IDs, role IDs, or expressions
            $table->integer('min_approvals')->nullable();

            // Timeout settings
            $table->integer('timeout_minutes')->nullable();
            $table->string('timeout_action')->nullable(); // escalate, skip, fail, auto_approve, auto_reject, notify, reassign
            $table->json('timeout_config')->nullable();

            // Reminder settings
            $table->integer('reminder_interval_minutes')->nullable();
            $table->integer('max_reminders')->nullable();

            // Form reference (when type = form)
            $table->foreignId('form_id')->nullable()->constrained('department_forms')->onDelete('set null');

            // Subprocess reference (when type = subprocess)
            $table->foreignId('subprocess_workflow_id')->nullable()->constrained('department_workflows')->onDelete('set null');

            // Action settings (when type = action)
            $table->string('action_type')->nullable();
            $table->json('action_config')->nullable();

            // Notification settings (when type = notification)
            $table->json('notification_config')->nullable();

            // Wait settings (when type = wait)
            $table->integer('wait_minutes')->nullable();
            $table->string('wait_until_event')->nullable();

            $table->integer('order')->default(0);
            $table->boolean('is_optional')->default(false);
            $table->timestamps();

            $table->index(['workflow_id', 'type']);
            $table->index(['workflow_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
