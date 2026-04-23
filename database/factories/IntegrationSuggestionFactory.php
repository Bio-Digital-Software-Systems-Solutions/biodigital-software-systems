<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VisitorVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IntegrationSuggestion>
 */
class IntegrationSuggestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'visitor_visit_id' => VisitorVisit::factory(),
            'suggested_to' => User::factory(),
            'score_at_suggestion' => fake()->randomFloat(2, 80, 100),
            'status' => 'pending',
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'accepted',
            'responded_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'rejected',
            'responded_at' => now(),
            'response_notes' => fake()->sentence(),
        ]);
    }
}
