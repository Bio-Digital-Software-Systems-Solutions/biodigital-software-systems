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
        // Only attempt to drop if the table exists
        if (!Schema::hasTable('project_tasks')) {
            return;
        }

        // Get database driver
        $driver = DB::getDriverName();

        // Only handle foreign keys for MySQL
        if ($driver === 'mysql') {
            // Get database name
            $database = DB::getDatabaseName();

            // Get all foreign keys that reference project_tasks
            $foreignKeys = DB::select("
                SELECT TABLE_NAME, CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE REFERENCED_TABLE_NAME = 'project_tasks'
                AND TABLE_SCHEMA = ?
            ", [$database]);

            // Drop each foreign key constraint
            foreach ($foreignKeys as $fk) {
                try {
                    DB::statement("ALTER TABLE `{$fk->TABLE_NAME}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                } catch (\Exception) {
                    // Constraint might already be dropped, continue
                }
            }
        }

        // Now we can drop the project_tasks table
        Schema::dropIfExists('project_tasks');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot recreate the table as data has been migrated to tasks table
        // See migration: 2025_10_28_183222_migrate_project_tasks_to_tasks_table
    }
};
