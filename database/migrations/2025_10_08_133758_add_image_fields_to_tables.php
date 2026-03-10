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
        // Add cover_image to books table
        Schema::table('books', function (Blueprint $table): void {
            $table->string('cover_image')->nullable()->after('description');
        });

        // Add image to tasks table
        Schema::table('tasks', function (Blueprint $table): void {
            $table->string('image')->nullable()->after('description');
        });

        // Add image to projects table
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('image')->nullable()->after('description');
        });

        // Add image to departments table
        Schema::table('departments', function (Blueprint $table): void {
            $table->string('image')->nullable()->after('description');
        });

        // Add image to groups table
        Schema::table('groups', function (Blueprint $table): void {
            $table->string('image')->nullable()->after('description');
        });

        // Add image to stocks table
        Schema::table('stocks', function (Blueprint $table): void {
            $table->string('image')->nullable()->after('description');
        });

        // Add image to libraries table
        Schema::table('libraries', function (Blueprint $table): void {
            $table->string('image')->nullable()->after('description');
        });

        // Add avatar to events table
        Schema::table('events', function (Blueprint $table): void {
            $table->string('avatar')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table): void {
            $table->dropColumn('cover_image');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('image');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('image');
        });

        Schema::table('departments', function (Blueprint $table): void {
            $table->dropColumn('image');
        });

        Schema::table('groups', function (Blueprint $table): void {
            $table->dropColumn('image');
        });

        Schema::table('stocks', function (Blueprint $table): void {
            $table->dropColumn('image');
        });

        Schema::table('libraries', function (Blueprint $table): void {
            $table->dropColumn('image');
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn('avatar');
        });
    }
};
