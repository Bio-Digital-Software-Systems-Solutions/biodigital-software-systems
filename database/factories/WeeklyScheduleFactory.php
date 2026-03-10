<?php

namespace Database\Factories;

use App\Enums\Scheduling\ScheduleStatus;
use App\Models\Department;
use App\Models\Scheduling\WeeklySchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Scheduling\WeeklySchedule>
 */
class WeeklyScheduleFactory extends Factory
{
    protected $model = WeeklySchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        return [
            'department_id' => Department::factory(),
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'status' => ScheduleStatus::DRAFT,
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => null,
            'published_by' => null,
            'published_at' => null,
            'locked_at' => null,
        ];
    }

    /**
     * Indicate that the schedule is published.
     */
    public function published(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => ScheduleStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the schedule is locked.
     */
    public function locked(): static
    {
        return $this->state(fn(array $attributes): array => [
            'status' => ScheduleStatus::LOCKED,
            'locked_at' => now(),
        ]);
    }

    /**
     * Indicate that the schedule is for a specific week.
     */
    public function forWeek(Carbon $date): static
    {
        $weekStart = $date->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        return $this->state(fn(array $attributes): array => [
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
        ]);
    }

    /**
     * Indicate that the schedule is for a specific department.
     */
    public function forDepartment(Department $department): static
    {
        return $this->state([
            'department_id' => $department->id,
        ]);
    }

    /**
     * Indicate that the schedule was created by a user.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn(array $attributes): array => [
            'created_by' => $user->id,
        ]);
    }
}
