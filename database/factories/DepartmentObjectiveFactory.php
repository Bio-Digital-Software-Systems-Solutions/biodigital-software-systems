<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\Report\ObjectiveStatus;
use App\Models\Department;
use App\Models\DepartmentObjective;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DepartmentObjective>
 */
class DepartmentObjectiveFactory extends Factory
{
    protected $model = DepartmentObjective::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        return [
            'department_id' => Department::factory(),
            'parent_id' => null,
            'assigned_to' => User::factory(),
            'title' => fake()->sentence(6),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(ObjectiveStatus::cases()),
            'priority' => fake()->randomElement(Priority::cases()),
            'progress_percentage' => fake()->numberBetween(0, 100),
            'target_date' => fake()->dateTimeBetween($periodStart, $periodEnd->copy()->addMonth()),
            'completed_at' => null,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'key_results' => [],
            'success_criteria' => [],
            'blockers' => [],
            'metadata' => [],
        ];
    }

    /**
     * Set objective for a specific department.
     */
    public function forDepartment(Department $department): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $department->id,
        ]);
    }

    /**
     * Set objective assignee.
     */
    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $user->id,
        ]);
    }

    /**
     * Set objective status.
     */
    public function withStatus(ObjectiveStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'completed_at' => $status === ObjectiveStatus::COMPLETED ? now() : null,
            'progress_percentage' => $status === ObjectiveStatus::COMPLETED ? 100 : $attributes['progress_percentage'],
        ]);
    }

    /**
     * Set objective as completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ObjectiveStatus::COMPLETED,
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);
    }

    /**
     * Set objective as in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ObjectiveStatus::IN_PROGRESS,
            'progress_percentage' => fake()->numberBetween(20, 80),
        ]);
    }

    /**
     * Set objective as at risk.
     */
    public function atRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ObjectiveStatus::AT_RISK,
        ]);
    }

    /**
     * Set objective as overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_date' => now()->subDays(fake()->numberBetween(1, 30)),
            'status' => ObjectiveStatus::IN_PROGRESS,
        ]);
    }

    /**
     * Set objective period.
     */
    public function forPeriod(Carbon $start, Carbon $end): static
    {
        return $this->state(fn (array $attributes) => [
            'period_start' => $start,
            'period_end' => $end,
            'target_date' => fake()->dateTimeBetween($start, $end),
        ]);
    }

    /**
     * Set parent objective.
     */
    public function withParent(DepartmentObjective $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'department_id' => $parent->department_id,
        ]);
    }

    /**
     * Add key results.
     */
    public function withKeyResults(array $keyResults): static
    {
        return $this->state(fn (array $attributes) => [
            'key_results' => $keyResults,
        ]);
    }
}
