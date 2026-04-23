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
        Schema::create('group_activities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('activity_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('status')->default('planned');
            $table->string('type')->default('task');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'activity_date']);
            $table->index(['group_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_activities');
    }
};
