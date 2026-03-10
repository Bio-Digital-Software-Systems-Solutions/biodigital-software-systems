<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' Group',
            'description' => fake()->optional()->paragraph(),
            'code' => fake()->unique()->regexify('[A-Z]{2,4}-[0-9]{2,3}'),
            'max_members' => fake()->optional()->numberBetween(5, 50),
            'leader_id' => fake()->optional()->randomElement([null, User::factory()]),
            'is_active' => fake()->boolean(85),
        ];
    }

    /**
     * Indicate that the group is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the group is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the group has a specific leader.
     */
    public function withLeader(User $leader): static
    {
        return $this->state(fn (array $attributes): array => [
            'leader_id' => $leader->id,
        ]);
    }

    /**
     * Indicate that the group has a maximum number of members.
     */
    public function withMaxMembers(int $maxMembers): static
    {
        return $this->state(fn (array $attributes): array => [
            'max_members' => $maxMembers,
        ]);
    }

    /**
     * Indicate that the group has unlimited members.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes): array => [
            'max_members' => null,
        ]);
    }
}
