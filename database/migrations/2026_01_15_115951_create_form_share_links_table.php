<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_share_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_id')->constrained('department_forms')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('token', 64)->unique(); // Secure random token
            $table->timestamp('expires_at');
            $table->integer('max_uses')->nullable(); // Optional max number of uses
            $table->integer('use_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['token', 'is_active']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_share_links');
    }
};
