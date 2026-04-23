<?php

namespace Database\Factories;

use App\Models\IntegrationPathwayTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IntegrationPathwayStep>
 */
class IntegrationPathwayStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'template_id' => IntegrationPathwayTemplate::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'order_index' => fake()->numberBetween(0, 10),
            'type' => fake()->randomElement([
                'attendance_count',
                'activity_participation',
                'meeting_attendance',
                'training_completion',
                'manual_approval',
                'custom',
            ]),
            'criteria' => ['min_attendance' => fake()->numberBetween(3, 10), 'period_weeks' => fake()->numberBetween(4, 12)],
            'weight' => fake()->numberBetween(1, 10),
            'is_required' => true,
        ];
    }

    public function attendanceCount(int $minAttendance = 4, int $periodWeeks = 8): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'attendance_count',
            'criteria' => ['min_attendance' => $minAttendance, 'period_weeks' => $periodWeeks],
        ]);
    }

    public function manualApproval(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'manual_approval',
            'criteria' => null,
        ]);
    }

    public function optional(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_required' => false,
        ]);
    }
}
