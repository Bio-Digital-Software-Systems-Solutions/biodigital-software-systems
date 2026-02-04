<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pastoral_cares', function (Blueprint $table) {
            // Add polymorphic columns for assigned_agent
            $table->nullableMorphs('assigned_agent');
        });

        // Migrate existing pastor_id data to assigned_agent columns
        // All existing appointments with pastor_id will have assigned_agent_type = 'App\Models\User'
        DB::statement("
            UPDATE pastoral_cares
            SET assigned_agent_id = pastor_id,
                assigned_agent_type = 'App\\\\Models\\\\User'
            WHERE pastor_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pastoral_cares', function (Blueprint $table) {
            $table->dropMorphs('assigned_agent');
        });
    }
};
