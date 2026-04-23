<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IntegrationPathwayTemplate>
 */
class IntegrationPathwayTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'target_type' => fake()->optional()->randomElement(['group', 'department']),
            'is_default' => false,
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }

    public function forGroups(): static
    {
        return $this->state(fn (array $attributes): array => [
            'target_type' => 'group',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
