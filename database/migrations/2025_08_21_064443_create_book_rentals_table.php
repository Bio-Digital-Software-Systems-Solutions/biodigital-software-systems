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
        Schema::create('book_rentals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('library_id')->constrained()->onDelete('cascade');
            $table->date('rental_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->decimal('rental_fee', 8, 2)->default(0);
            $table->decimal('late_fee', 8, 2)->default(0);
            $table->enum('status', ['active', 'returned', 'overdue'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_rentals');
    }
};
