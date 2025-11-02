<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrate existing training_materials to training_class_materials.
     * For each training material, create a copy for each class of that training.
     */
    public function up(): void
    {
        // Get all training materials
        $trainingMaterials = DB::table('training_materials')->get();

        foreach ($trainingMaterials as $material) {
            // Get all classes for this training
            $classes = DB::table('training_classes')
                ->where('training_id', $material->training_id)
                ->get();

            // Create a material entry for each class
            foreach ($classes as $class) {
                DB::table('training_class_materials')->insert([
                    'uuid' => (string) Str::uuid(),
                    'training_class_id' => $class->id,
                    'teacher_id' => $class->teacher_id,
                    'title' => $material->title,
                    'type' => $material->type,
                    'file_path' => null,
                    'url' => $material->url,
                    'duration' => $material->duration,
                    'description' => null,
                    'order' => $material->order,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Log migration statistics
        $totalOldMaterials = DB::table('training_materials')->count();
        $totalNewMaterials = DB::table('training_class_materials')->count();

        \Log::info("Migrated $totalOldMaterials training_materials to $totalNewMaterials training_class_materials");
    }

    /**
     * Reverse the migrations.
     *
     * This will delete all migrated data from training_class_materials.
     * Use with caution!
     */
    public function down(): void
    {
        // Delete all training_class_materials (they will be recreated from training_materials if needed)
        DB::table('training_class_materials')->truncate();

        \Log::info("Rolled back training_class_materials migration");
    }
};
