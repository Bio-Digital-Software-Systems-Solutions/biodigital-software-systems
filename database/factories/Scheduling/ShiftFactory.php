<?php

namespace Database\Factories\Scheduling;

use App\Enums\Scheduling\ShiftStatus;
use App\Enums\Scheduling\ShiftType;
use App\Models\Department;
use App\Models\Scheduling\Shift;
use App\Models\Scheduling\WeeklySchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        $types = ShiftType::cases();
        $type = $this->faker->randomElement($types);
        $times = $this->getTimesForType($type);

        return [
            'weekly_schedule_id' => WeeklySchedule::factory(),
            'department_id' => Department::factory(),
            'user_id' => $this->faker->optional(0.7)->passthrough(User::factory()),
            'date' => Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays(random_int(0, 6)),
            'start_time' => $times['start'],
            'end_time' => $times['end'],
            'break_duration' => $this->faker->randomElement([0, 30, 60]),
            'type' => $type,
            'status' => ShiftStatus::DRAFT,
            'title' => $this->faker->optional()->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'location' => $this->faker->optional()->city(),
            'color' => $this->faker->optional()->hexColor(),
            'min_employees' => 1,
            'max_employees' => $this->faker->numberBetween(1, 3),
            'required_skills' => [],
            'hourly_rate' => $this->faker->optional()->randomFloat(2, 10, 25),
            'is_overtime' => $this->faker->boolean(10),
            'requires_approval' => $this->faker->boolean(20),
        ];
    }

    protected function getTimesForType(ShiftType $type): array
    {
        return match ($type) {
            ShiftType::MORNING => ['start' => '06:00', 'end' => '14:00'],
            ShiftType::AFTERNOON => ['start' => '14:00', 'end' => '22:00'],
            ShiftType::EVENING => ['start' => '18:00', 'end' => '23:00'],
            ShiftType::NIGHT => ['start' => '22:00', 'end' => '06:00'],
            ShiftType::FULL_DAY => ['start' => '09:00', 'end' => '17:00'],
            ShiftType::SPLIT => ['start' => '10:00', 'end' => '14:00'],
            ShiftType::ON_CALL => ['start' => '00:00', 'end' => '23:59'],
            ShiftType::CUSTOM => ['start' => '08:00', 'end' => '16:00'],
        };
    }

    public function unassigned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => null,
        ]);
    }

    public function assigned(User $user = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user?->id ?? User::factory(),
            'assigned_at' => now(),
            'status' => ShiftStatus::CONFIRMED,
        ]);
    }

    public function morning(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ShiftType::MORNING,
            'start_time' => '06:00',
            'end_time' => '14:00',
        ]);
    }

    public function afternoon(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ShiftType::AFTERNOON,
            'start_time' => '14:00',
            'end_time' => '22:00',
        ]);
    }

    public function night(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ShiftType::NIGHT,
            'start_time' => '22:00',
            'end_time' => '06:00',
        ]);
    }

    public function fullDay(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ShiftType::FULL_DAY,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);
    }

    public function overtime(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_overtime' => true,
        ]);
    }

    public function forDate(Carbon $date): static
    {
        return $this->state(fn (array $attributes): array => [
            'date' => $date,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ShiftStatus::DRAFT,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ShiftStatus::PUBLISHED,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ShiftStatus::CONFIRMED,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ShiftStatus::IN_PROGRESS,
            'checked_in_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ShiftStatus::COMPLETED,
            'checked_in_at' => now()->subHours(8),
            'checked_out_at' => now(),
        ]);
    }
}
