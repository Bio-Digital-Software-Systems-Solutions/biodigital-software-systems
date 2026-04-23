<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VisitorVisit>
 */
class VisitorVisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'visitor_id' => Visitor::factory(),
            'visitable_type' => Group::class,
            'visitable_id' => Group::factory(),
            'first_visited_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'integration_score' => 0,
            'integration_status' => 'visiting',
            'notes' => fake()->optional()->sentence(),
            'invited_by' => fake()->optional()->randomElement([null, User::factory()]),
        ];
    }

    public function forGroup(Group $group): static
    {
        return $this->state(fn (array $attributes): array => [
            'visitable_type' => Group::class,
            'visitable_id' => $group->id,
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes): array => [
            'integration_score' => fake()->randomFloat(2, 80, 100),
            'integration_status' => 'ready',
        ]);
    }

    public function progressing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'integration_score' => fake()->randomFloat(2, 25, 79),
            'integration_status' => 'progressing',
        ]);
    }
}
