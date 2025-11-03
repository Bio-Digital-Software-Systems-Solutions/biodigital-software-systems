<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all enrollments without a training_class_id
        $enrollments = DB::table('training_enrollments')
            ->whereNull('training_class_id')
            ->get();

        $updated = 0;
        $skipped = 0;

        foreach ($enrollments as $enrollment) {
            // Find the first available class for this training
            $class = DB::table('training_classes')
                ->where('training_id', $enrollment->training_id)
                ->orderBy('date')
                ->first();

            if ($class) {
                // Assign this class to the enrollment
                DB::table('training_enrollments')
                    ->where('user_id', $enrollment->user_id)
                    ->where('training_id', $enrollment->training_id)
                    ->update([
                        'training_class_id' => $class->id,
                        'updated_at' => now(),
                    ]);

                $updated++;
            } else {
                // No class found for this training, skip
                $skipped++;
                \Log::warning("No class found for training {$enrollment->training_id}, enrollment for user {$enrollment->user_id} not updated");
            }
        }

        \Log::info("Assigned classes to enrollments: {$updated} updated, {$skipped} skipped");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We cannot reliably reverse this migration as we don't know
        // which enrollments originally had no class assigned
        // So we'll leave the data as is
    }
};
