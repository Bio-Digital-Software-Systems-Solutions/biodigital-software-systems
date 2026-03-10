<?php

namespace Database\Factories;

use App\Enums\Report\ActivityCategory;
use App\Models\Department;
use App\Models\DepartmentActivity;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DepartmentActivity>
 */
class DepartmentActivityFactory extends Factory
{
    protected $model = DepartmentActivity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'user_id' => User::factory(),
            'category' => fake()->randomElement(ActivityCategory::cases()),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'date' => fake()->dateTimeBetween('-1 month', 'now'),
            'duration_hours' => fake()->randomFloat(1, 0.5, 8.0),
            'participants' => [],
            'outcomes' => fake()->optional()->sentence(),
            'metrics' => null,
            'related_project_id' => null,
            'metadata' => [],
        ];
    }

    /**
     * Set activity for a specific department.
     */
    public function forDepartment(Department $department): static
    {
        return $this->state(fn (array $attributes): array => [
            'department_id' => $department->id,
        ]);
    }

    /**
     * Set activity user.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set activity date.
     */
    public function onDate(Carbon $date): static
    {
        return $this->state(fn (array $attributes): array => [
            'date' => $date,
        ]);
    }

    /**
     * Set activity category.
     */
    public function withCategory(ActivityCategory $category): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => $category,
        ]);
    }

    /**
     * Set activity duration.
     */
    public function withDuration(float $hours): static
    {
        return $this->state(fn (array $attributes): array => [
            'duration_hours' => $hours,
        ]);
    }

    /**
     * Set related project.
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes): array => [
            'related_project_id' => $project->id,
        ]);
    }

    /**
     * Add participants.
     */
    public function withParticipants(array $userIds): static
    {
        return $this->state(fn (array $attributes): array => [
            'participants' => $userIds,
        ]);
    }
}
