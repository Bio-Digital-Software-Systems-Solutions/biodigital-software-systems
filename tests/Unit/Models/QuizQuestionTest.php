<?php

namespace Tests\Unit\Models;

use App\Models\QuizQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizQuestionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_correctly_validates_multiple_choice_answer_with_string()
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
    public function it_correctly_validates_multiple_choice_answer_with_array()
    {
        $question = QuizQuestion::factory()->create([
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answers' => ['A', 'C'],
        ]);

        $this->assertTrue($question->isCorrectAnswer(['A', 'C']));
        $this->assertTrue($question->isCorrectAnswer(['C', 'A'])); // Order shouldn't matter
        $this->assertFalse($question->isCorrectAnswer(['A'])); // Missing one
        $this->assertFalse($question->isCorrectAnswer(['A', 'B'])); // Wrong answer
    }

    /** @test */
    public function it_correctly_validates_true_false_answer()
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
    public function it_correctly_validates_short_answer_case_insensitive()
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
    public function it_accepts_multiple_correct_answers_for_short_answer()
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
    public function it_returns_false_for_invalid_answer_types()
    {
        $question = QuizQuestion::factory()->create([
            'type' => 'multiple_choice',
            'correct_answers' => ['A'],
        ]);

        $this->assertFalse($question->isCorrectAnswer(null));
        $this->assertFalse($question->isCorrectAnswer(''));
    }

    /** @test */
    public function it_handles_empty_arrays_for_multiple_choice()
    {
        $question = QuizQuestion::factory()->create([
            'type' => 'multiple_choice',
            'correct_answers' => ['A', 'B'],
        ]);

        $this->assertFalse($question->isCorrectAnswer([]));
    }
}
