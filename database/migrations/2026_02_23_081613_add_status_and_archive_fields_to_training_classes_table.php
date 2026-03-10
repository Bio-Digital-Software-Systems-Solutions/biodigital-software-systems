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
        Schema::table('training_classes', function (Blueprint $table): void {
            $table->string('status')->default('active')->after('notes');
            $table->timestamp('archived_at')->nullable()->after('status');
            $table->timestamp('archive_access_until')->nullable()->after('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_classes', function (Blueprint $table): void {
            $table->dropColumn(['status', 'archived_at', 'archive_access_until']);
        });
    }
};
