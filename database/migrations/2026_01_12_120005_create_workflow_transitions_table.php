<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workflow_id')->constrained('department_workflows')->onDelete('cascade');
            $table->foreignId('from_step_id')->constrained('workflow_steps')->onDelete('cascade');
            $table->foreignId('to_step_id')->constrained('workflow_steps')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('condition_type')->default('always'); // always, expression, approval_result, form_field, variable
            $table->text('condition_expression')->nullable();
            $table->json('condition_config')->nullable();
            $table->integer('priority')->default(0); // For ordering multiple transitions from same step
            $table->string('label')->nullable(); // Label shown on the edge
            $table->timestamps();

            $table->index(['workflow_id', 'from_step_id']);
            $table->index(['from_step_id', 'to_step_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_transitions');
    }
};
