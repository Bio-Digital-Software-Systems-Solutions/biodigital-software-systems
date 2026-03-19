<?php

namespace Database\Factories;

use App\Enums\RoutineFrequency;
use App\Enums\RoutineStatus;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Routine>
 */
class RoutineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'department_id' => Department::factory(),
            'status' => RoutineStatus::Draft,
            'frequency' => fake()->randomElement(RoutineFrequency::cases()),
            'responsible_id' => User::factory(),
            'created_by' => User::factory(),
            'estimated_duration_minutes' => fake()->numberBetween(15, 480),
            'is_active' => false,
            'sort_order' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => RoutineStatus::Draft,
            'is_active' => false,
        ]);
    }

    public function pendingApproval(): static
    {
        return $this->state(fn (): array => [
            'status' => RoutineStatus::PendingApproval,
            'is_active' => false,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => RoutineStatus::Approved,
            'approved_by' => User::factory(),
            'approved_at' => now(),
            'is_active' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => RoutineStatus::Active,
            'approved_by' => User::factory(),
            'approved_at' => now(),
            'activated_at' => now(),
            'is_active' => true,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => RoutineStatus::Archived,
            'is_active' => false,
        ]);
    }
}
