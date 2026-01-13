<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('need_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('need_id')->constrained('department_needs')->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size'); // bytes
            $table->string('path');
            $table->string('disk')->default('public');
            $table->string('type')->default('document'); // document, quote, invoice, receipt, image, other
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['need_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('need_attachments');
    }
};
