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
        Schema::table('attendances', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['training_class_id']);
            $table->dropForeign(['student_id']);

            // Drop old unique constraint
            $table->dropUnique(['training_class_id', 'student_id']);

            // Add new unique constraint on training_class_schedule_id and student_id
            $table->unique(['training_class_schedule_id', 'student_id']);

            // Re-add foreign keys
            $table->foreign('training_class_id')->references('id')->on('training_classes')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop new unique constraint
            $table->dropUnique(['training_class_schedule_id', 'student_id']);

            // Restore old unique constraint
            $table->unique(['training_class_id', 'student_id']);
        });
    }
};
