<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\TrainingClassMaterial;
use App\Models\TrainingMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuizClassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected User $student;

    protected Training $training;

    protected TrainingClass $trainingClass1;

    protected TrainingClass $trainingClass2;

    protected TrainingClassMaterial $material1;

    protected TrainingClassMaterial $material2;

    protected Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'manage trainings']);
        Permission::create(['name' => 'manage quizzes']);
        Permission::create(['name' => 'create quizzes']);
        Permission::create(['name' => 'edit quizzes']);

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(['manage trainings', 'manage quizzes', 'create quizzes', 'edit quizzes']);

        $teacherRole = Role::create(['name' => 'teacher']);
        $teacherRole->givePermissionTo(['manage quizzes', 'create quizzes', 'edit quizzes']);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        $this->student = User::factory()->create();

        // Create training
        $this->training = Training::factory()->create([
            'title' => 'Test Training',
            'teacher_id' => $this->teacher->id,
        ]);

        // Create training classes
        $this->trainingClass1 = TrainingClass::factory()->create([
            'training_id' => $this->training->id,
            'name' => 'Class 1',
            'date' => now()->addDays(1),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $this->trainingClass2 = TrainingClass::factory()->create([
            'training_id' => $this->training->id,
            'name' => 'Class 2',
            'date' => now()->addDays(2),
            'start_time' => '14:00:00',
            'end_time' => '17:00:00',
        ]);

        // Create the underlying TrainingMaterials (content) and attach each
        // to its class through the pivot.
        $content1 = TrainingMaterial::factory()->create([
            'training_id' => $this->training->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Material 1',
            'type' => 'pdf',
        ]);
        $content2 = TrainingMaterial::factory()->create([
            'training_id' => $this->training->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Material 2',
            'type' => 'video',
        ]);

        $this->material1 = TrainingClassMaterial::create([
            'training_class_id' => $this->trainingClass1->id,
            'training_material_id' => $content1->id,
            'teacher_id' => $this->teacher->id,
            'is_active' => true,
        ]);

        $this->material2 = TrainingClassMaterial::create([
            'training_class_id' => $this->trainingClass2->id,
            'training_material_id' => $content2->id,
            'teacher_id' => $this->teacher->id,
            'is_active' => true,
        ]);

        // Create quiz
        $this->quiz = Quiz::factory()->create([
            'training_id' => $this->training->id,
            'title' => 'Test Quiz',
            'duration_minutes' => 30,
            'max_score' => 100,
            'passing_score' => 60,
        ]);
    }

    public function test_quiz_can_be_assigned_to_training_class(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('trainings.quizzes.assign-to-class', [
                $this->training->uuid,
                $this->quiz->uuid,
                $this->trainingClass1->uuid,
            ]), [
                'available_from' => now()->format('Y-m-d H:i:s'),
                'available_until' => now()->addDays(7)->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('quiz_training_class', [
            'quiz_id' => $this->quiz->id,
            'training_class_id' => $this->trainingClass1->id,
            'is_active' => true,
        ]);

        // Test relationship
        $this->assertTrue($this->quiz->trainingClasses->contains($this->trainingClass1));
        $this->assertTrue($this->trainingClass1->quizzes->contains($this->quiz));
    }

    public function test_quiz_cannot_be_assigned_twice_to_same_class(): void
    {
        // First assignment
        $this->quiz->trainingClasses()->attach($this->trainingClass1->id, [
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        // Second assignment should fail
        $response = $this->actingAs($this->admin)
            ->post(route('trainings.quizzes.assign-to-class', [
                $this->training->uuid,
                $this->quiz->uuid,
                $this->trainingClass1->uuid,
            ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_quiz_can_be_removed_from_training_class(): void
    {
        // First assign
        $this->quiz->trainingClasses()->attach($this->trainingClass1->id, [
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        // Then remove
        $response = $this->actingAs($this->admin)
            ->delete(route('trainings.quizzes.remove-from-class', [
                $this->training->uuid,
                $this->quiz->uuid,
                $this->trainingClass1->uuid,
            ]));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('quiz_training_class', [
            'quiz_id' => $this->quiz->id,
            'training_class_id' => $this->trainingClass1->id,
        ]);
    }

    public function test_quiz_can_be_assigned_to_training_class_material(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('trainings.quizzes.assign-to-material', [
                $this->training->uuid,
                $this->quiz->uuid,
                $this->material1->uuid,
            ]), [
                'order' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('quiz_training_class_material', [
            'quiz_id' => $this->quiz->id,
            'training_class_material_id' => $this->material1->id,
            'is_active' => true,
            'order' => 1,
        ]);

        // Test relationship
        $this->assertTrue($this->quiz->trainingClassMaterials->contains($this->material1));
        $this->assertTrue($this->material1->quizzes->contains($this->quiz));
    }

    public function test_quiz_assignment_assignment_dates_are_validated(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('trainings.quizzes.assign-to-class', [
                $this->training->uuid,
                $this->quiz->uuid,
                $this->trainingClass1->uuid,
            ]), [
                'available_from' => now()->addDays(7)->format('Y-m-d H:i:s'),
                'available_until' => now()->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(422);
    }

    public function test_bulk_assign_quiz_to_multiple_classes(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('trainings.quizzes.bulk-assign-classes', [
                $this->training->uuid,
                $this->quiz->uuid,
            ]), [
                'class_ids' => [$this->trainingClass1->id, $this->trainingClass2->id],
                'available_from' => now()->format('Y-m-d H:i:s'),
                'available_until' => now()->addDays(7)->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'assigned_count' => 2,
            ]);

        $this->assertDatabaseHas('quiz_training_class', [
            'quiz_id' => $this->quiz->id,
            'training_class_id' => $this->trainingClass1->id,
        ]);

        $this->assertDatabaseHas('quiz_training_class', [
            'quiz_id' => $this->quiz->id,
            'training_class_id' => $this->trainingClass2->id,
        ]);
    }

    public function test_quiz_class_assignment_page_loads_correctly(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('trainings.quizzes.class-assignments', [
                $this->training->uuid,
                $this->quiz->uuid,
            ]));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Quiz/ClassAssignments')
                ->has('quiz')
                ->has('availableClasses')
            );
    }

    public function test_quiz_model_helper_methods(): void
    {
        // Test isAvailableForClass
        $this->assertFalse($this->quiz->isAvailableForClass($this->trainingClass1));

        $this->quiz->trainingClasses()->attach($this->trainingClass1->id, [
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $this->assertTrue($this->quiz->isAvailableForClass($this->trainingClass1));

        // Test isAvailableForMaterial
        $this->assertFalse($this->quiz->isAvailableForMaterial($this->material1));

        $this->quiz->trainingClassMaterials()->attach($this->material1->id, [
            'assigned_at' => now(),
            'is_active' => true,
            'order' => 0,
        ]);

        $this->assertTrue($this->quiz->isAvailableForMaterial($this->material1));
    }

    public function test_training_class_model_helper_methods(): void
    {
        // Test hasQuiz
        $this->assertFalse($this->trainingClass1->hasQuiz($this->quiz));

        $this->quiz->trainingClasses()->attach($this->trainingClass1->id, [
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $this->assertTrue($this->trainingClass1->hasQuiz($this->quiz));
    }

    public function test_quiz_creation_with_class_assignments(): void
    {
        $quizData = [
            'title' => 'New Test Quiz',
            'description' => 'A quiz for testing',
            'duration_minutes' => 45,
            'passing_score' => 70,
            'max_attempts' => 3,
            'score_display' => 'best',
            'status' => 'published',
            'is_active' => true,
            'questions' => [
                [
                    'question' => 'What is 2+2?',
                    'type' => 'multiple_choice',
                    'options' => ['3', '4', '5'],
                    'correct_answers' => [1], // Index of correct answer
                    'points' => 10,
                ],
            ],
            'assigned_classes' => [$this->trainingClass1->id, $this->trainingClass2->id],
            'assigned_materials' => [$this->material1->id],
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('trainings.quizzes.store', $this->training->uuid), $quizData);

        $response->assertRedirect(route('trainings.quizzes.index', $this->training->uuid))
            ->assertSessionHas('success');

        // Check quiz was created
        $newQuiz = Quiz::where('title', 'New Test Quiz')->first();
        $this->assertNotNull($newQuiz);

        // Check class assignments
        $this->assertDatabaseHas('quiz_training_class', [
            'quiz_id' => $newQuiz->id,
            'training_class_id' => $this->trainingClass1->id,
        ]);

        $this->assertDatabaseHas('quiz_training_class', [
            'quiz_id' => $newQuiz->id,
            'training_class_id' => $this->trainingClass2->id,
        ]);

        // Check material assignment
        $this->assertDatabaseHas('quiz_training_class_material', [
            'quiz_id' => $newQuiz->id,
            'training_class_material_id' => $this->material1->id,
        ]);
    }

    public function test_unauthorized_users_cannot_manage_quiz_assignments(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson(route('trainings.quizzes.assign-to-class', [
                $this->training->uuid,
                $this->quiz->uuid,
                $this->trainingClass1->uuid,
            ]));

        $response->assertStatus(403);
    }

    public function test_quiz_stats_endpoint_returns_correct_data(): void
    {
        // Assign quiz to classes
        $this->quiz->trainingClasses()->attach([
            $this->trainingClass1->id => [
                'assigned_at' => now(),
                'is_active' => true,
            ],
            $this->trainingClass2->id => [
                'assigned_at' => now(),
                'is_active' => true,
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('trainings.quizzes.stats', [
                $this->training->uuid,
                $this->quiz->uuid,
            ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'quiz',
                'total_students',
                'total_completed',
                'total_passed',
                'overall_completion_rate',
                'overall_pass_rate',
                'class_stats',
            ]);
    }

    public function test_quiz_cannot_be_assigned_to_class_from_different_training(): void
    {
        // Create another training and class
        $otherTraining = Training::factory()->create([
            'title' => 'Other Training',
            'teacher_id' => $this->teacher->id,
        ]);

        $otherClass = TrainingClass::factory()->create([
            'training_id' => $otherTraining->id,
            'name' => 'Other Class',
            'date' => now()->addDays(1),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('trainings.quizzes.assign-to-class', [
                $this->training->uuid,
                $this->quiz->uuid,
                $otherClass->uuid,
            ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_assignment_settings_can_be_updated(): void
    {
        // First assign
        $this->quiz->trainingClasses()->attach($this->trainingClass1->id, [
            'assigned_at' => now(),
            'available_from' => now(),
            'available_until' => now()->addDays(7),
            'is_active' => true,
        ]);

        // Update settings
        $response = $this->actingAs($this->admin)
            ->put(route('trainings.quizzes.update-class-assignment', [
                $this->training->uuid,
                $this->quiz->uuid,
                $this->trainingClass1->uuid,
            ]), [
                'available_from' => now()->addDays(1)->format('Y-m-d H:i:s'),
                'available_until' => now()->addDays(14)->format('Y-m-d H:i:s'),
                'is_active' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $assignment = $this->quiz->allTrainingClasses()
            ->where('training_class_id', $this->trainingClass1->id)
            ->first();

        $this->assertFalse((bool) $assignment->pivot->is_active);
    }
}
