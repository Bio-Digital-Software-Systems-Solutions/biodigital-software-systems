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
        // Add missing columns to tasks table to match project_tasks
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'key')) {
                $table->string('key')->nullable()->unique()->after('uuid');
            }
            if (!Schema::hasColumn('tasks', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->constrained('tasks')->nullOnDelete()->after('taskable_id');
            }
            if (!Schema::hasColumn('tasks', 'reporter_id')) {
                $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete()->after('assigned_to');
            }
            if (!Schema::hasColumn('tasks', 'type')) {
                $table->string('type')->default('task')->after('priority');
            }
            if (!Schema::hasColumn('tasks', 'story_points')) {
                $table->integer('story_points')->nullable()->after('estimated_hours');
            }
            if (!Schema::hasColumn('tasks', 'sprint_id')) {
                $table->foreignId('sprint_id')->nullable()->constrained('sprints')->nullOnDelete()->after('project_id');
            }
            if (!Schema::hasColumn('tasks', 'epic_id')) {
                $table->foreignId('epic_id')->nullable()->constrained('tasks')->nullOnDelete()->after('sprint_id');
            }
            if (!Schema::hasColumn('tasks', 'labels')) {
                $table->json('labels')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('tasks', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('labels');
            }
            if (!Schema::hasColumn('tasks', 'position')) {
                $table->integer('position')->default(0)->after('custom_fields');
            }
        });

        // Migrate data from project_tasks to tasks one by one to generate UUIDs
        // Only run if project_tasks table exists
        if (Schema::hasTable('project_tasks')) {
            $projectTasks = DB::table('project_tasks')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('tasks')
                        ->whereColumn('tasks.key', 'project_tasks.key');
                })
                ->get();
        } else {
            $projectTasks = collect([]);
        }

        foreach ($projectTasks as $pt) {
            $statusId = DB::table('statuses')
                ->whereRaw('LOWER(name) = ?', [strtolower($pt->status)])
                ->value('id');

            DB::table('tasks')->insert([
                'uuid' => $pt->uuid, // Preserve original UUID
                'title' => $pt->title,
                'key' => $pt->key,
                'description' => $pt->description,
                'taskable_type' => 'App\\Models\\Project',
                'taskable_id' => $pt->project_id,
                'project_id' => $pt->project_id,
                'parent_id' => $pt->parent_id,
                'assigned_to' => $pt->assignee_id,
                'reporter_id' => $pt->reporter_id,
                'status_id' => $statusId,
                'priority' => $pt->priority,
                'type' => $pt->type,
                'estimated_hours' => $pt->estimated_hours,
                'story_points' => $pt->story_points,
                'due_date' => $pt->due_date,
                'sprint_id' => $pt->sprint_id,
                'epic_id' => $pt->epic_id,
                'labels' => $pt->labels,
                'custom_fields' => $pt->custom_fields,
                'position' => $pt->position,
                'created_at' => $pt->created_at,
                'updated_at' => $pt->updated_at,
                'deleted_at' => $pt->deleted_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove migrated project_tasks data
        DB::statement("
            DELETE FROM tasks
            WHERE taskable_type = 'App\\\\Models\\\\Project'
            AND `key` IN (SELECT `key` FROM project_tasks)
        ");

        // Remove added columns
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'position')) {
                $table->dropColumn('position');
            }
            if (Schema::hasColumn('tasks', 'custom_fields')) {
                $table->dropColumn('custom_fields');
            }
            if (Schema::hasColumn('tasks', 'labels')) {
                $table->dropColumn('labels');
            }
            if (Schema::hasColumn('tasks', 'epic_id')) {
                $table->dropForeign(['epic_id']);
                $table->dropColumn('epic_id');
            }
            if (Schema::hasColumn('tasks', 'sprint_id')) {
                $table->dropForeign(['sprint_id']);
                $table->dropColumn('sprint_id');
            }
            if (Schema::hasColumn('tasks', 'story_points')) {
                $table->dropColumn('story_points');
            }
            if (Schema::hasColumn('tasks', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('tasks', 'reporter_id')) {
                $table->dropForeign(['reporter_id']);
                $table->dropColumn('reporter_id');
            }
            if (Schema::hasColumn('tasks', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
            if (Schema::hasColumn('tasks', 'key')) {
                $table->dropColumn('key');
            }
        });
    }
};
