<?php

namespace Database\Factories\Scheduling;

use App\Enums\Scheduling\ScheduleStatus;
use App\Models\Department;
use App\Models\Scheduling\WeeklySchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeeklyScheduleFactory extends Factory
{
    protected $model = WeeklySchedule::class;

    public function definition(): array
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);

        return [
            'department_id' => Department::factory(),
            'week_start' => $weekStart,
            'week_end' => $weekStart->copy()->endOfWeek(Carbon::SUNDAY),
            'status' => ScheduleStatus::DRAFT,
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ScheduleStatus::DRAFT,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ScheduleStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ScheduleStatus::LOCKED,
            'published_at' => now()->subDays(2),
            'locked_at' => now(),
        ]);
    }

    public function forWeek(Carbon $weekStart): static
    {
        return $this->state(fn (array $attributes) => [
            'week_start' => $weekStart->startOfWeek(Carbon::MONDAY),
            'week_end' => $weekStart->copy()->endOfWeek(Carbon::SUNDAY),
        ]);
    }
}
