<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\Training;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $trainings = Training::all();

        if ($trainings->isEmpty()) {
            $this->command->warn('No trainings found. Please run TrainingSeeder first.');

            return;
        }

        $quizTemplates = [
            [
                'title' => 'QCM - Bases de données relationnelles',
                'description' => 'Testez vos connaissances sur les bases de données relationnelles',
                'duration_minutes' => 30,
                'max_score' => 100,
                'passing_score' => 60,
            ],
            [
                'title' => 'Évaluation SQL pratique',
                'description' => 'Exercices pratiques de requêtes SQL',
                'duration_minutes' => 45,
                'max_score' => 100,
                'passing_score' => 70,
            ],
            [
                'title' => 'Test intermédiaire',
                'description' => 'Évaluation de mi-parcours',
                'duration_minutes' => 30,
                'max_score' => 50,
                'passing_score' => 30,
            ],
        ];

        // Créer 2-3 quizzes par formation
        foreach ($trainings as $training) {
            $numberOfQuizzes = rand(2, 3);

            for ($i = 0; $i < $numberOfQuizzes; $i++) {
                $template = $quizTemplates[$i % count($quizTemplates)];

                Quiz::create([
                    'training_id' => $training->id,
                    'title' => $template['title'],
                    'description' => $template['description'],
                    'duration_minutes' => $template['duration_minutes'],
                    'max_score' => $template['max_score'],
                    'passing_score' => $template['passing_score'],
                    'available_from' => now()->subDays(7),
                    'available_until' => now()->addDays(30),
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Quizzes created successfully.');
    }
}
