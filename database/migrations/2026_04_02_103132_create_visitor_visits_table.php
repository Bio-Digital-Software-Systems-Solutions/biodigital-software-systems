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
        Schema::create('visitor_visits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('visitor_id')->constrained()->cascadeOnDelete();
            $table->morphs('visitable');
            $table->date('first_visited_at');
            $table->decimal('integration_score', 5, 2)->default(0);
            $table->string('integration_status')->default('visiting');
            $table->text('notes')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['visitor_id', 'visitable_type', 'visitable_id']);
            $table->index('integration_score');
            $table->index('integration_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_visits');
    }
};
