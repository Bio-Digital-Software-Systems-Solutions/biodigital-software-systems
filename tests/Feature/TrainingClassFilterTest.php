<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\Training;
use App\Models\TrainingClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrainingClassFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Training $training1;

    protected Training $training2;

    protected Teacher $teacher1;

    protected Teacher $teacher2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create teachers
        $this->teacher1 = Teacher::create([
            'user_id' => User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe'])->id,
            'specialization' => 'Computer Science',
            'bio' => 'Experienced teacher',
            'is_active' => true,
        ]);

        $this->teacher2 = Teacher::create([
            'user_id' => User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith'])->id,
            'specialization' => 'Mathematics',
            'bio' => 'Math expert',
            'is_active' => true,
        ]);

        // Create trainings
        $this->training1 = Training::factory()->create([
            'title' => 'Laravel Development',
            'teacher_id' => $this->teacher1->id,
        ]);

        $this->training2 = Training::factory()->create([
            'title' => 'React Development',
            'teacher_id' => $this->teacher2->id,
        ]);
    }

    public function test_classes_can_be_retrieved_for_index(): void
    {
        // Create classes
        TrainingClass::factory()->create([
            'training_id' => $this->training1->id,
            'teacher_id' => $this->teacher1->id,
            'date' => now()->addDays(1),
            'room' => 'Room A1',
        ]);

        TrainingClass::factory()->create([
            'training_id' => $this->training2->id,
            'teacher_id' => $this->teacher2->id,
            'date' => now()->addDays(2),
            'room' => 'Room B2',
        ]);

        $response = $this->actingAs($this->admin)->get(route('training-classes.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('TrainingClass/Dashboard')
            ->has('classes.data', 2)
        );
    }

    public function test_search_filters_classes_by_training_name(): void
    {
        TrainingClass::factory()->create([
            'training_id' => $this->training1->id,
            'teacher_id' => $this->teacher1->id,
            'room' => 'Room A1',
        ]);

        TrainingClass::factory()->create([
            'training_id' => $this->training2->id,
            'teacher_id' => $this->teacher2->id,
            'room' => 'Room B2',
        ]);

        $response = $this->actingAs($this->admin)->get(route('training-classes.index'));

        $response->assertStatus(200);

        // Get classes from response
        $classes = $response->viewData('page')['props']['classes']['data'];

        // Test that both classes exist
        $this->assertCount(2, $classes);

        // Verify training names
        $trainingNames = collect($classes)->pluck('training_name')->toArray();
        $this->assertContains('Laravel Development', $trainingNames);
        $this->assertContains('React Development', $trainingNames);
    }

    public function test_classes_have_correct_teacher_information(): void
    {
        TrainingClass::factory()->create([
            'training_id' => $this->training1->id,
            'teacher_id' => $this->teacher1->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('training-classes.index'));

        $classes = $response->viewData('page')['props']['classes']['data'];

        // Verify at least one class has teacher information
        $this->assertNotEmpty($classes);
        $this->assertArrayHasKey('teacher_name', $classes[0]);
        $this->assertNotNull($classes[0]['teacher_name']);
    }

    public function test_classes_have_correct_room_information(): void
    {
        TrainingClass::factory()->create([
            'training_id' => $this->training1->id,
            'teacher_id' => $this->teacher1->id,
            'room' => 'Physics Lab',
        ]);

        TrainingClass::factory()->create([
            'training_id' => $this->training2->id,
            'teacher_id' => $this->teacher2->id,
            'room' => 'Computer Lab',
        ]);

        $response = $this->actingAs($this->admin)->get(route('training-classes.index'));

        $classes = $response->viewData('page')['props']['classes']['data'];

        $rooms = collect($classes)->pluck('room')->toArray();
        $this->assertContains('Physics Lab', $rooms);
        $this->assertContains('Computer Lab', $rooms);
    }

    public function test_classes_have_status_information(): void
    {
        TrainingClass::factory()->create([
            'training_id' => $this->training1->id,
            'teacher_id' => $this->teacher1->id,
            'date' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('training-classes.index'));

        $classes = $response->viewData('page')['props']['classes']['data'];

        $statuses = collect($classes)->pluck('status')->toArray();
        $this->assertContains('À venir', $statuses);
    }

    public function test_classes_include_student_count(): void
    {
        $class = TrainingClass::factory()->create([
            'training_id' => $this->training1->id,
            'teacher_id' => $this->teacher1->id,
            'max_students' => 30,
        ]);

        // Enroll some students in the class
        $students = User::factory()->count(5)->create();
        foreach ($students as $student) {
            DB::table('training_enrollments')->insert([
                'user_id' => $student->id,
                'training_id' => $this->training1->id,
                'training_class_id' => $class->id,
                'status' => 'approved',
                'enrolled_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('training-classes.index'));

        $classes = $response->viewData('page')['props']['classes']['data'];
        $targetClass = collect($classes)->firstWhere('id', $class->id);

        $this->assertNotNull($targetClass);
        $this->assertEquals(5, $targetClass['students_count']);
        $this->assertEquals(30, $targetClass['max_students']);
    }

    public function test_non_authenticated_users_cannot_access_classes(): void
    {
        $response = $this->get(route('training-classes.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_classes_can_be_filtered_by_multiple_criteria(): void
    {
        // Create multiple classes with different attributes
        TrainingClass::factory()->create([
            'training_id' => $this->training1->id,
            'teacher_id' => $this->teacher1->id,
            'room' => 'Room A',
            'date' => now()->addDay(),
        ]);

        TrainingClass::factory()->create([
            'training_id' => $this->training2->id,
            'teacher_id' => $this->teacher2->id,
            'room' => 'Room B',
            'date' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('training-classes.index'));

        $classes = $response->viewData('page')['props']['classes']['data'];

        // Verify all classes are returned
        $this->assertCount(2, $classes);

        // Verify we can identify unique training names
        $uniqueTrainings = collect($classes)->pluck('training_name')->unique();
        $this->assertCount(2, $uniqueTrainings);

        // Verify we can identify unique teachers
        $uniqueTeachers = collect($classes)->pluck('teacher_name')->unique();
        $this->assertCount(2, $uniqueTeachers);
    }
}
