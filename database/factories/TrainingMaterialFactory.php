<?php

namespace Database\Factories;

use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrainingMaterial>
 */
class TrainingMaterialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['pdf', 'powerpoint', 'video', 'audio', 'document'];
        $type = fake()->randomElement($types);

        return [
            'training_id' => Training::factory(),
            'teacher_id' => null,
            'title' => fake()->sentence(3),
            'type' => $type,
            'duration' => in_array($type, ['video', 'audio'], true)
                ? fake()->randomElement(['5 min', '15 min', '30 min', '1h'])
                : null,
            'url' => fake()->url(),
            'file_path' => null,
            'description' => fake()->optional()->paragraph(),
            'order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }

    public function withTeacher(?User $teacher = null): self
    {
        return $this->state(fn () => [
            'teacher_id' => $teacher?->id ?? User::factory(),
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
