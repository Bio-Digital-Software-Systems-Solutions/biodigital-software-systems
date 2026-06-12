<?php

namespace Database\Seeders;

use App\Models\Training;
use App\Models\TrainingEvaluation;
use App\Models\User;
use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 20 trainings with related data
        Training::factory(3)->create()->each(function ($training): void {
            // Topics and Materials are now seeded separately by TrainingTopicSeeder and TrainingMaterialSeeder
            // Classes are now seeded separately by TrainingClassSeeder

            // Create 2-3 evaluations for each training
            TrainingEvaluation::factory(random_int(2, 3))->create([
                'training_id' => $training->id,
            ]);

            // Enroll some random users
            $users = User::inRandomOrder()->limit(random_int(5, 15))->get();
            foreach ($users as $user) {
                $training->students()->attach($user->id, [
                    'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
                    'progress' => fake()->randomFloat(2, 0, 100),
                    'grade' => fake()->optional()->randomFloat(2, 0, 20),
                    'attendance_rate' => fake()->randomFloat(2, 0, 100),
                    'enrolled_at' => now()->subDays(random_int(1, 90)),
                    'completed_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
                ]);
            }

            // Update students count
            $training->update(['students_count' => $training->students()->count()]);
        });
    }
}
