<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherDashboardEvaluationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $student;
    protected Training $training;
    protected Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        // Run permission seeder
        $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);

        // Create teacher
        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');
        $this->teacher->givePermissionTo('manage trainings');

        // Create student
        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        // Create training
        $this->training = Training::factory()->create([
            'teacher_id' => $this->teacher->id,
        ]);

        // Create quiz
        $this->quiz = Quiz::factory()->create([
            'training_id' => $this->training->id,
            'is_active' => true,
            'status' => 'published',
            'max_score' => 100,
            'passing_score' => 60,
            'max_attempts' => 3,
        ]);
    }

    /** @test */
    public function teacher_can_view_evaluations_results(): void
    {
        // Create quiz attempts
        QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'student_id' => $this->student->id,
            'status' => 'completed',
            'score' => 75,
        ]);

        QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'student_id' => $this->student->id,
            'status' => 'completed',
            'score' => 85,
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.dashboard'));

        $response->assertStatus(200);

        $evaluations = $response->viewData('page')['props']['evaluations'];

        $this->assertNotEmpty($evaluations);
        $this->assertEquals($this->quiz->id, $evaluations[0]['id']);
        $this->assertEquals(2, $evaluations[0]['total_attempts']);
    }

    /** @test */
    public function evaluation_statistics_are_calculated_correctly(): void
    {
        // Create 3 passing attempts
        for ($i = 0; $i < 3; $i++) {
            QuizAttempt::factory()->create([
                'quiz_id' => $this->quiz->id,
                'student_id' => User::factory()->create()->id,
                'status' => 'completed',
                'score' => 70, // Passing score
            ]);
        }

        // Create 2 failing attempts
        for ($i = 0; $i < 2; $i++) {
            QuizAttempt::factory()->create([
                'quiz_id' => $this->quiz->id,
                'student_id' => User::factory()->create()->id,
                'status' => 'completed',
                'score' => 50, // Failing score
            ]);
        }

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.dashboard'));

        $response->assertStatus(200);

        $evaluations = $response->viewData('page')['props']['evaluations'];

        $this->assertEquals(5, $evaluations[0]['total_attempts']);
        $this->assertEquals(3, $evaluations[0]['passed_count']);
        $this->assertEquals(2, $evaluations[0]['failed_count']);
        $this->assertEquals(60.0, $evaluations[0]['pass_rate']); // 3/5 * 100
        $this->assertEquals(62.0, $evaluations[0]['average_score']); // (70*3 + 50*2) / 5
    }

    /** @test */
    public function only_completed_attempts_are_counted_in_statistics(): void
    {
        // Completed attempt
        QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'student_id' => $this->student->id,
            'status' => 'completed',
            'score' => 75,
        ]);

        // In-progress attempt (should not be counted)
        QuizAttempt::factory()->create([
            'quiz_id' => $this->quiz->id,
            'student_id' => User::factory()->create()->id,
            'status' => 'in_progress',
            'score' => null,
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.dashboard'));

        $response->assertStatus(200);

        $evaluations = $response->viewData('page')['props']['evaluations'];

        $this->assertEquals(1, $evaluations[0]['total_attempts']);
    }

    /** @test */
    public function teacher_only_sees_their_own_quiz_evaluations(): void
    {
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');
        $otherTeacher->givePermissionTo('manage trainings');

        $otherTraining = Training::factory()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        $otherQuiz = Quiz::factory()->create([
            'training_id' => $otherTraining->id,
            'is_active' => true,
            'status' => 'published',
        ]);

        QuizAttempt::factory()->create([
            'quiz_id' => $otherQuiz->id,
            'student_id' => $this->student->id,
            'status' => 'completed',
            'score' => 80,
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.dashboard'));

        $response->assertStatus(200);

        $evaluations = $response->viewData('page')['props']['evaluations'];

        // Should have quiz from teacher's training but not from other teacher
        $quizIds = array_column($evaluations, 'id');
        $this->assertContains($this->quiz->id, $quizIds);
        $this->assertNotContains($otherQuiz->id, $quizIds);
    }

    /** @test */
    public function evaluation_with_no_attempts_shows_zero_statistics(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.dashboard'));

        $response->assertStatus(200);

        $evaluations = $response->viewData('page')['props']['evaluations'];

        $this->assertEquals(0, $evaluations[0]['total_attempts']);
        $this->assertEquals(0, $evaluations[0]['average_score']);
        $this->assertEquals(0, $evaluations[0]['passed_count']);
        $this->assertEquals(0, $evaluations[0]['failed_count']);
        $this->assertEquals(0, $evaluations[0]['pass_rate']);
    }
}
