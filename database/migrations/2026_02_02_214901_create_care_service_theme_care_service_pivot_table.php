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
        Schema::create('care_service_care_service_theme', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('care_service_id')->constrained()->onDelete('cascade');
            $table->foreignId('care_service_theme_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['care_service_id', 'care_service_theme_id'], 'pc_pct_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_service_care_service_theme');
    }
};
