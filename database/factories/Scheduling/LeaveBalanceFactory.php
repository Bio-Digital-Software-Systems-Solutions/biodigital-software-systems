<?php

namespace Database\Factories\Scheduling;

use App\Enums\Scheduling\AbsenceType;
use App\Models\Department;
use App\Models\Scheduling\LeaveBalance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveBalanceFactory extends Factory
{
    protected $model = LeaveBalance::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'year' => Carbon::now()->year,
            'leave_type' => $this->faker->randomElement(AbsenceType::cases())->value,
            'entitled_days' => $this->faker->randomFloat(2, 15, 30),
            'taken_days' => $this->faker->randomFloat(2, 0, 10),
            'pending_days' => $this->faker->randomFloat(2, 0, 5),
            'carried_over' => $this->faker->randomFloat(2, 0, 5),
        ];
    }

    public function forYear(int $year): static
    {
        return $this->state(fn (array $attributes): array => [
            'year' => $year,
        ]);
    }

    public function vacation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'leave_type' => AbsenceType::VACATION->value,
        ]);
    }

    public function sick(): static
    {
        return $this->state(fn (array $attributes): array => [
            'leave_type' => AbsenceType::SICK->value,
        ]);
    }

    public function withFullEntitlement(float $days = 25): static
    {
        return $this->state(fn (array $attributes): array => [
            'entitled_days' => $days,
            'taken_days' => 0,
            'pending_days' => 0,
            'carried_over' => 0,
        ]);
    }

    public function exhausted(): static
    {
        return $this->state(function (array $attributes): array {
            $entitled = $attributes['entitled_days'] ?? 25;
            return [
                'entitled_days' => $entitled,
                'taken_days' => $entitled,
                'pending_days' => 0,
            ];
        });
    }
}
