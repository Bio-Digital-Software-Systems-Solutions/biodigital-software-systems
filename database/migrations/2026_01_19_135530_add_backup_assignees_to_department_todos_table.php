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
        Schema::table('department_todos', function (Blueprint $table): void {
            $table->json('backup_assignees')->nullable()->after('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('department_todos', function (Blueprint $table): void {
            $table->dropColumn('backup_assignees');
        });
    }
};
