<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Refactor training_class_materials so it becomes a pure pivot between
 * training_classes and training_materials. A TrainingMaterial holds the
 * content (file/url/title/type/duration/description); the pivot row
 * carries the per-class state (is_active, order, teacher_id).
 *
 * Existing rows are dropped (user-confirmed: no backfill).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop the Quiz <-> TrainingClassMaterial pivot first because it
        //    holds a FK to training_class_materials we are about to recreate.
        Schema::dropIfExists('quiz_training_class_material');

        // 2. Drop the old training_class_materials table (it carried content).
        Schema::dropIfExists('training_class_materials');

        // 3. Enrich training_materials with the content fields it now owns.
        Schema::table('training_materials', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
            $table->foreignId('teacher_id')->nullable()->after('training_id')
                ->constrained('users')->nullOnDelete();
            $table->string('file_path')->nullable()->after('url');
            $table->text('description')->nullable()->after('duration');
            $table->boolean('is_active')->default(true)->after('order');
            // url was NOT NULL on the original schema; it now coexists with
            // file_path so either one — but not both — is required.
            $table->string('url')->nullable()->change();
        });

        // Backfill UUIDs for any pre-existing rows, then enforce uniqueness.
        DB::table('training_materials')->whereNull('uuid')->orderBy('id')->each(function ($row): void {
            DB::table('training_materials')->where('id', $row->id)
                ->update(['uuid' => (string) Str::uuid()]);
        });
        Schema::table('training_materials', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });

        // 4. Recreate training_class_materials as a pure pivot.
        Schema::create('training_class_materials', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('training_class_id')->constrained('training_classes')->cascadeOnDelete();
            $table->foreignId('training_material_id')->constrained('training_materials')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['training_class_id', 'training_material_id'], 'tcm_class_material_unique');
            $table->index(['training_class_id', 'order']);
        });

        // 5. Recreate the Quiz <-> TrainingClassMaterial pivot (preserve the
        //    original schema exactly so existing app code keeps working).
        Schema::create('quiz_training_class_material', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_class_material_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['quiz_id', 'training_class_material_id'], 'quiz_material_unique');
            $table->index('quiz_id');
            $table->index('training_class_material_id');
            $table->index('is_active');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_training_class_material');
        Schema::dropIfExists('training_class_materials');

        Schema::table('training_materials', function (Blueprint $table): void {
            $table->dropForeign(['teacher_id']);
            $table->dropColumn(['uuid', 'file_path', 'description', 'teacher_id', 'is_active']);
        });

        Schema::create('training_class_materials', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('training_class_id')->constrained('training_classes')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('type');
            $table->string('file_path')->nullable();
            $table->string('url')->nullable();
            $table->string('duration')->nullable();
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('training_class_id');
            $table->index(['training_class_id', 'order']);
            $table->index('teacher_id');
        });

        Schema::create('quiz_training_class_material', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_class_material_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['quiz_id', 'training_class_material_id'], 'quiz_material_unique');
            $table->index('quiz_id');
            $table->index('training_class_material_id');
            $table->index('is_active');
            $table->index('order');
        });
    }
};
