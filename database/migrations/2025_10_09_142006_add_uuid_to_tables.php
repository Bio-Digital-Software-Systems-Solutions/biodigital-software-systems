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
        // List of tables to add UUID to
        $tables = [
            'users',
            'events',
            'trainings',
            'articles',
            'books',
            'projects',
            'chat_rooms',
            'chat_messages',
            'contacts',
            'book_rentals',
        ];

        foreach ($tables as $tableName) {
            // Add uuid column
            Schema::table($tableName, function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });

            // Generate UUIDs for existing records
            DB::table($tableName)->whereNull('uuid')->cursor()->each(function ($record) use ($tableName) {
                DB::table($tableName)
                    ->where('id', $record->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });

            // Make uuid unique and non-nullable
            Schema::table($tableName, function (Blueprint $table) {
                $table->uuid('uuid')->unique()->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'users',
            'events',
            'trainings',
            'articles',
            'books',
            'projects',
            'chat_rooms',
            'chat_messages',
            'contacts',
            'book_rentals',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('uuid');
            });
        }
    }
};
