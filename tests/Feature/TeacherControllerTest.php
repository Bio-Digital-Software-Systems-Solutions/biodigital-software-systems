<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_teachers_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/teachers');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Teachers/Index'));
    }

    public function test_unauthenticated_user_cannot_view_teachers_index(): void
    {
        $response = $this->get('/teachers');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_create_teacher_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/teachers/create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Teachers/Create'));
    }

    public function test_authenticated_user_can_create_teacher(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $teacherData = [
            'user_id' => $targetUser->id,
            'specialization' => 'Computer Science',
            'experience_years' => 5,
            'bio' => 'Experienced computer science teacher',
            'qualifications' => ['PhD', 'Teaching Certificate'],
            'phone' => '123-456-7890',
            'is_active' => true,
        ];

        $response = $this->actingAs($user)->post('/teachers', $teacherData);

        $response->assertRedirect('/teachers');
        $this->assertDatabaseHas('teachers', [
            'user_id' => $targetUser->id,
            'specialization' => 'Computer Science',
            'experience_years' => 5,
        ]);
    }

    public function test_teacher_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/teachers', []);

        $response->assertSessionHasErrors(['user_id', 'specialization', 'experience_years']);
    }

    public function test_teacher_creation_validates_user_id_exists(): void
    {
        $user = User::factory()->create();

        $teacherData = [
            'user_id' => 99999,
            'specialization' => 'Computer Science',
            'experience_years' => 5,
        ];

        $response = $this->actingAs($user)->post('/teachers', $teacherData);

        $response->assertSessionHasErrors(['user_id']);
    }

    public function test_teacher_creation_validates_unique_user_id(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        Teacher::factory()->create(['user_id' => $targetUser->id]);

        $teacherData = [
            'user_id' => $targetUser->id,
            'specialization' => 'Computer Science',
            'experience_years' => 5,
        ];

        $response = $this->actingAs($user)->post('/teachers', $teacherData);

        $response->assertSessionHasErrors(['user_id']);
    }

    public function test_teacher_creation_validates_experience_years_range(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $teacherData = [
            'user_id' => $targetUser->id,
            'specialization' => 'Computer Science',
            'experience_years' => -1,
        ];

        $response = $this->actingAs($user)->post('/teachers', $teacherData);

        $response->assertSessionHasErrors(['experience_years']);

        $teacherData['experience_years'] = 51;

        $response = $this->actingAs($user)->post('/teachers', $teacherData);

        $response->assertSessionHasErrors(['experience_years']);
    }

    public function test_authenticated_user_can_view_teacher_details(): void
    {
        $user = User::factory()->create();
        $teacher = Teacher::factory()->create();

        $response = $this->actingAs($user)->get('/teachers/'.$teacher->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Teachers/Show'));
    }

    public function test_authenticated_user_can_view_edit_teacher_page(): void
    {
        $user = User::factory()->create();
        $teacher = Teacher::factory()->create();

        $response = $this->actingAs($user)->get('/teachers/'.$teacher->id.'/edit');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Teachers/Edit'));
    }

    public function test_authenticated_user_can_update_teacher(): void
    {
        $user = User::factory()->create();
        $teacher = Teacher::factory()->create([
            'specialization' => 'Mathematics',
            'experience_years' => 5,
        ]);

        $updateData = [
            'specialization' => 'Physics',
            'experience_years' => 10,
            'bio' => 'Updated bio',
        ];

        $response = $this->actingAs($user)->put('/teachers/'.$teacher->id, $updateData);

        $response->assertRedirect('/teachers');
        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'specialization' => 'Physics',
            'experience_years' => 10,
            'bio' => 'Updated bio',
        ]);
    }

    public function test_teacher_update_validates_unique_user_id(): void
    {
        $user = User::factory()->create();
        $teacher1 = Teacher::factory()->create();
        $teacher2 = Teacher::factory()->create();

        $updateData = [
            'user_id' => $teacher2->user_id,
            'specialization' => 'Physics',
            'experience_years' => 10,
        ];

        $response = $this->actingAs($user)->put('/teachers/'.$teacher1->id, $updateData);

        $response->assertSessionHasErrors(['user_id']);
    }

    public function test_authenticated_user_can_delete_teacher(): void
    {
        $user = User::factory()->create();
        $teacher = Teacher::factory()->create();

        $response = $this->actingAs($user)->delete('/teachers/'.$teacher->id);

        $response->assertRedirect('/teachers');
        $this->assertDatabaseMissing('teachers', [
            'id' => $teacher->id,
        ]);
    }

    public function test_teacher_relationships_are_loaded_correctly(): void
    {
        $user = User::factory()->create();
        $teacher = Teacher::factory()->create();

        $response = $this->actingAs($user)->get('/teachers/'.$teacher->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Teachers/Show')
            ->has('teacher.user')
        );
    }

    public function test_teachers_index_displays_paginated_results(): void
    {
        $user = User::factory()->create();
        Teacher::factory(15)->create();

        $response = $this->actingAs($user)->get('/teachers');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Teachers/Index')
            ->has('teachers.data', 10)
        );
    }

    public function test_qualifications_are_stored_as_array(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $teacherData = [
            'user_id' => $targetUser->id,
            'specialization' => 'Computer Science',
            'experience_years' => 5,
            'qualifications' => ['PhD in CS', 'Teaching Certificate', 'Industry Experience'],
        ];

        $response = $this->actingAs($user)->post('/teachers', $teacherData);

        $response->assertRedirect('/teachers');

        $teacher = Teacher::where('user_id', $targetUser->id)->first();
        $this->assertIsArray($teacher->qualifications);
        $this->assertCount(3, $teacher->qualifications);
    }
}
