<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\DepartmentPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DepartmentPosition>
 */
class DepartmentPositionFactory extends Factory
{
    protected $model = DepartmentPosition::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'name' => fake()->randomElement(['Manager', 'Coordinator', 'Assistant', 'Specialist', 'Analyst', 'Lead']),
            'code' => fake()->unique()->regexify('[A-Z]{3}'),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->optional()->hexColor(),
            'min_staff' => 1,
            'max_staff' => fake()->optional()->numberBetween(2, 10),
            'required_skills' => [],
            'hourly_rate' => fake()->optional()->randomFloat(2, 10, 50),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
