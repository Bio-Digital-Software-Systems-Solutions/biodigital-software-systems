<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrainingTopic>
 */
class TrainingTopicFactory extends Factory
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
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(2),
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
