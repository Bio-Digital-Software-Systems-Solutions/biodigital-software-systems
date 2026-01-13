<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('need_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('need_id')->constrained('department_needs')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->boolean('is_internal')->default(false); // Internal notes vs public comments
            $table->foreignId('parent_id')->nullable()->constrained('need_comments')->onDelete('cascade');
            $table->json('mentions')->nullable(); // Array of mentioned user IDs
            $table->timestamps();
            $table->softDeletes();

            $table->index(['need_id', 'is_internal']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('need_comments');
    }
};
