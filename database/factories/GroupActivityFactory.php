<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroupActivity>
 */
class GroupActivityFactory extends Factory
{
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-1 month', '+2 months');

        return [
            'group_id' => Group::factory(),
            'assigned_to' => fake()->optional()->randomElement([null, User::factory()]),
            'created_by' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'activity_date' => $date->format('Y-m-d'),
            'start_time' => fake()->optional()->time('H:i'),
            'end_time' => fake()->optional()->time('H:i'),
            'status' => fake()->randomElement(['planned', 'in_progress', 'completed', 'cancelled']),
            'type' => fake()->randomElement(['meeting', 'task', 'event', 'other']),
            'location' => fake()->optional()->address(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function planned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'planned',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes): array => [
            'activity_date' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'status' => 'planned',
        ]);
    }
}
