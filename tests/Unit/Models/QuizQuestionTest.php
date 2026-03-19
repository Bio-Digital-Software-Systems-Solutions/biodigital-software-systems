<?php

namespace Tests\Unit\Models;

use App\Models\QuizQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizQuestionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_correctly_validates_multiple_choice_answer_with_string(): void
    {
        $question = QuizQuestion::factory()->create([
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answers' => ['B'],
        ]);

        $this->assertTrue($question->isCorrectAnswer('B'));
        $this->assertFalse($question->isCorrectAnswer('A'));
        $this->assertFalse($question->isCorrectAnswer('C'));
    }

    /** @test */
    public function single_correct_answer_accepts_single_element_array(): void
    {
        $question = QuizQuestion::factory()->create([
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answers' => ['B'],
        ]);

        $this->assertTrue($question->isCorrectAnswer(['B']));
        $this->assertFalse($question->isCorrectAnswer(['A']));
        $this->assertFalse($question->isCorrectAnswer(['A', 'B']));
    }

    /** @test */
    public function it_correctly_validates_multiple_choice_answer_with_array(): void
    {
        $question = QuizQuestion::factory()->create([
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answers' => ['A', 'C'],
        ]);

        $this->assertTrue($question->isCorrectAnswer(['A', 'C']));
        $this->assertTrue($question->isCorrectAnswer(['C', 'A'])); // Order shouldn't matter
        $this->assertFalse($question->isCorrectAnswer(['A'])); // Missing one
        $this->assertFalse($question->isCorrectAnswer(['A', 'B'])); // Wrong answer included
    }

    /** @test */
    public function multiple_correct_answers_rejects_string_input(): void
    {
        $question = QuizQuestion::factory()->create([
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answers' => ['A', 'C'],
        ]);

        // A single string should NOT be accepted when multiple answers are required
        $this->assertFalse($question->isCorrectAnswer('A'));
        $this->assertFalse($question->isCorrectAnswer('C'));
    }

    /** @test */
    public function multiple_correct_answers_rejects_partial_selection(): void
    {
        $question = QuizQuestion::factory()->create([
            'type' => 'multiple_choice',
            'options' => ['je suis enfant de Dieu', 'je suis élevé', 'je suis MOI', 'je suis juste', 'je suis délivré'],
            'correct_answers' => ['je suis enfant de Dieu', 'je suis élevé', 'je suis juste', 'je suis délivré'],
            'points' => 4,
        ]);

        // Must select ALL 4 correct answers
        $this->assertTrue($question->isCorrectAnswer([
            'je suis enfant de Dieu', 'je suis élevé', 'je suis juste', 'je suis délivré',
        ]));

        // Order doesn't matter
        $this->assertTrue($question->isCorrectAnswer([
            'je suis délivré', 'je suis juste', 'je suis enfant de Dieu', 'je suis élevé',
        ]));

        // Missing one correct answer = 0 points
        $this->assertFalse($question->isCorrectAnswer([
            'je suis enfant de Dieu', 'je suis élevé', 'je suis juste',
        ]));

        // Including one wrong answer = 0 points
        $this->assertFalse($question->isCorrectAnswer([
            'je suis enfant de Dieu', 'je suis élevé', 'je suis MOI', 'je suis juste', 'je suis délivré',
        ]));

        // Only wrong answer
        $this->assertFalse($question->isCorrectAnswer(['je suis MOI']));
    }

    /** @test */
    public function multiple_correct_answers_rejects_superset(): void
    {
        $question = QuizQuestion::factory()->create([
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answers' => ['A', 'C'],
        ]);

        // All options selected including wrong ones = 0 points
        $this->assertFalse($question->isCorrectAnswer(['A', 'B', 'C', 'D']));
        $this->assertFalse($question->isCorrectAnswer(['A', 'C', 'D']));
    }

    /** @test */
    public function it_correctly_validates_true_false_answer(): void
    {
        $questionTrue = QuizQuestion::factory()->create([
            'type' => 'true_false',
            'correct_answers' => [true],
        ]);

        $this->assertTrue($questionTrue->isCorrectAnswer(true));
        $this->assertFalse($questionTrue->isCorrectAnswer(false));

        $questionFalse = QuizQuestion::factory()->create([
            'type' => 'true_false',
            'correct_answers' => [false],
        ]);

        $this->assertTrue($questionFalse->isCorrectAnswer(false));
        $this->assertFalse($questionFalse->isCorrectAnswer(true));
    }

    /** @test */
    public function it_correctly_validates_short_answer_case_insensitive(): void
    {
        $question = QuizQuestion::factory()->create([
            'type' => 'short_answer',
            'correct_answers' => ['Laravel', 'PHP'],
        ]);

        // Case insensitive
        $this->assertTrue($question->isCorrectAnswer('laravel'));
        $this->assertTrue($question->isCorrectAnswer('LARAVEL'));
        $this->assertTrue($question->isCorrectAnswer('php'));
        $this->assertTrue($question->isCorrectAnswer('PHP'));

        // Wrong answer
        $this->assertFalse($question->isCorrectAnswer('Python'));

        // Trims whitespace
        $this->assertTrue($question->isCorrectAnswer('  Laravel  '));
    }

    /** @test */
    public function it_accepts_multiple_correct_answers_for_short_answer(): void
    {
        $question = QuizQuestion::factory()->create([
            'type' => 'short_answer',
            'correct_answers' => ['yes', 'oui', 'si'],
        ]);

        $this->assertTrue($question->isCorrectAnswer('yes'));
        $this->assertTrue($question->isCorrectAnswer('oui'));
        $this->assertTrue($question->isCorrectAnswer('si'));
        $this->assertTrue($question->isCorrectAnswer('YES')); // Case insensitive
        $this->assertFalse($question->isCorrectAnswer('no'));
    }

    /** @test */
    public function it_returns_false_for_invalid_answer_types(): void
    {
        $question = QuizQuestion::factory()->create([
            'type' => 'multiple_choice',
            'correct_answers' => ['A'],
        ]);

        $this->assertFalse($question->isCorrectAnswer(null));
        $this->assertFalse($question->isCorrectAnswer(''));
    }

    /** @test */
    public function it_handles_empty_arrays_for_multiple_choice(): void
    {
        $question = QuizQuestion::factory()->create([
            'type' => 'multiple_choice',
            'correct_answers' => ['A', 'B'],
        ]);

        $this->assertFalse($question->isCorrectAnswer([]));
    }

    /** @test */
    public function has_multiple_correct_answers_returns_true_for_multi_answer_questions(): void
    {
        $multi = QuizQuestion::factory()->create([
            'type' => 'multiple_choice',
            'correct_answers' => ['A', 'C'],
        ]);

        $single = QuizQuestion::factory()->create([
            'type' => 'multiple_choice',
            'correct_answers' => ['A'],
        ]);

        $trueFalse = QuizQuestion::factory()->create([
            'type' => 'true_false',
            'correct_answers' => [true],
        ]);

        $this->assertTrue($multi->hasMultipleCorrectAnswers());
        $this->assertFalse($single->hasMultipleCorrectAnswers());
        $this->assertFalse($trueFalse->hasMultipleCorrectAnswers());
    }

    /** @test */
    public function it_handles_null_answer_for_multi_answer_question(): void
    {
        $question = QuizQuestion::factory()->create([
            'type' => 'multiple_choice',
            'correct_answers' => ['A', 'B'],
        ]);

        $this->assertFalse($question->isCorrectAnswer(null));
    }
}
