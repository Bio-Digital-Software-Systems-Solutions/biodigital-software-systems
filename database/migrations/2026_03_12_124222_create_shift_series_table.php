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
        Schema::create('shift_series', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('recurrence_type')->nullable(); // daily, weekly, monthly, null = specific dates
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_series');
    }
};
