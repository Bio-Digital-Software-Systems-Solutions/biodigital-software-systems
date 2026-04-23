<?php

namespace Database\Factories;

use App\Models\GroupActivity;
use App\Models\User;
use App\Models\Visitor;
use App\Models\VisitorVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VisitorAttendance>
 */
class VisitorAttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'visitor_id' => Visitor::factory(),
            'visitor_visit_id' => VisitorVisit::factory(),
            'attendable_type' => GroupActivity::class,
            'attendable_id' => GroupActivity::factory(),
            'attended_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'status' => fake()->randomElement(['present', 'absent', 'excused', 'late']),
            'notes' => fake()->optional()->sentence(),
            'recorded_by' => User::factory(),
        ];
    }

    public function present(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'present',
        ]);
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'absent',
        ]);
    }
}
