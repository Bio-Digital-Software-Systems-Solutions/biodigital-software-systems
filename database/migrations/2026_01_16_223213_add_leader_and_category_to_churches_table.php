<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->string('leader_name', 255)->nullable()->after('phone');
            $table->string('category', 50)->nullable()->default('eglise')->after('leader_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->dropColumn(['leader_name', 'category']);
        });
    }
};
