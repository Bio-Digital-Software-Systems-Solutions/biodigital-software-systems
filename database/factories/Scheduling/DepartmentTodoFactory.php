<?php

namespace Database\Factories\Scheduling;

use App\Enums\Scheduling\ShiftTaskStatus;
use App\Enums\Scheduling\TodoPriority;
use App\Models\Department;
use App\Models\Scheduling\DepartmentTodo;
use App\Models\Scheduling\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Scheduling\DepartmentTodo>
 */
class DepartmentTodoFactory extends Factory
{
    protected $model = DepartmentTodo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'shift_id' => null,
            'assigned_to' => null,
            'created_by' => User::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'status' => ShiftTaskStatus::TODO,
            'priority' => $this->faker->randomElement(TodoPriority::cases()),
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+2 weeks'),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'estimated_minutes' => $this->faker->optional()->numberBetween(15, 240),
        ];
    }

    /**
     * Create todo linked to a specific shift.
     */
    public function forShift(Shift $shift): static
    {
        return $this->state(fn (array $attributes): array => [
            'shift_id' => $shift->id,
            'department_id' => $shift->department_id,
        ]);
    }

    /**
     * Create todo assigned to a specific user.
     */
    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'assigned_to' => $user->id,
        ]);
    }

    /**
     * Create a completed todo.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ShiftTaskStatus::COMPLETED,
            'completed_at' => now(),
            'completed_by' => $attributes['created_by'] ?? User::factory(),
        ]);
    }

    /**
     * Create an in progress todo.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ShiftTaskStatus::IN_PROGRESS,
        ]);
    }

    /**
     * Create a blocked todo.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ShiftTaskStatus::BLOCKED,
        ]);
    }

    /**
     * Create an urgent priority todo.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => TodoPriority::URGENT,
        ]);
    }

    /**
     * Create a high priority todo.
     */
    public function high(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => TodoPriority::HIGH,
        ]);
    }

    /**
     * Create an overdue todo.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'due_date' => now()->subDays(random_int(1, 7)),
            'status' => ShiftTaskStatus::TODO,
        ]);
    }

    /**
     * Create a todo due today.
     */
    public function dueToday(): static
    {
        return $this->state(fn (array $attributes): array => [
            'due_date' => now(),
            'status' => ShiftTaskStatus::TODO,
        ]);
    }
}
