<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\ProgramStep;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramStepFactory extends Factory
{
    protected $model = ProgramStep::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+30 days');
        $duration = fake()->numberBetween(60, 480);
        $endDate = (clone $startDate)->modify("+{$duration} minutes");

        return [
            'program_id' => Program::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'order_index' => fake()->numberBetween(1, 10),
            'start_datetime' => $startDate,
            'end_datetime' => $endDate,
            'duration_minutes' => $duration,
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'in_progress',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
        ]);
    }
}
