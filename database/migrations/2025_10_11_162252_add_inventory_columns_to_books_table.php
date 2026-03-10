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
        Schema::table('books', function (Blueprint $table): void {
            // Add inventory tracking columns
            $table->unsignedInteger('total_copies')->default(0)->after('stock_quantity');
            $table->unsignedInteger('available_copies')->default(0)->after('total_copies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table): void {
            $table->dropColumn(['total_copies', 'available_copies']);
        });
    }
};
