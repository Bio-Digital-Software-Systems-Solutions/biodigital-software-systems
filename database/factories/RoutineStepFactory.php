<?php

namespace Database\Factories;

use App\Enums\RoutineStepValidationStatus;
use App\Models\Routine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoutineStep>
 */
class RoutineStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'routine_id' => Routine::factory(),
            'parent_id' => null,
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'instructions' => fake()->paragraph(),
            'duration_minutes' => fake()->numberBetween(5, 120),
            'sort_order' => 0,
            'is_required' => true,
            'requires_validation' => true,
            'validation_status' => RoutineStepValidationStatus::Pending,
        ];
    }

    public function validated(): static
    {
        return $this->state(fn (): array => [
            'validation_status' => RoutineStepValidationStatus::Validated,
            'validated_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'validation_status' => RoutineStepValidationStatus::Rejected,
            'validated_at' => now(),
            'validation_notes' => fake()->sentence(),
        ]);
    }

    public function optional(): static
    {
        return $this->state(fn (): array => [
            'is_required' => false,
        ]);
    }
}
