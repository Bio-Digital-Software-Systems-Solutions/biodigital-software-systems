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
        Schema::create('stocks', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->text('description')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('minimum_quantity')->default(0);
            $table->decimal('unit_price', 8, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->string('supplier_contact')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
