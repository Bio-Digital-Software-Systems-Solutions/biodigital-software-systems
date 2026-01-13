<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_needs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category'); // equipment, software, furniture, supplies, services, training, recruitment, other
            $table->string('priority')->default('medium'); // critical, high, medium, low
            $table->string('status')->default('draft'); // draft, submitted, under_review, approved, rejected, ordered, in_progress, delivered, completed, cancelled
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->decimal('approved_budget', 15, 2)->nullable();
            $table->decimal('actual_cost', 15, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->integer('quantity')->default(1);
            $table->string('unit')->nullable(); // pieces, hours, months, etc.
            $table->text('justification')->nullable();
            $table->json('specifications')->nullable(); // Technical specifications
            $table->json('vendor_info')->nullable(); // Preferred vendor details
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('workflow_instance_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('form_submission_id')->nullable()->constrained('department_form_submissions')->onDelete('set null');
            $table->date('needed_by')->nullable();
            $table->date('expected_delivery')->nullable();
            $table->date('actual_delivery')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'status']);
            $table->index(['requester_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['category', 'status']);
            $table->index(['priority', 'status']);
            $table->index('status');
            $table->index('needed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_needs');
    }
};
