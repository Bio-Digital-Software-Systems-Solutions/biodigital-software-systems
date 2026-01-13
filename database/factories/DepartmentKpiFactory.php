<?php

namespace Database\Factories;

use App\Enums\Report\TrendDirection;
use App\Models\Department;
use App\Models\DepartmentKpi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DepartmentKpi>
 */
class DepartmentKpiFactory extends Factory
{
    protected $model = DepartmentKpi::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'unit' => fake()->randomElement(['%', '€', 'h', 'units', 'points']),
            'target_value' => fake()->randomFloat(2, 50, 100),
            'warning_threshold' => fake()->randomFloat(2, 30, 50),
            'critical_threshold' => fake()->randomFloat(2, 10, 30),
            'trend_direction' => fake()->randomElement(TrendDirection::cases()),
            'calculation_method' => null,
            'data_source' => null,
            'is_active' => true,
            'display_order' => fake()->numberBetween(1, 10),
            'config' => [],
            'metadata' => [],
        ];
    }

    /**
     * Set KPI for a specific department.
     */
    public function forDepartment(Department $department): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $department->id,
        ]);
    }

    /**
     * Set KPI as active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Set KPI as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set trend direction to higher is better.
     */
    public function higherIsBetter(): static
    {
        return $this->state(fn (array $attributes) => [
            'trend_direction' => TrendDirection::HIGHER_IS_BETTER,
        ]);
    }

    /**
     * Set trend direction to lower is better.
     */
    public function lowerIsBetter(): static
    {
        return $this->state(fn (array $attributes) => [
            'trend_direction' => TrendDirection::LOWER_IS_BETTER,
        ]);
    }

    /**
     * Set trend direction to target is best.
     */
    public function targetIsBest(): static
    {
        return $this->state(fn (array $attributes) => [
            'trend_direction' => TrendDirection::TARGET_IS_BEST,
        ]);
    }

    /**
     * Set KPI unit.
     */
    public function withUnit(string $unit): static
    {
        return $this->state(fn (array $attributes) => [
            'unit' => $unit,
        ]);
    }

    /**
     * Set target value.
     */
    public function withTarget(float $target): static
    {
        return $this->state(fn (array $attributes) => [
            'target_value' => $target,
        ]);
    }
}
