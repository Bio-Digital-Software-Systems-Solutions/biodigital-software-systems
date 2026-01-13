<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_workflows', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft, active, deprecated
            $table->string('trigger_type')->default('manual'); // manual, event, scheduled, form_submission, webhook, api
            $table->string('scope')->default('department'); // department, enterprise
            $table->json('trigger_config')->nullable();
            $table->json('variables')->nullable(); // Workflow variables schema
            $table->json('settings')->nullable(); // General settings
            $table->integer('version')->default(1);
            $table->boolean('is_template')->default(false);
            $table->foreignId('parent_workflow_id')->nullable()->constrained('department_workflows')->onDelete('set null');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deprecated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'status']);
            $table->index(['status', 'trigger_type']);
            $table->index('is_template');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_workflows');
    }
};
