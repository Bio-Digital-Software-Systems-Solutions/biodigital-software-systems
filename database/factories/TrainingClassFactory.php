<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrainingClass>
 */
class TrainingClassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('now', '+6 months');
        $startTime = fake()->time('H:i:s', '18:00:00');

        return [
            'training_id' => \App\Models\Training::factory(),
            'teacher_id' => \App\Models\User::whereHas('teacher')->inRandomOrder()->first()?->id,
            'name' => 'Classe '.fake()->numberBetween(1, 20).' - '.fake()->words(2, true),
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => date('H:i:s', strtotime($startTime) + (2 * 3600)),
            'room' => 'Salle '.fake()->numberBetween(1, 10),
            'max_students' => fake()->numberBetween(15, 30),
            'notes' => fake()->optional()->paragraph(1),
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the class is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
            'archived_at' => now()->subMonth(),
            'archive_access_until' => now()->addMonths(5),
        ]);
    }
}
