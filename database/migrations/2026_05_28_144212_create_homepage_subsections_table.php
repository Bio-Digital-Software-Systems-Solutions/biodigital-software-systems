<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_subsections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('homepage_section_id')
                ->constrained('homepage_sections')
                ->cascadeOnDelete();
            $table->enum('block_type', ['heading', 'paragraph', 'image', 'button', 'card']);
            $table->json('content');
            $table->json('design_settings')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['homepage_section_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_subsections');
    }
};
