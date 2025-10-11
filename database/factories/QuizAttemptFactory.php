<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-2 weeks', 'now');
        $completedAt = fake()->optional(0.7)->dateTimeBetween($startedAt, 'now');
        $isCompleted = $completedAt !== null;

        return [
            'quiz_id' => Quiz::factory(),
            'student_id' => User::factory(),
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'time_remaining_seconds' => $isCompleted ? 0 : fake()->numberBetween(0, 1800),
            'score' => $isCompleted ? fake()->numberBetween(0, 100) : null,
            'status' => $isCompleted ? 'completed' : 'in_progress',
            'answers' => $isCompleted ? [] : null,
        ];
    }

    /**
     * Indicate that the quiz attempt is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => now(),
            'time_remaining_seconds' => 0,
            'score' => fake()->numberBetween(0, 100),
            'status' => 'completed',
            'answers' => [],
        ]);
    }

    /**
     * Indicate that the quiz attempt is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => null,
            'time_remaining_seconds' => fake()->numberBetween(300, 1800),
            'score' => null,
            'status' => 'in_progress',
            'answers' => null,
        ]);
    }
}
