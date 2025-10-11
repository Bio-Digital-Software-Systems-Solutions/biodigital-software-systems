<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrainingMaterial>
 */
class TrainingMaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['pdf', 'powerpoint', 'video', 'audio'];
        $type = fake()->randomElement($types);

        return [
            'training_id' => \App\Models\Training::factory(),
            'title' => fake()->sentence(3),
            'type' => $type,
            'duration' => $type === 'video' || $type === 'audio' ? fake()->time('H:i:s') : null,
            'url' => fake()->url(),
            'order' => fake()->numberBetween(1, 20),
        ];
    }
}
