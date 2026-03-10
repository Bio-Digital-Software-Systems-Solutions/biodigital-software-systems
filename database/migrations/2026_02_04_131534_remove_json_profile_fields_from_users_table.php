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
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['languages', 'hobbies', 'skills']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('languages')->nullable()->after('address');
            $table->json('hobbies')->nullable()->after('languages');
            $table->json('skills')->nullable()->after('hobbies');
        });
    }
};
