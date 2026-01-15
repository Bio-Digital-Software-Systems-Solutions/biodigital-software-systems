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
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('year');
            $table->string('leave_type');
            $table->decimal('entitled_days', 5, 2)->default(0);
            $table->decimal('taken_days', 5, 2)->default(0);
            $table->decimal('pending_days', 5, 2)->default(0);
            $table->decimal('carried_over', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'department_id', 'year', 'leave_type']);
            $table->index(['user_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
