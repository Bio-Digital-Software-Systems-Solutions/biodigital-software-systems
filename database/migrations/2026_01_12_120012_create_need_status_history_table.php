<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('need_status_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('need_id')->constrained('department_needs')->onDelete('cascade');
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamps();

            $table->index(['need_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('need_status_history');
    }
};
