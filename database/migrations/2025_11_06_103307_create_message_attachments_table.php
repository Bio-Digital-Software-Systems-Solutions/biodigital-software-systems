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
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            $table->string('filename'); // Original filename
            $table->string('stored_filename'); // Filename on disk
            $table->string('file_path'); // Path to the file
            $table->string('mime_type'); // MIME type of the file
            $table->bigInteger('file_size'); // File size in bytes
            $table->string('file_type')->nullable(); // image, document, etc.
            $table->timestamps();

            $table->index(['message_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
