<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizQuestionFactory extends Factory
{
    protected $model = QuizQuestion::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['multiple_choice', 'true_false', 'short_answer']);

        $options = null;
        $correct_answers = [];

        switch ($type) {
            case 'multiple_choice':
                $options = [
                    $this->faker->word(),
                    $this->faker->word(),
                    $this->faker->word(),
                    $this->faker->word(),
                ];
                $correct_answers = [$options[0]]; // First option is correct
                break;

            case 'true_false':
                $correct_answers = [$this->faker->boolean()];
                break;

            case 'short_answer':
                $correct_answers = [$this->faker->word()];
                break;
        }

        return [
            'quiz_id' => Quiz::factory(),
            'question' => $this->faker->sentence() . '?',
            'type' => $type,
            'options' => $options,
            'correct_answers' => $correct_answers,
            'feedback_correct' => $this->faker->sentence(),
            'feedback_incorrect' => $this->faker->sentence(),
            'points' => $this->faker->numberBetween(1, 10),
            'order' => 0,
        ];
    }

    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'multiple_choice',
            'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
            'correct_answers' => ['Option A'],
        ]);
    }

    public function trueFalse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'true_false',
            'options' => null,
            'correct_answers' => [true],
        ]);
    }

    public function shortAnswer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'short_answer',
            'options' => null,
            'correct_answers' => ['answer'],
        ]);
    }
}
