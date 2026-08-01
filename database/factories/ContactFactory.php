<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
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

    /**
     * Indicate that the contact is being handled.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'in_progress',
            'read_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the contact has been resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'resolved',
            'read_at' => fake()->dateTimeBetween('-1 month', '-1 week'),
        ]);
    }

    /**
     * Indicate that the contact has been closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'closed',
            'read_at' => fake()->dateTimeBetween('-1 month', '-1 week'),
        ]);
    }

    /**
     * Indicate that the contact is assigned to a user.
     */
    public function assigned(?User $user = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'assigned_to' => $user?->id ?? User::factory(),
        ]);
    }
}
