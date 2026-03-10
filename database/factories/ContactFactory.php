<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'subject' => fake()->sentence(),
            'message' => fake()->paragraphs(3, true),
            'status' => fake()->randomElement(['new', 'in_progress', 'resolved', 'closed']),
            'assigned_to' => null,
            'read_at' => null,
        ];
    }

    /**
     * Indicate that the contact is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'new',
            'read_at' => null,
        ]);
    }

    /**
     * Indicate that the contact has been read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes): array => [
            'read_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
