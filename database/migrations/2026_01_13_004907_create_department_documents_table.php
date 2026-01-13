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
        Schema::create('department_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();

            // File information
            $table->string('original_name');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('extension', 20);

            // Organization (auto-calculated from upload date)
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            // Metadata
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for efficient tree queries
            $table->index(['department_id', 'year', 'month']);
            $table->index(['department_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_documents');
    }
};
