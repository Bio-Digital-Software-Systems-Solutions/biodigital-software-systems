<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizMultipleCorrectAnswersTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected User $teacher;

    protected Training $training;

    protected Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        $this->training = Training::factory()->create([
            'teacher_id' => $this->teacher->id,
        ]);

        $this->quiz = Quiz::factory()->create([
            'training_id' => $this->training->id,
            'is_active' => true,
            'status' => 'published',
            'max_score' => 10,
            'passing_score' => 60,
        ]);
    }

    /** @test */
    public function student_scores_full_points_when_all_correct_answers_selected(): void
    {
        $question = QuizQuestion::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice',
            'options' => ['je suis enfant de Dieu', 'je suis élevé', 'je suis MOI', 'je suis juste', 'je suis délivré'],
            'correct_answers' => ['je suis enfant de Dieu', 'je suis élevé', 'je suis juste', 'je suis délivré'],
            'points' => 4,
        ]);

        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('quiz-attempts.submit', $attempt->uuid), [
                'answers' => [
                    [
                        'question_id' => $question->id,
                        'answer' => ['je suis enfant de Dieu', 'je suis élevé', 'je suis juste', 'je suis délivré'],
                    ],
                ],
            ]);

        $response->assertRedirect();

        $attempt->refresh();
        $this->assertEquals('completed', $attempt->status);
        $this->assertEquals(4, $attempt->score);
        $this->assertTrue($attempt->answers[0]['is_correct']);
        $this->assertEquals(4, $attempt->answers[0]['points_earned']);
    }

    /** @test */
    public function student_scores_zero_when_one_correct_answer_is_missing(): void
    {
        $question = QuizQuestion::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answers' => ['A', 'C', 'D'],
            'points' => 5,
        ]);

        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
        ]);

        // Student selects only A and C, missing D
        $this->actingAs($this->student)
            ->post(route('quiz-attempts.submit', $attempt->uuid), [
                'answers' => [
                    ['question_id' => $question->id, 'answer' => ['A', 'C']],
                ],
            ]);

        $attempt->refresh();
        $this->assertEquals(0, $attempt->score);
        $this->assertFalse($attempt->answers[0]['is_correct']);
        $this->assertEquals(0, $attempt->answers[0]['points_earned']);
    }

    /** @test */
    public function student_scores_zero_when_one_wrong_answer_is_included(): void
    {
        $question = QuizQuestion::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answers' => ['A', 'C'],
            'points' => 5,
        ]);

        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
        ]);

        // Student selects A, B, C — B is wrong
        $this->actingAs($this->student)
            ->post(route('quiz-attempts.submit', $attempt->uuid), [
                'answers' => [
                    ['question_id' => $question->id, 'answer' => ['A', 'B', 'C']],
                ],
            ]);

        $attempt->refresh();
        $this->assertEquals(0, $attempt->score);
        $this->assertFalse($attempt->answers[0]['is_correct']);
    }

    /** @test */
    public function answer_order_does_not_matter_for_multi_answer_questions(): void
    {
        $question = QuizQuestion::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answers' => ['A', 'C'],
            'points' => 5,
        ]);

        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
        ]);

        // Student selects in reverse order
        $this->actingAs($this->student)
            ->post(route('quiz-attempts.submit', $attempt->uuid), [
                'answers' => [
                    ['question_id' => $question->id, 'answer' => ['C', 'A']],
                ],
            ]);

        $attempt->refresh();
        $this->assertEquals(5, $attempt->score);
        $this->assertTrue($attempt->answers[0]['is_correct']);
    }

    /** @test */
    public function mixed_quiz_with_single_and_multi_answer_questions_scores_correctly(): void
    {
        // Single-answer multiple choice (3 pts)
        $q1 = QuizQuestion::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice',
            'options' => ['Paris', 'London', 'Berlin'],
            'correct_answers' => ['Paris'],
            'points' => 3,
            'order' => 0,
        ]);

        // Multi-answer multiple choice (4 pts)
        $q2 = QuizQuestion::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice',
            'options' => ['je suis enfant de Dieu', 'je suis élevé', 'je suis MOI', 'je suis juste', 'je suis délivré'],
            'correct_answers' => ['je suis enfant de Dieu', 'je suis élevé', 'je suis juste', 'je suis délivré'],
            'points' => 4,
            'order' => 1,
        ]);

        // True/false (2 pts)
        $q3 = QuizQuestion::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'true_false',
            'correct_answers' => [true],
            'points' => 2,
            'order' => 2,
        ]);

        // Short answer (1 pt)
        $q4 = QuizQuestion::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'short_answer',
            'correct_answers' => ['Laravel'],
            'points' => 1,
            'order' => 3,
        ]);

        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->student)
            ->post(route('quiz-attempts.submit', $attempt->uuid), [
                'answers' => [
                    ['question_id' => $q1->id, 'answer' => 'Paris'],                    // Correct: +3
                    ['question_id' => $q2->id, 'answer' => ['je suis enfant de Dieu', 'je suis élevé', 'je suis juste']], // Missing one: +0
                    ['question_id' => $q3->id, 'answer' => true],                       // Correct: +2
                    ['question_id' => $q4->id, 'answer' => 'laravel'],                  // Correct (case-insensitive): +1
                ],
            ]);

        $attempt->refresh();
        $this->assertEquals(6, $attempt->score); // 3 + 0 + 2 + 1

        // Verify individual answers
        $answers = collect($attempt->answers);
        $this->assertTrue($answers->firstWhere('question_id', $q1->id)['is_correct']);
        $this->assertFalse($answers->firstWhere('question_id', $q2->id)['is_correct']);
        $this->assertTrue($answers->firstWhere('question_id', $q3->id)['is_correct']);
        $this->assertTrue($answers->firstWhere('question_id', $q4->id)['is_correct']);
    }

    /** @test */
    public function selecting_all_options_for_multi_answer_question_is_wrong(): void
    {
        $question = QuizQuestion::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answers' => ['A', 'C'],
            'points' => 5,
        ]);

        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
        ]);

        // Student selects everything
        $this->actingAs($this->student)
            ->post(route('quiz-attempts.submit', $attempt->uuid), [
                'answers' => [
                    ['question_id' => $question->id, 'answer' => ['A', 'B', 'C', 'D']],
                ],
            ]);

        $attempt->refresh();
        $this->assertEquals(0, $attempt->score);
        $this->assertFalse($attempt->answers[0]['is_correct']);
    }

    /** @test */
    public function null_answer_for_multi_answer_question_scores_zero(): void
    {
        $question = QuizQuestion::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answers' => ['A', 'C'],
            'points' => 5,
        ]);

        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->student)
            ->post(route('quiz-attempts.submit', $attempt->uuid), [
                'answers' => [
                    ['question_id' => $question->id, 'answer' => null],
                ],
            ]);

        $attempt->refresh();
        $this->assertEquals(0, $attempt->score);
        $this->assertFalse($attempt->answers[0]['is_correct']);
    }

    /** @test */
    public function correct_answers_count_is_sent_to_frontend_when_starting_quiz(): void
    {
        QuizQuestion::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answers' => ['A', 'C', 'D'],
            'points' => 5,
        ]);

        QuizQuestion::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice',
            'options' => ['X', 'Y', 'Z'],
            'correct_answers' => ['Y'],
            'points' => 3,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('quizzes.start', $this->quiz->uuid));

        $response->assertStatus(200);

        $questions = $response->viewData('page')['props']['quiz']['questions'];

        // Multi-answer question should report count = 3
        $multiQuestion = collect($questions)->firstWhere('correct_answers_count', 3);
        $this->assertNotNull($multiQuestion);
        $this->assertEquals(3, $multiQuestion['correct_answers_count']);

        // Single-answer question should report count = 1
        $singleQuestion = collect($questions)->firstWhere('correct_answers_count', 1);
        $this->assertNotNull($singleQuestion);
        $this->assertEquals(1, $singleQuestion['correct_answers_count']);

        // Correct answers themselves should NOT be sent
        foreach ($questions as $q) {
            $this->assertArrayNotHasKey('correct_answers', $q);
        }
    }

    /** @test */
    public function teacher_can_create_quiz_with_multi_answer_questions(): void
    {
        $quizData = [
            'title' => 'Quiz Multi-Réponses',
            'description' => 'Test avec questions à réponses multiples',
            'duration_minutes' => 30,
            'passing_score' => 60,
            'is_active' => true,
            'questions' => [
                [
                    'question' => 'Quelle est maintenant votre nouvelle identité en Christ',
                    'type' => 'multiple_choice',
                    'options' => ['je suis enfant de Dieu', 'je suis élevé', 'je suis MOI', 'je suis juste', 'je suis délivré'],
                    'correct_answers' => ['je suis enfant de Dieu', 'je suis élevé', 'je suis juste', 'je suis délivré'],
                    'points' => 4,
                ],
            ],
        ];

        $response = $this->actingAs($this->teacher)
            ->post(route('trainings.quizzes.store', $this->training->uuid), $quizData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $question = QuizQuestion::where('question', 'Quelle est maintenant votre nouvelle identité en Christ')->first();
        $this->assertNotNull($question);
        $this->assertCount(4, $question->correct_answers);
        $this->assertContains('je suis enfant de Dieu', $question->correct_answers);
        $this->assertContains('je suis délivré', $question->correct_answers);
        $this->assertTrue($question->hasMultipleCorrectAnswers());
    }

    /** @test */
    public function feedback_is_stored_correctly_for_multi_answer_questions(): void
    {
        $question = QuizQuestion::factory()->create([
            'quiz_id' => $this->quiz->id,
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answers' => ['A', 'C'],
            'points' => 5,
            'feedback_correct' => 'Bravo, toutes les bonnes réponses!',
            'feedback_incorrect' => 'Il fallait cocher A et C.',
        ]);

        // Test correct feedback
        $attempt1 = QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->student)
            ->post(route('quiz-attempts.submit', $attempt1->uuid), [
                'answers' => [
                    ['question_id' => $question->id, 'answer' => ['A', 'C']],
                ],
            ]);

        $attempt1->refresh();
        $this->assertEquals('Bravo, toutes les bonnes réponses!', $attempt1->answers[0]['feedback']);

        // Test incorrect feedback
        $student2 = User::factory()->create();
        $student2->assignRole('student');

        $attempt2 = QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'student_id' => $student2->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($student2)
            ->post(route('quiz-attempts.submit', $attempt2->uuid), [
                'answers' => [
                    ['question_id' => $question->id, 'answer' => ['A']],
                ],
            ]);

        $attempt2->refresh();
        $this->assertEquals('Il fallait cocher A et C.', $attempt2->answers[0]['feedback']);
    }
}
