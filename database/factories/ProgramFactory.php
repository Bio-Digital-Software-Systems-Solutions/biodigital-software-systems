<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Program>
 */
class ProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'start_date' => fake()->dateTimeBetween('now', '+1 week'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
            'budget' => fake()->randomFloat(2, 10000, 500000),
            'status' => fake()->randomElement(['draft', 'active', 'paused', 'completed', 'cancelled']),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'progress_percentage' => fake()->numberBetween(0, 100),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the program is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the program is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
            'progress_percentage' => 100,
        ]);
    }

    /**
     * Indicate that the program has high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => 'high',
        ]);
    }
}
