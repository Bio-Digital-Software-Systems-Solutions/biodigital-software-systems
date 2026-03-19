<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Training>
 */
class TrainingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Développement Web', 'Data Science', 'Design UI/UX', 'Marketing Digital', 'Gestion de Projet', 'Leadership', 'Communication'];
        $levels = ['beginner', 'intermediate', 'advanced'];

        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(3),
            'duration' => fake()->randomElement(['2 mois', '3 mois', '4 mois', '6 mois', '1 an']),
            'level' => fake()->randomElement($levels),
            'price' => fake()->randomFloat(2, 0, 2000),
            'image' => 'training-'.fake()->numberBetween(1, 5).'.jpg',
            'category' => fake()->randomElement($categories),
            'rating' => fake()->randomFloat(2, 3.5, 5.0),
            'students_count' => fake()->numberBetween(0, 500),
            'is_active' => fake()->boolean(80),
            'visibility' => 'public',
        ];
    }

    /**
     * Set the training as public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes): array => ['visibility' => 'public']);
    }

    /**
     * Set the training as private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes): array => ['visibility' => 'private']);
    }
}
