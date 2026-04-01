<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departmentName = fake()->randomElement([
            'Human Resources',
            'Information Technology',
            'Marketing',
            'Finance',
            'Operations',
            'Sales',
            'Customer Service',
            'Research & Development',
            'Quality Assurance',
            'Legal',
        ]);

        return [
            'name' => $departmentName,
            'code' => fake()->unique()->regexify('[A-Z]{2,4}'),
            'description' => fake()->optional()->paragraph(),
            'head_of_department' => fake()->optional()->randomElement([null, User::factory()]),
            'budget' => fake()->optional()->randomFloat(2, 50000, 1000000),
            'is_active' => fake()->boolean(90),
        ];
    }

    /**
     * Indicate that the department is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the department is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the department has a specific head.
     */
    public function withHead(User $head): static
    {
        return $this->state(fn (array $attributes): array => [
            'head_of_department' => $head->id,
        ]);
    }

    /**
     * Indicate that the department has a specific budget.
     */
    public function withBudget(float $budget): static
    {
        return $this->state(fn (array $attributes): array => [
            'budget' => $budget,
        ]);
    }

    /**
     * Indicate that the department is a sub-department of a given parent.
     */
    public function withParent(Department $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->id,
        ]);
    }
}
