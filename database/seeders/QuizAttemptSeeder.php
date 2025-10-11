<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuizAttemptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quizzes = Quiz::all();
        $students = User::whereHas('trainings', function ($query) {
            $query->where('status', 'approved');
        })->get();

        if ($quizzes->isEmpty()) {
            $this->command->warn('No quizzes found. Please run QuizSeeder first.');

            return;
        }

        if ($students->isEmpty()) {
            $this->command->warn('No enrolled students found.');

            return;
        }

        // Créer quelques tentatives complétées
        foreach ($students->take(5) as $student) {
            $studentTrainings = $student->trainings()->where('training_enrollments.status', 'approved')->pluck('trainings.id');

            $studentQuizzes = Quiz::whereIn('training_id', $studentTrainings)->get();

            foreach ($studentQuizzes->take(rand(1, 3)) as $quiz) {
                // 70% de chances de créer une tentative complétée
                if (rand(1, 10) <= 7) {
                    $score = rand(40, 100);

                    QuizAttempt::create([
                        'quiz_id' => $quiz->id,
                        'student_id' => $student->id,
                        'started_at' => now()->subDays(rand(1, 14)),
                        'completed_at' => now()->subDays(rand(0, 7)),
                        'time_remaining_seconds' => 0,
                        'score' => $score,
                        'status' => 'completed',
                        'answers' => [],
                    ]);
                }
            }
        }

        $this->command->info('Quiz attempts created successfully.');
    }
}
