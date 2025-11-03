<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAttemptsTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $teacher;
    protected Training $training;
    protected TrainingClass $trainingClass;

    protected function setUp(): void
    {
        parent::setUp();

        // Run permission seeder to create roles and permissions
        $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);

        // Create teacher
        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        // Create student
        $this->student = User::factory()->create();
        $this->student->assignRole('student');
        $this->student->givePermissionTo('view trainings');

        // Create training
        $this->training = Training::factory()->create([
            'teacher_id' => $this->teacher->id,
        ]);

        // Create training class
        $this->trainingClass = TrainingClass::factory()->create([
            'training_id' => $this->training->id,
            'teacher_id' => $this->teacher->id,
        ]);

        // Enroll student
        $this->training->students()->attach($this->student->id, [
            'training_class_id' => $this->trainingClass->id,
            'status' => 'approved',
            'enrolled_at' => now(),
        ]);
    }

    /** @test */
    public function student_only_sees_active_and_published_quizzes()
    {
        // Create active + published quiz
        $publishedQuiz = Quiz::factory()->create([
            'training_id' => $this->training->id,
            'title' => 'Published Quiz',
            'is_active' => true,
            'status' => 'published',
        ]);

        // Create inactive quiz
        $inactiveQuiz = Quiz::factory()->create([
            'training_id' => $this->training->id,
            'title' => 'Inactive Quiz',
            'is_active' => false,
            'status' => 'published',
        ]);

        // Create draft quiz
        $draftQuiz = Quiz::factory()->create([
            'training_id' => $this->training->id,
            'title' => 'Draft Quiz',
            'is_active' => true,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.dashboard'));

        $response->assertStatus(200);

        $trainings = $response->viewData('page')['props']['trainings'];
        $quizzes = $trainings[0]['quizzes'];

        // Only published and active quiz should be visible
        $this->assertCount(1, $quizzes);
        $this->assertEquals('Published Quiz', $quizzes[0]['title']);
    }

    /** @test */
    public function student_can_start_quiz_with_no_attempts()
    {
        $quiz = Quiz::factory()->create([
            'training_id' => $this->training->id,
            'is_active' => true,
            'status' => 'published',
            'max_attempts' => 3,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.dashboard'));

        $response->assertStatus(200);

        $trainings = $response->viewData('page')['props']['trainings'];
        $quizzes = $trainings[0]['quizzes'];
        $quizData = $quizzes[0];

        $this->assertEquals(0, $quizData['attempts_count']);
        $this->assertEquals(3, $quizData['max_attempts']);
        $this->assertTrue($quizData['can_retake']);
    }

    /** @test */
    public function student_can_retake_quiz_if_attempts_remaining()
    {
        $quiz = Quiz::factory()->create([
            'training_id' => $this->training->id,
            'is_active' => true,
            'status' => 'published',
            'max_attempts' => 3,
        ]);

        // Create 2 completed attempts
        QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'status' => 'completed',
            'score' => 50,
        ]);

        QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'status' => 'completed',
            'score' => 60,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.dashboard'));

        $response->assertStatus(200);

        $trainings = $response->viewData('page')['props']['trainings'];
        $quizzes = $trainings[0]['quizzes'];
        $quizData = $quizzes[0];

        $this->assertEquals(2, $quizData['attempts_count']);
        $this->assertEquals(3, $quizData['max_attempts']);
        $this->assertTrue($quizData['can_retake']);
    }

    /** @test */
    public function student_cannot_retake_quiz_when_max_attempts_reached()
    {
        $quiz = Quiz::factory()->create([
            'training_id' => $this->training->id,
            'is_active' => true,
            'status' => 'published',
            'max_attempts' => 2,
        ]);

        // Create 2 completed attempts (max reached)
        QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'status' => 'completed',
            'score' => 50,
        ]);

        QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'status' => 'completed',
            'score' => 60,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.dashboard'));

        $response->assertStatus(200);

        $trainings = $response->viewData('page')['props']['trainings'];
        $quizzes = $trainings[0]['quizzes'];
        $quizData = $quizzes[0];

        $this->assertEquals(2, $quizData['attempts_count']);
        $this->assertEquals(2, $quizData['max_attempts']);
        $this->assertFalse($quizData['can_retake']);
    }

    /** @test */
    public function student_sees_latest_attempt_details()
    {
        $quiz = Quiz::factory()->create([
            'training_id' => $this->training->id,
            'is_active' => true,
            'status' => 'published',
            'max_attempts' => 5,
        ]);

        // Create older attempt
        QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'status' => 'completed',
            'score' => 50,
            'completed_at' => now()->subDays(2),
        ]);

        // Create latest attempt
        $latestAttempt = QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'status' => 'completed',
            'score' => 75,
            'completed_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.dashboard'));

        $response->assertStatus(200);

        $trainings = $response->viewData('page')['props']['trainings'];
        $quizzes = $trainings[0]['quizzes'];
        $quizData = $quizzes[0];

        $this->assertEquals(2, $quizData['attempts_count']);
        $this->assertEquals(75, $quizData['attempt']['score']);
        $this->assertEquals('completed', $quizData['attempt']['status']);
    }

    /** @test */
    public function quiz_with_unlimited_attempts_allows_retaking()
    {
        $quiz = Quiz::factory()->create([
            'training_id' => $this->training->id,
            'is_active' => true,
            'status' => 'published',
            'max_attempts' => 999, // Essentially unlimited
        ]);

        // Create 10 attempts
        for ($i = 0; $i < 10; $i++) {
            QuizAttempt::factory()->create([
                'quiz_id' => $quiz->id,
                'student_id' => $this->student->id,
                'status' => 'completed',
                'score' => 50 + $i,
            ]);
        }

        $response = $this->actingAs($this->student)
            ->get(route('student.dashboard'));

        $response->assertStatus(200);

        $trainings = $response->viewData('page')['props']['trainings'];
        $quizzes = $trainings[0]['quizzes'];
        $quizData = $quizzes[0];

        $this->assertEquals(10, $quizData['attempts_count']);
        $this->assertEquals(999, $quizData['max_attempts']);
        $this->assertTrue($quizData['can_retake']);
    }

    /** @test */
    public function in_progress_attempts_are_not_counted()
    {
        $quiz = Quiz::factory()->create([
            'training_id' => $this->training->id,
            'is_active' => true,
            'status' => 'published',
            'max_attempts' => 3,
        ]);

        // Create 1 completed attempt
        QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'status' => 'completed',
            'score' => 75,
        ]);

        // Create 1 in-progress attempt (should not be counted)
        QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
            'score' => null,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.dashboard'));

        $response->assertStatus(200);

        $trainings = $response->viewData('page')['props']['trainings'];
        $quizzes = $trainings[0]['quizzes'];
        $quizData = $quizzes[0];

        // Only completed attempts should be counted
        $this->assertEquals(1, $quizData['attempts_count']);
        $this->assertTrue($quizData['can_retake']);
    }
}
