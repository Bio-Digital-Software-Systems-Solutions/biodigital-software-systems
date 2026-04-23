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
        Schema::create('visitor_attendances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('visitor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visitor_visit_id')->constrained('visitor_visits')->cascadeOnDelete();
            $table->morphs('attendable');
            $table->dateTime('attended_at');
            $table->string('status')->default('present');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['visitor_id', 'attended_at']);
            $table->index('visitor_visit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_attendances');
    }
};
