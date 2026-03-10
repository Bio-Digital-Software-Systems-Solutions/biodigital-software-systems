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
            $table->text('pastor_notes')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pastoral_cares', function (Blueprint $table): void {
            $table->dropColumn('pastor_notes');
        });
    }
};
