<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
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
            'specialization' => fake()->randomElement([
                'Mathematics',
                'Physics',
                'Chemistry',
                'Computer Science',
                'Engineering',
                'Business Management',
                'Marketing',
                'Finance',
                'Languages',
                'Psychology',
            ]),
            'experience_years' => fake()->numberBetween(1, 30),
            'bio' => fake()->paragraph(3),
            'qualifications' => [
                fake()->randomElement(['PhD', 'Master\'s Degree', 'Bachelor\'s Degree']),
                fake()->randomElement(['Teaching Certificate', 'Professional Certification', 'Industry Experience']),
            ],
            'phone' => fake()->phoneNumber(),
            'is_active' => fake()->boolean(90),
        ];
    }
}
