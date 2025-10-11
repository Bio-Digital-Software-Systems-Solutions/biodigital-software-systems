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
        // Add images/attachments to events
        Schema::table('events', function (Blueprint $table) {
            $table->json('images')->nullable()->after('status'); // Store multiple image paths as JSON
        });

        // Add images and documents to articles
        Schema::table('articles', function (Blueprint $table) {
            $table->string('featured_image')->nullable()->after('content');
            $table->json('images')->nullable()->after('featured_image'); // Additional images
            $table->json('documents')->nullable()->after('images'); // Attached documents
        });

        // Add images to books (already has cover_image, add gallery)
        Schema::table('books', function (Blueprint $table) {
            $table->json('images')->nullable()->after('cover_image'); // Additional images for book gallery
        });

        // Add attachments to messages
        Schema::table('messages', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('bcc_recipients'); // File attachments
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('images');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['featured_image', 'images', 'documents']);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('images');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });
    }
};
