<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_students_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/students');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Students/Index'));
    }

    public function test_unauthenticated_user_cannot_view_students_index(): void
    {
        $response = $this->get('/students');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_create_student_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/students/create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Students/Create'));
    }

    public function test_authenticated_user_can_create_student(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $studentData = [
            'user_id' => $targetUser->id,
            'student_number' => 'STU123456',
            'level' => 'Intermediate',
            'enrollment_date' => '2024-01-15',
            'phone' => '123-456-7890',
            'address' => '123 Main St, City, Country',
            'emergency_contact' => 'Jane Doe',
            'emergency_phone' => '098-765-4321',
            'is_active' => true,
        ];

        $response = $this->actingAs($user)->post('/students', $studentData);

        $response->assertRedirect('/students');
        $this->assertDatabaseHas('students', [
            'user_id' => $targetUser->id,
            'student_number' => 'STU123456',
            'level' => 'Intermediate',
        ]);
    }

    public function test_student_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/students', []);

        $response->assertSessionHasErrors(['user_id', 'student_number', 'level', 'enrollment_date']);
    }

    public function test_student_creation_validates_user_id_exists(): void
    {
        $user = User::factory()->create();

        $studentData = [
            'user_id' => 99999,
            'student_number' => 'STU123456',
            'level' => 'Intermediate',
            'enrollment_date' => '2024-01-15',
        ];

        $response = $this->actingAs($user)->post('/students', $studentData);

        $response->assertSessionHasErrors(['user_id']);
    }

    public function test_student_creation_validates_unique_user_id(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        Student::factory()->create(['user_id' => $targetUser->id]);

        $studentData = [
            'user_id' => $targetUser->id,
            'student_number' => 'STU123456',
            'level' => 'Intermediate',
            'enrollment_date' => '2024-01-15',
        ];

        $response = $this->actingAs($user)->post('/students', $studentData);

        $response->assertSessionHasErrors(['user_id']);
    }

    public function test_student_creation_validates_unique_student_number(): void
    {
        $user = User::factory()->create();
        $targetUser1 = User::factory()->create();
        $targetUser2 = User::factory()->create();

        Student::factory()->create([
            'user_id' => $targetUser1->id,
            'student_number' => 'STU123456',
        ]);

        $studentData = [
            'user_id' => $targetUser2->id,
            'student_number' => 'STU123456',
            'level' => 'Intermediate',
            'enrollment_date' => '2024-01-15',
        ];

        $response = $this->actingAs($user)->post('/students', $studentData);

        $response->assertSessionHasErrors(['student_number']);
    }

    public function test_student_creation_validates_level_values(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $studentData = [
            'user_id' => $targetUser->id,
            'student_number' => 'STU123456',
            'level' => 'InvalidLevel',
            'enrollment_date' => '2024-01-15',
        ];

        $response = $this->actingAs($user)->post('/students', $studentData);

        $response->assertSessionHasErrors(['level']);
    }

    public function test_authenticated_user_can_view_student_details(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($user)->get('/students/'.$student->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Students/Show'));
    }

    public function test_authenticated_user_can_view_edit_student_page(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($user)->get('/students/'.$student->id.'/edit');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Students/Edit'));
    }

    public function test_authenticated_user_can_update_student(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create([
            'level' => 'Beginner',
            'phone' => '111-111-1111',
        ]);

        $updateData = [
            'level' => 'Advanced',
            'phone' => '222-222-2222',
            'address' => 'New Address 456',
        ];

        $response = $this->actingAs($user)->put('/students/'.$student->id, $updateData);

        $response->assertRedirect('/students');
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'level' => 'Advanced',
            'phone' => '222-222-2222',
            'address' => 'New Address 456',
        ]);
    }

    public function test_student_update_validates_unique_user_id(): void
    {
        $user = User::factory()->create();
        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();

        $updateData = [
            'user_id' => $student2->user_id,
            'level' => 'Advanced',
        ];

        $response = $this->actingAs($user)->put('/students/'.$student1->id, $updateData);

        $response->assertSessionHasErrors(['user_id']);
    }

    public function test_student_update_validates_unique_student_number(): void
    {
        $user = User::factory()->create();
        $student1 = Student::factory()->create(['student_number' => 'STU111111']);
        Student::factory()->create(['student_number' => 'STU222222']);

        $updateData = [
            'student_number' => 'STU222222',
            'level' => 'Advanced',
        ];

        $response = $this->actingAs($user)->put('/students/'.$student1->id, $updateData);

        $response->assertSessionHasErrors(['student_number']);
    }

    public function test_authenticated_user_can_delete_student(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($user)->delete('/students/'.$student->id);

        $response->assertRedirect('/students');
        $this->assertDatabaseMissing('students', [
            'id' => $student->id,
        ]);
    }

    public function test_student_relationships_are_loaded_correctly(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($user)->get('/students/'.$student->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Students/Show')
            ->has('student.user')
        );
    }

    public function test_students_index_displays_paginated_results(): void
    {
        $user = User::factory()->create();
        Student::factory(15)->create();

        $response = $this->actingAs($user)->get('/students');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Students/Index')
            ->has('students.data', 10)
        );
    }

    public function test_enrollment_date_is_stored_correctly(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $studentData = [
            'user_id' => $targetUser->id,
            'student_number' => 'STU123456',
            'level' => 'Intermediate',
            'enrollment_date' => '2024-01-15',
        ];

        $response = $this->actingAs($user)->post('/students', $studentData);

        $response->assertRedirect('/students');

        $student = Student::where('user_id', $targetUser->id)->first();
        $this->assertEquals('2024-01-15', $student->enrollment_date->format('Y-m-d'));
    }

    public function test_student_can_have_all_optional_fields_empty(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $studentData = [
            'user_id' => $targetUser->id,
            'student_number' => 'STU123456',
            'level' => 'Intermediate',
            'enrollment_date' => '2024-01-15',
            'is_active' => true,
        ];

        $response = $this->actingAs($user)->post('/students', $studentData);

        $response->assertRedirect('/students');

        $this->assertDatabaseHas('students', [
            'user_id' => $targetUser->id,
            'student_number' => 'STU123456',
        ]);
    }
}
