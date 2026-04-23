<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_item_links', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->string('link_type', 32);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['source_type', 'source_id'], 'wil_source_idx');
            $table->index(['target_type', 'target_id'], 'wil_target_idx');
            $table->unique(
                ['source_type', 'source_id', 'target_type', 'target_id', 'link_type'],
                'wil_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_item_links');
    }
};
