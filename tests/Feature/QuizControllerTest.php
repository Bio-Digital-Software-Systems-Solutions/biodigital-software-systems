<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    /** @test */
    public function teacher_can_view_quiz_index()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $training = Training::factory()->create();
        Quiz::factory()->count(3)->create(['training_id' => $training->id]);

        $response = $this->actingAs($teacher)->get(route('trainings.quizzes.index', $training->uuid));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Quiz/Index')
            ->has('quizzes', 3)
        );
    }

    /** @test */
    public function student_cannot_access_quiz_management()
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $training = Training::factory()->create();

        $response = $this->actingAs($student)->get(route('trainings.quizzes.index', $training->uuid));

        $response->assertStatus(403);
    }

    /** @test */
    public function teacher_can_create_quiz_with_questions()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $training = Training::factory()->create();

        $quizData = [
            'title' => 'Test Quiz',
            'description' => 'A test quiz',
            'duration_minutes' => 30,
            'passing_score' => 60,
            'is_active' => true,
            'questions' => [
                [
                    'question' => 'What is 2+2?',
                    'type' => 'multiple_choice',
                    'options' => ['3', '4', '5'],
                    'correct_answers' => ['4'],
                    'points' => 5,
                ],
                [
                    'question' => 'Is PHP a programming language?',
                    'type' => 'true_false',
                    'options' => null,
                    'correct_answers' => [true],
                    'points' => 3,
                ],
            ],
        ];

        $response = $this->actingAs($teacher)
            ->post(route('trainings.quizzes.store', $training->uuid), $quizData);

        $response->assertRedirect(route('trainings.quizzes.index', $training->uuid));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('quizzes', [
            'training_id' => $training->id,
            'title' => 'Test Quiz',
            'max_score' => 8, // 5 + 3
            'passing_score' => 60,
        ]);

        $this->assertDatabaseCount('quiz_questions', 2);
    }

    /** @test */
    public function quiz_creation_validates_required_fields()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $training = Training::factory()->create();

        $response = $this->actingAs($teacher)
            ->post(route('trainings.quizzes.store', $training->uuid), [
                'title' => '', // Missing title
                'duration_minutes' => 30,
                'passing_score' => 60,
                'questions' => [],
            ]);

        $response->assertSessionHasErrors(['title', 'questions']);
    }

    /** @test */
    public function teacher_can_update_quiz_and_questions()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $training = Training::factory()->create();
        $quiz = Quiz::factory()->create(['training_id' => $training->id]);
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);

        $updateData = [
            'title' => 'Updated Quiz',
            'description' => 'Updated description',
            'duration_minutes' => 45,
            'passing_score' => 70,
            'is_active' => false,
            'questions' => [
                [
                    'id' => $question->id,
                    'question' => 'Updated question',
                    'type' => 'multiple_choice',
                    'options' => ['A', 'B', 'C'],
                    'correct_answers' => ['A'],
                    'points' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($teacher)
            ->put(route('trainings.quizzes.update', [$training->uuid, $quiz->uuid]), $updateData);

        $response->assertRedirect(route('trainings.quizzes.index', $training->uuid));

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'title' => 'Updated Quiz',
            'duration_minutes' => 45,
            'passing_score' => 70,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('quiz_questions', [
            'id' => $question->id,
            'question' => 'Updated question',
            'points' => 10,
        ]);
    }

    /** @test */
    public function teacher_can_delete_quiz()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $training = Training::factory()->create();
        $quiz = Quiz::factory()->create(['training_id' => $training->id]);
        QuizQuestion::factory()->count(2)->create(['quiz_id' => $quiz->id]);

        $response = $this->actingAs($teacher)
            ->delete(route('trainings.quizzes.destroy', [$training->uuid, $quiz->uuid]));

        $response->assertRedirect(route('trainings.quizzes.index', $training->uuid));

        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
        $this->assertDatabaseMissing('quiz_questions', ['quiz_id' => $quiz->id]);
    }

    /** @test */
    public function student_can_start_active_quiz()
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $training = Training::factory()->create();
        $quiz = Quiz::factory()->create([
            'training_id' => $training->id,
            'is_active' => true,
        ]);
        QuizQuestion::factory()->count(3)->create(['quiz_id' => $quiz->id]);

        $response = $this->actingAs($student)
            ->get(route('quizzes.start', $quiz->uuid));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Quiz/Take')
            ->has('quiz.questions', 3)
            ->has('attempt')
        );

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'status' => 'in_progress',
        ]);
    }

    /** @test */
    public function student_cannot_start_inactive_quiz()
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $training = Training::factory()->create();
        $quiz = Quiz::factory()->create([
            'training_id' => $training->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($student)
            ->get(route('quizzes.start', $quiz->uuid));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
        ]);
    }

    /** @test */
    public function student_cannot_retake_completed_quiz()
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $training = Training::factory()->create();
        $quiz = Quiz::factory()->create([
            'training_id' => $training->id,
            'is_active' => true,
        ]);

        QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($student)
            ->get(route('quizzes.start', $quiz->uuid));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Vous avez déjà complété ce quiz.');
    }

    /** @test */
    public function student_can_submit_quiz_and_score_is_calculated_correctly()
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $training = Training::factory()->create();
        $quiz = Quiz::factory()->create([
            'training_id' => $training->id,
            'max_score' => 10,
            'passing_score' => 6,
        ]);

        $question1 = QuizQuestion::factory()->create([
            'quiz_id' => $quiz->id,
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C'],
            'correct_answers' => ['B'],
            'points' => 5,
        ]);

        $question2 = QuizQuestion::factory()->create([
            'quiz_id' => $quiz->id,
            'type' => 'true_false',
            'correct_answers' => [true],
            'points' => 5,
        ]);

        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'status' => 'in_progress',
        ]);

        $answers = [
            ['question_id' => $question1->id, 'answer' => 'B'], // Correct
            ['question_id' => $question2->id, 'answer' => false], // Incorrect
        ];

        $response = $this->actingAs($student)
            ->post(route('quiz-attempts.submit', $attempt->uuid), ['answers' => $answers]);

        $response->assertRedirect(route('trainings.show', $training->uuid));

        $attempt->refresh();
        $this->assertEquals('completed', $attempt->status);
        $this->assertEquals(5, $attempt->score); // Only question1 correct
        $this->assertNotNull($attempt->completed_at);
    }

    /** @test */
    public function student_cannot_submit_another_students_attempt()
    {
        $student1 = User::factory()->create();
        $student1->assignRole('student');
        $student2 = User::factory()->create();
        $student2->assignRole('student');

        $training = Training::factory()->create();
        $quiz = Quiz::factory()->create(['training_id' => $training->id]);

        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $student1->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($student2)
            ->post(route('quiz-attempts.submit', $attempt->uuid), ['answers' => []]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Tentative invalide.');
    }

    /** @test */
    public function teacher_can_view_quiz_results()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $student = User::factory()->create();
        $student->assignRole('student');

        $training = Training::factory()->create();
        $quiz = Quiz::factory()->create(['training_id' => $training->id]);

        QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'status' => 'completed',
            'score' => 80,
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('trainings.quizzes.results', [$training->uuid, $quiz->uuid]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Quiz/Results')
            ->has('attempts', 1)
            ->has('statistics')
        );
    }

    /** @test */
    public function teacher_can_export_quiz_results_as_csv()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $student = User::factory()->create();
        $student->assignRole('student');

        $training = Training::factory()->create();
        $quiz = Quiz::factory()->create(['training_id' => $training->id, 'title' => 'Test Quiz']);

        QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'status' => 'completed',
            'score' => 80,
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('trainings.quizzes.export-csv', [$training->uuid, $quiz->uuid]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('quiz_results_Test Quiz', $response->headers->get('content-disposition'));
    }

    /** @test */
    public function correct_answers_are_not_sent_to_frontend_when_taking_quiz()
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $training = Training::factory()->create();
        $quiz = Quiz::factory()->create([
            'training_id' => $training->id,
            'is_active' => true,
        ]);

        $question = QuizQuestion::factory()->create([
            'quiz_id' => $quiz->id,
            'correct_answers' => ['secret answer'],
        ]);

        $response = $this->actingAs($student)
            ->get(route('quizzes.start', $quiz->uuid));

        $response->assertStatus(200);

        // Check that correct_answers is not in the response
        $responseData = $response->viewData('page')['props'];
        $questions = $responseData['quiz']['questions'];

        foreach ($questions as $q) {
            $this->assertArrayNotHasKey('correct_answers', $q);
        }
    }
}
