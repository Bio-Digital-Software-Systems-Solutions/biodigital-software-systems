<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlockedLoginAttempt>
 */
class BlockedLoginAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->blocked(),
            'email' => fake()->safeEmail(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'acknowledged' => false,
            'acknowledged_by' => null,
            'acknowledged_at' => null,
        ];
    }

    /**
     * Indicate that the attempt has been acknowledged.
     */
    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes): array => [
            'acknowledged' => true,
            'acknowledged_by' => User::factory(),
            'acknowledged_at' => now(),
        ]);
    }
}
