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
        Schema::create('event_tickets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('paid'); // free, paid, donation, early_bird, vip, group, student, member
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('original_price', 10, 2)->nullable(); // For showing discounts
            $table->string('currency', 3)->default('EUR');
            $table->integer('quantity_total')->nullable();
            $table->integer('quantity_sold')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('min_per_order')->default(1);
            $table->integer('max_per_order')->nullable();
            $table->dateTime('sales_start')->nullable();
            $table->dateTime('sales_end')->nullable();
            $table->json('benefits')->nullable(); // What's included
            $table->json('restrictions')->nullable(); // Any restrictions
            $table->boolean('is_visible')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'type']);
            $table->index('is_visible');
            $table->index(['sales_start', 'sales_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_tickets');
    }
};
