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
        Schema::table('groups', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Generate UUIDs for existing records
        \App\Models\Group::whereNull('uuid')->each(function ($group): void {
            $group->uuid = (string) Str::uuid();
            $group->save();
        });

        // Make uuid unique and not nullable
        Schema::table('groups', function (Blueprint $table): void {
            $table->uuid('uuid')->unique()->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });
    }
};
