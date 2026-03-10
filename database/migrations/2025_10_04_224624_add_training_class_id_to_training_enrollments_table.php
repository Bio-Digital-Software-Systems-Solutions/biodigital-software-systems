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
        Schema::table('training_enrollments', function (Blueprint $table): void {
            $table->foreignId('training_class_id')->nullable()->after('training_id')->constrained('training_classes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_enrollments', function (Blueprint $table): void {
            $table->dropForeign(['training_class_id']);
            $table->dropColumn('training_class_id');
        });
    }
};
