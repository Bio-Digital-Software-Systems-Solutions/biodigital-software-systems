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
        Schema::create('books', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->string('isbn')->unique()->nullable();
            $table->text('description')->nullable();
            $table->decimal('rental_price', 8, 2)->nullable();
            $table->integer('max_rental_days')->default(30);
            $table->integer('stock_quantity')->default(0);
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
