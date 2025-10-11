<?php

namespace Database\Factories;

use App\Models\Training;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            'QCM - Bases de données relationnelles',
            'Évaluation SQL pratique',
            'Test de connaissances - Introduction',
            'Quiz intermédiaire',
            'Examen final',
            'Test de compétences',
        ];

        return [
            'training_id' => Training::factory(),
            'title' => fake()->randomElement($titles),
            'description' => fake()->sentence(10),
            'duration_minutes' => 30,
            'max_score' => 100,
            'passing_score' => 60,
            'available_from' => now()->subDays(7),
            'available_until' => now()->addDays(30),
            'is_active' => true,
        ];
    }
}
