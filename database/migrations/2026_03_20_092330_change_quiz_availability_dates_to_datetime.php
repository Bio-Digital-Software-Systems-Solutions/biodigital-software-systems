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
        Schema::table('quizzes', function (Blueprint $table): void {
            $table->dateTime('available_from')->nullable()->change();
            $table->dateTime('available_until')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table): void {
            $table->date('available_from')->nullable()->change();
            $table->date('available_until')->nullable()->change();
        });
    }
};
