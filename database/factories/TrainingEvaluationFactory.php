<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrainingEvaluation>
 */
class TrainingEvaluationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_id' => \App\Models\Training::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(2),
            'max_score' => fake()->numberBetween(20, 100),
            'due_date' => fake()->dateTimeBetween('now', '+3 months'),
        ];
    }
}
