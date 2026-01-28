<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class SprintFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-2 months', '+1 month');
        $endDate = fake()->dateTimeBetween($startDate, '+3 months');

        return [
            'project_id' => Project::factory(),
            'name' => 'Sprint '.fake()->numberBetween(1, 20),
            'goal' => fake()->sentence(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => fake()->randomElement(['planned', 'active', 'completed', 'cancelled']),
            'capacity' => fake()->optional(0.7)->numberBetween(20, 100),
        ];
    }

    /**
     * Indicate that the sprint is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_date' => now()->subDays(fake()->numberBetween(1, 14)),
            'end_date' => now()->addDays(fake()->numberBetween(7, 21)),
        ]);
    }

    /**
     * Indicate that the sprint is planned.
     */
    public function planned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planned',
            'start_date' => now()->addDays(fake()->numberBetween(1, 30)),
            'end_date' => now()->addDays(fake()->numberBetween(31, 60)),
        ]);
    }

    /**
     * Indicate that the sprint is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'start_date' => now()->subDays(fake()->numberBetween(30, 60)),
            'end_date' => now()->subDays(fake()->numberBetween(1, 29)),
        ]);
    }

    /**
     * Indicate that the sprint is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
