<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Visitor>
 */
class VisitorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'city' => fake()->optional()->city(),
            'country' => fake()->optional()->country(),
            'gender' => fake()->optional()->randomElement(['male', 'female', 'other']),
            'date_of_birth' => fake()->optional()->dateTimeBetween('-60 years', '-16 years'),
            'notes' => fake()->optional()->sentence(),
            'source' => fake()->randomElement(['friend', 'online', 'event', 'walk_in', 'other']),
            'first_visit_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    public function integrated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'integrated',
            'user_id' => User::factory(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }
}
