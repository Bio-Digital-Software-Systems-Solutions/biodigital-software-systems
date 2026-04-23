<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_scenarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('acceptance_criterion_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('given')->nullable();
            $table->text('when')->nullable();
            $table->text('then')->nullable();
            $table->text('free_form')->nullable();
            $table->string('automated_test_ref')->nullable();
            $table->string('execution_status', 16)->default('not_run');
            $table->foreignId('last_executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_executed_at')->nullable();
            $table->text('failure_notes')->nullable();
            $table->timestamps();

            $table->index(['acceptance_criterion_id', 'execution_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_scenarios');
    }
};
