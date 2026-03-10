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
            // Add library_id column (nullable because existing books might not have one)
            $table->foreignId('library_id')->nullable()->after('category_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table): void {
            $table->dropForeign(['library_id']);
            $table->dropColumn('library_id');
        });
    }
};
