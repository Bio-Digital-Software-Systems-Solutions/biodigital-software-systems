<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'student_number' => 'STU'.fake()->unique()->numberBetween(100000, 999999),
            'level' => fake()->randomElement(['Beginner', 'Intermediate', 'Advanced', 'Expert']),
            'enrollment_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'emergency_contact' => fake()->name(),
            'emergency_phone' => fake()->phoneNumber(),
            'is_active' => fake()->boolean(90),
        ];
    }
}
