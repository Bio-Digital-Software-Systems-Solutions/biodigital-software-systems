<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    /** @test */
    public function admin_has_all_quiz_permissions()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($admin->can('view quizzes'));
        $this->assertTrue($admin->can('create quizzes'));
        $this->assertTrue($admin->can('edit quizzes'));
        $this->assertTrue($admin->can('delete quizzes'));
        $this->assertTrue($admin->can('manage quizzes'));
        $this->assertTrue($admin->can('take quizzes'));
        $this->assertTrue($admin->can('grade quizzes'));
    }

    /** @test */
    public function teacher_has_management_permissions()
    {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $this->assertTrue($teacher->can('create quizzes'));
        $this->assertTrue($teacher->can('edit quizzes'));
        $this->assertTrue($teacher->can('delete quizzes'));
        $this->assertTrue($teacher->can('manage quizzes'));
        $this->assertTrue($teacher->can('grade quizzes'));
    }

    /** @test */
    public function student_can_only_take_and_view_quizzes()
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $this->assertTrue($student->can('view quizzes'));
        $this->assertTrue($student->can('take quizzes'));

        $this->assertFalse($student->can('create quizzes'));
        $this->assertFalse($student->can('edit quizzes'));
        $this->assertFalse($student->can('delete quizzes'));
        $this->assertFalse($student->can('manage quizzes'));
        $this->assertFalse($student->can('grade quizzes'));
    }

    /** @test */
    public function student_cannot_access_quiz_management_pages()
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $training = Training::factory()->create();

        // Cannot access index
        $response = $this->actingAs($student)
            ->get(route('trainings.quizzes.index', $training->uuid));
        $response->assertStatus(403);

        // Cannot access create page
        $response = $this->actingAs($student)
            ->get(route('trainings.quizzes.create', $training->uuid));
        $response->assertStatus(403);
    }

    /** @test */
    public function student_cannot_create_or_modify_quizzes()
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $training = Training::factory()->create();

        // Cannot create quiz
        $response = $this->actingAs($student)
            ->post(route('trainings.quizzes.store', $training->uuid), [
                'title' => 'Unauthorized Quiz',
                'duration_minutes' => 30,
                'passing_score' => 60,
                'questions' => [],
            ]);
        $response->assertStatus(403);

        // Cannot delete quiz
        $quiz = Quiz::factory()->create(['training_id' => $training->id]);
        $response = $this->actingAs($student)
            ->delete(route('trainings.quizzes.destroy', [$training->uuid, $quiz->uuid]));
        $response->assertStatus(403);
    }

    /** @test */
    public function student_cannot_view_other_students_results()
    {
        $student1 = User::factory()->create();
        $student1->assignRole('student');
        $student2 = User::factory()->create();
        $student2->assignRole('student');

        $training = Training::factory()->create();
        $quiz = Quiz::factory()->create(['training_id' => $training->id]);

        QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $student2->id,
            'status' => 'completed',
        ]);

        // Student1 cannot view results page
        $response = $this->actingAs($student1)
            ->get(route('trainings.quizzes.results', [$training->uuid, $quiz->uuid]));
        $response->assertStatus(403);
    }

    /** @test */
    public function teacher_can_view_all_student_results()
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
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('trainings.quizzes.results', [$training->uuid, $quiz->uuid]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Quiz/Results'));
    }

    /** @test */
    public function only_authorized_users_can_export_quiz_results()
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $training = Training::factory()->create();
        $quiz = Quiz::factory()->create(['training_id' => $training->id]);

        // Student cannot export
        $response = $this->actingAs($student)
            ->get(route('trainings.quizzes.export-csv', [$training->uuid, $quiz->uuid]));
        $response->assertStatus(403);

        // Teacher can export
        $response = $this->actingAs($teacher)
            ->get(route('trainings.quizzes.export-csv', [$training->uuid, $quiz->uuid]));
        $response->assertStatus(200);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_quiz_features()
    {
        $training = Training::factory()->create();
        $quiz = Quiz::factory()->create(['training_id' => $training->id]);

        // Cannot access quiz management
        $response = $this->get(route('trainings.quizzes.index', $training->uuid));
        $response->assertRedirect(route('login'));

        // Cannot take quiz
        $response = $this->get(route('quizzes.start', $quiz->uuid));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function class_coordinator_has_management_permissions()
    {
        $coordinator = User::factory()->create();
        $coordinator->assignRole('class_coordinator');

        $this->assertTrue($coordinator->can('manage quizzes'));
        $this->assertTrue($coordinator->can('grade quizzes'));
    }
}
