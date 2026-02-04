<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Interest>
 */
class InterestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $interests = [
            'Reading', 'Music', 'Sports', 'Travel', 'Photography', 'Cooking',
            'Gaming', 'Art', 'Hiking', 'Swimming', 'Cycling', 'Gardening',
            'Writing', 'Yoga', 'Meditation', 'Dancing', 'Movies', 'Theater',
            'Volunteering', 'Technology', 'Science', 'History', 'Languages',
            'Fashion', 'Food', 'Nature', 'Animals', 'Environment',
        ];

        return [
            'name' => fake()->unique()->randomElement($interests),
            'icon' => null,
        ];
    }
}
