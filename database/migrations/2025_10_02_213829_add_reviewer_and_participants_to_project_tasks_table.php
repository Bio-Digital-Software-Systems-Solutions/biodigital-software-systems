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
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->foreignId('reviewer_id')->nullable()->after('reporter_id')->constrained('users')->onDelete('set null');
            $table->boolean('reviewed')->default(false)->after('reviewer_id');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed');
            $table->timestamp('started_at')->nullable()->after('reviewed_at');
            $table->timestamp('paused_at')->nullable()->after('started_at');
            $table->timestamp('stopped_at')->nullable()->after('paused_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropForeign(['reviewer_id']);
            $table->dropColumn(['reviewer_id', 'reviewed', 'reviewed_at', 'started_at', 'paused_at', 'stopped_at']);
        });
    }
};
