<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('form_id')->constrained('department_forms')->onDelete('cascade');
            $table->foreignId('parent_field_id')->nullable()->constrained('form_fields')->onDelete('cascade');
            $table->string('name'); // Field identifier (snake_case)
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('type'); // text, textarea, select, checkbox, etc.
            $table->string('placeholder')->nullable();
            $table->text('default_value')->nullable();
            $table->json('options')->nullable(); // For select, radio, checkbox
            $table->json('validation')->nullable(); // Validation rules
            $table->json('conditional_logic')->nullable(); // Show/hide conditions
            $table->json('config')->nullable(); // Type-specific config
            $table->integer('order')->default(0);
            $table->integer('step')->default(1); // For multi-step forms
            $table->integer('column_span')->default(12); // Grid column span (1-12)
            $table->boolean('is_required')->default(false);
            $table->boolean('is_readonly')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->string('help_text')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'order']);
            $table->index(['form_id', 'step']);
            $table->unique(['form_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
