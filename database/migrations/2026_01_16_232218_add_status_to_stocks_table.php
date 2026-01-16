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
        Schema::table('stocks', function (Blueprint $table) {
            $table->string('status')->default('active')->after('is_active');
        });

        // Migrate existing is_active values to status
        \DB::table('stocks')->where('is_active', false)->update(['status' => 'inactive']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
