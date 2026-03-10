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
        Schema::table('pastoral_cares', function (Blueprint $table): void {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('user_id')
                ->constrained('pastoral_cares')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pastoral_cares', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
