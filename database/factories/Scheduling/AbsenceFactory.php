<?php

namespace Database\Factories\Scheduling;

use App\Enums\Scheduling\AbsenceStatus;
use App\Enums\Scheduling\AbsenceType;
use App\Models\Department;
use App\Models\Scheduling\Absence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbsenceFactory extends Factory
{
    protected $model = Absence::class;

    public function definition(): array
    {
        $startDate = Carbon::now()->addDays(rand(1, 30));
        $endDate = (clone $startDate)->addDays(rand(0, 5));

        return [
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'type' => $this->faker->randomElement(AbsenceType::cases()),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_full_day' => true,
            'is_half_day_start' => false,
            'is_half_day_end' => false,
            'status' => AbsenceStatus::PENDING,
            'reason' => $this->faker->optional()->sentence(),
        ];
    }

    public function vacation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AbsenceType::VACATION,
        ]);
    }

    public function sick(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AbsenceType::SICK,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AbsenceStatus::PENDING,
        ]);
    }

    public function approved(User $approver = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AbsenceStatus::APPROVED,
            'approved_by' => $approver?->id ?? User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(User $approver = null, ?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AbsenceStatus::REJECTED,
            'approved_by' => $approver?->id ?? User::factory(),
            'rejection_reason' => $reason ?? $this->faker->sentence(),
            'rejected_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AbsenceStatus::CANCELLED,
        ]);
    }

    public function forDates(Carbon $start, Carbon $end): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }

    public function halfDayStart(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_full_day' => false,
            'is_half_day_start' => true,
            'is_half_day_end' => false,
        ]);
    }

    public function halfDayEnd(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_full_day' => false,
            'is_half_day_start' => false,
            'is_half_day_end' => true,
        ]);
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => Carbon::now()->subDays(1),
            'end_date' => Carbon::now()->addDays(1),
            'status' => AbsenceStatus::APPROVED,
        ]);
    }

    public function past(): static
    {
        $start = Carbon::now()->subDays(rand(10, 30));
        $end = (clone $start)->addDays(rand(1, 3));

        return $this->state(fn (array $attributes) => [
            'start_date' => $start,
            'end_date' => $end,
            'status' => AbsenceStatus::APPROVED,
        ]);
    }

    public function future(): static
    {
        $start = Carbon::now()->addDays(rand(10, 30));
        $end = (clone $start)->addDays(rand(1, 3));

        return $this->state(fn (array $attributes) => [
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }
}
