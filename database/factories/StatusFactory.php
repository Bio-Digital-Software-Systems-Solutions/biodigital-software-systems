<?php

namespace Database\Factories;

use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Status>
 */
class StatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->hexColor(),
            'is_active' => fake()->boolean(95),
        ];
    }

    /**
     * Indicate that the status is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'pending',
            'color' => '#f59e0b',
        ]);
    }

    /**
     * Indicate that the status is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'in_progress',
            'color' => '#3b82f6',
        ]);
    }

    /**
     * Indicate that the status is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'completed',
            'color' => '#10b981',
        ]);
    }

    /**
     * Indicate that the status is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'cancelled',
            'color' => '#ef4444',
        ]);
    }
}
