<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('integration_pathway_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('template_id')->constrained('integration_pathway_templates')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order_index');
            $table->string('type');
            $table->json('criteria')->nullable();
            $table->integer('weight')->default(1);
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->index(['template_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_pathway_steps');
    }
};
