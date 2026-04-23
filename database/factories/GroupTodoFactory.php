<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroupTodo>
 */
class GroupTodoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'assigned_to' => fake()->optional()->randomElement([null, User::factory()]),
            'created_by' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'due_date' => fake()->optional()->dateTimeBetween('-1 month', '+2 months'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
            'completed_by' => User::factory(),
            'completed_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
            'due_date' => fake()->dateTimeBetween('-2 months', '-1 day'),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => 'high',
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => 'critical',
        ]);
    }
}
