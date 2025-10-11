<?php

namespace Database\Seeders;

use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\User;
use Illuminate\Database\Seeder;

class TrainingClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $trainings = Training::all();
        $teachers = User::whereHas('teacher')->get();

        if ($teachers->isEmpty()) {
            $this->command->warn('No teachers found. Skipping training class seeding.');

            return;
        }

        foreach ($trainings as $training) {
            // Create 8-15 classes for each training
            $numberOfClasses = rand(8, 15);

            for ($i = 0; $i < $numberOfClasses; $i++) {
                // Distribute classes over the next 6 months
                $weeksFromNow = $i * 2; // Bi-weekly classes
                $date = now()->addWeeks($weeksFromNow)->format('Y-m-d');

                // Alternate between morning (9:00-11:00) and afternoon (14:00-16:00) sessions
                $isMorning = $i % 2 === 0;
                $startTime = $isMorning ? '09:00:00' : '14:00:00';
                $endTime = $isMorning ? '11:00:00' : '16:00:00';

                TrainingClass::create([
                    'training_id' => $training->id,
                    'teacher_id' => $teachers->random()->id,
                    'name' => 'Classe '.($i + 1).' - '.$training->title,
                    'date' => $date,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'room' => 'Salle '.rand(1, 10),
                    'max_students' => rand(15, 30),
                    'notes' => fake()->optional(0.3)->sentence(),
                ]);
            }
        }

        $this->command->info('Training classes seeded successfully!');
        $this->command->info('Total classes created: '.TrainingClass::count());
    }
}
