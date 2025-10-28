<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Add polymorphic columns
            $table->nullableMorphs('taskable');

            // Make program_id nullable and keep it for backward compatibility
            $table->unsignedBigInteger('program_id')->nullable()->change();

            // Add project_id column for migration purposes
            if (!Schema::hasColumn('tasks', 'project_id')) {
                $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            }
        });

        // Migrate existing data: set taskable_type and taskable_id based on program_id
        DB::statement("
            UPDATE tasks
            SET taskable_type = 'App\\\\Models\\\\Program',
                taskable_id = program_id
            WHERE program_id IS NOT NULL
        ");

        // Migrate existing data: set taskable_type and taskable_id based on project_id
        DB::statement("
            UPDATE tasks
            SET taskable_type = 'App\\\\Models\\\\Project',
                taskable_id = project_id
            WHERE project_id IS NOT NULL AND taskable_id IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropMorphs('taskable');

            if (Schema::hasColumn('tasks', 'project_id')) {
                $table->dropForeign(['project_id']);
                $table->dropColumn('project_id');
            }
        });
    }
};
