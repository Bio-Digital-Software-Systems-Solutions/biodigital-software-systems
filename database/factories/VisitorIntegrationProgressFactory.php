<?php

namespace Database\Factories;

use App\Models\IntegrationPathwayStep;
use App\Models\VisitorVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VisitorIntegrationProgress>
 */
class VisitorIntegrationProgressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'visitor_visit_id' => VisitorVisit::factory(),
            'step_id' => IntegrationPathwayStep::factory(),
            'status' => 'pending',
            'progress_value' => 0,
            'completed_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
            'progress_value' => 100,
            'completed_at' => now(),
        ]);
    }

    public function inProgress(float $value = 50): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'in_progress',
            'progress_value' => $value,
        ]);
    }
}
