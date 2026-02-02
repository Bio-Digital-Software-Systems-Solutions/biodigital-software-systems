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
        Schema::create('pastoral_care_pastoral_care_theme', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pastoral_care_id')->constrained()->onDelete('cascade');
            $table->foreignId('pastoral_care_theme_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['pastoral_care_id', 'pastoral_care_theme_id'], 'pc_pct_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pastoral_care_pastoral_care_theme');
    }
};
