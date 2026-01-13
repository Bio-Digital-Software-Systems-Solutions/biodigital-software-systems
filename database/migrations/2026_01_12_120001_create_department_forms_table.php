<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_forms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft, published, archived
            $table->string('scope')->default('department'); // department, enterprise
            $table->boolean('is_template')->default(false);
            $table->boolean('is_multi_step')->default(false);
            $table->json('settings')->nullable(); // Form settings (submit button text, etc.)
            $table->json('validation_rules')->nullable(); // Cross-field validation
            $table->json('conditional_logic')->nullable(); // Field visibility conditions
            $table->text('success_message')->nullable(); // Message shown after successful submission
            $table->string('redirect_url')->nullable(); // URL to redirect after submission
            $table->string('submit_action')->nullable(); // What happens on submit
            $table->json('submit_config')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'status']);
            $table->index('is_template');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_forms');
    }
};
