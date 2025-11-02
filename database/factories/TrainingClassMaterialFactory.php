<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrainingClassMaterial>
 */
class TrainingClassMaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['pdf', 'video', 'audio', 'powerpoint', 'document'];
        $type = fake()->randomElement($types);

        return [
            'training_class_id' => \App\Models\TrainingClass::factory(),
            'teacher_id' => \App\Models\User::factory(),
            'title' => fake()->sentence(4),
            'type' => $type,
            'file_path' => null,
            'url' => fake()->url(),
            'duration' => $type === 'video' || $type === 'audio' ? fake()->randomElement(['5 min', '15 min', '30 min', '1h']) : null,
            'description' => fake()->optional()->paragraph(),
            'order' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
