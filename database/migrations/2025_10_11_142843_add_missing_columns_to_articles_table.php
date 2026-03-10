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
        Schema::table('articles', function (Blueprint $table): void {
            // Add status column (can be: draft, published, pending, scheduled)
            $table->string('status')->default('draft')->after('content');

            // Add excerpt column
            $table->text('excerpt')->nullable()->after('content');

            // Add views counter
            $table->unsignedBigInteger('views')->default(0)->after('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropColumn(['status', 'excerpt', 'views']);
        });
    }
};
