<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'estimated_hours' => fake()->optional()->randomFloat(1, 1, 40),
            'actual_hours' => fake()->optional()->randomFloat(1, 1, 50),
            'notes' => fake()->optional()->paragraph(),
            'status_id' => Status::factory(),
            'program_id' => Program::factory(),
            'assigned_to' => fake()->optional()->randomElement([null, User::factory()]),
        ];
    }

    /**
     * Indicate that the task has high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => 'high',
        ]);
    }

    /**
     * Indicate that the task is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'due_date' => fake()->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    /**
     * Indicate that the task is assigned to a specific user.
     */
    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'assigned_to' => $user->id,
        ]);
    }
}
