<?php

namespace Database\Factories;

use App\Enums\Scheduling\ShiftStatus;
use App\Enums\Scheduling\ShiftType;
use App\Models\Department;
use App\Models\Scheduling\Shift;
use App\Models\Scheduling\WeeklySchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Scheduling\Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = Carbon::now()->addDays($this->faker->numberBetween(0, 7));

        return [
            'weekly_schedule_id' => WeeklySchedule::factory(),
            'department_id' => Department::factory(),
            'date' => $date,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'break_duration' => 30,
            'type' => ShiftType::FULL_DAY,
            'status' => ShiftStatus::DRAFT,
            'title' => $this->faker->optional()->word(),
            'description' => $this->faker->optional()->sentence(),
            'location' => $this->faker->optional()->address(),
            'min_employees' => 1,
            'max_employees' => 1,
            'is_overtime' => false,
            'requires_approval' => false,
        ];
    }

    /**
     * Indicate that the shift is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ShiftStatus::DRAFT,
        ]);
    }

    /**
     * Indicate that the shift is published.
     */
    public function published(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ShiftStatus::PUBLISHED,
        ]);
    }

    /**
     * Indicate that the shift is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ShiftStatus::CONFIRMED,
        ]);
    }

    /**
     * Indicate that the shift is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ShiftStatus::IN_PROGRESS,
        ]);
    }

    /**
     * Indicate that the shift is completed.
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ShiftStatus::COMPLETED,
        ]);
    }

    /**
     * Indicate that the shift is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ShiftStatus::CANCELLED,
        ]);
    }

    /**
     * Indicate that the shift is a no-show.
     */
    public function noShow(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ShiftStatus::NO_SHOW,
        ]);
    }

    /**
     * Assign the shift to a user.
     */
    public function assignedTo(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Morning shift.
     */
    public function morning(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => ShiftType::MORNING,
            'start_time' => '06:00',
            'end_time' => '14:00',
        ]);
    }

    /**
     * Afternoon shift.
     */
    public function afternoon(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => ShiftType::AFTERNOON,
            'start_time' => '14:00',
            'end_time' => '22:00',
        ]);
    }

    /**
     * Evening shift.
     */
    public function evening(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => ShiftType::EVENING,
            'start_time' => '18:00',
            'end_time' => '23:00',
        ]);
    }

    /**
     * Night shift.
     */
    public function night(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => ShiftType::NIGHT,
            'start_time' => '22:00',
            'end_time' => '06:00',
        ]);
    }

    /**
     * For a specific schedule.
     */
    public function forSchedule(WeeklySchedule $schedule): static
    {
        return $this->state([
            'weekly_schedule_id' => $schedule->id,
            'department_id' => $schedule->department_id,
        ]);
    }

    /**
     * For a specific department.
     */
    public function forDepartment(Department $department): static
    {
        return $this->state([
            'department_id' => $department->id,
        ]);
    }

    /**
     * On a specific date.
     */
    public function onDate(Carbon $date): static
    {
        return $this->state(fn(array $attributes) => [
            'date' => $date,
        ]);
    }

    /**
     * Overtime shift.
     */
    public function overtime(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_overtime' => true,
        ]);
    }
}
