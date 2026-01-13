<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            // Add timeout_hours column if not exists
            if (!Schema::hasColumn('workflow_steps', 'timeout_hours')) {
                $table->integer('timeout_hours')->nullable()->after('timeout_minutes');
            }
            // Add escalation_user_id column if not exists
            if (!Schema::hasColumn('workflow_steps', 'escalation_user_id')) {
                $table->foreignId('escalation_user_id')->nullable()->after('timeout_action')->constrained('users')->onDelete('set null');
            }
            // Add retry_count column if not exists
            if (!Schema::hasColumn('workflow_steps', 'retry_count')) {
                $table->integer('retry_count')->default(0)->after('escalation_user_id');
            }
            // Add retry_delay_minutes column if not exists
            if (!Schema::hasColumn('workflow_steps', 'retry_delay_minutes')) {
                $table->integer('retry_delay_minutes')->nullable()->after('retry_count');
            }
            // Add conditions column if not exists
            if (!Schema::hasColumn('workflow_steps', 'conditions')) {
                $table->json('conditions')->nullable()->after('retry_delay_minutes');
            }
            // Add metadata column if not exists
            if (!Schema::hasColumn('workflow_steps', 'metadata')) {
                $table->json('metadata')->nullable()->after('conditions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $columns = ['timeout_hours', 'escalation_user_id', 'retry_count', 'retry_delay_minutes', 'conditions', 'metadata'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('workflow_steps', $column)) {
                    if ($column === 'escalation_user_id') {
                        $table->dropForeign(['escalation_user_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
