<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('routine_step_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('assignee');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['routine_id', 'routine_step_id', 'user_id', 'role'], 'routine_assignees_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_assignees');
    }
};
