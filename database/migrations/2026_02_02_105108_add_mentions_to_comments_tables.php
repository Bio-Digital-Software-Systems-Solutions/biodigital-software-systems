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
        Schema::table('task_comments', function (Blueprint $table): void {
            $table->json('mentions')->nullable()->after('content');
        });

        Schema::table('project_comments', function (Blueprint $table): void {
            $table->json('mentions')->nullable()->after('content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_comments', function (Blueprint $table): void {
            $table->dropColumn('mentions');
        });

        Schema::table('project_comments', function (Blueprint $table): void {
            $table->dropColumn('mentions');
        });
    }
};
