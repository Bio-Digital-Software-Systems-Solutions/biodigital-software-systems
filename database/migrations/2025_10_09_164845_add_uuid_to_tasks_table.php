<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Generate UUIDs for existing records
        \App\Models\Task::whereNull('uuid')->each(function ($task): void {
            $task->uuid = (string) Str::uuid();
            $task->save();
        });

        // Make uuid unique and not nullable
        Schema::table('tasks', function (Blueprint $table): void {
            $table->uuid('uuid')->unique()->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });
    }
};
