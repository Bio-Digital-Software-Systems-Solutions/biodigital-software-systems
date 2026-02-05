<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTeacherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create super-admin role
        Role::create(['name' => 'super-admin']);
    }

    public function test_only_superadmin_can_access_user_management_teacher_features()
    {
        $regularUser = User::factory()->create();
        $targetUser = User::factory()->create();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Access denied. super-admin role required.');

        $this->actingAs($regularUser)
            ->withoutExceptionHandling()
            ->post(route('user-management.add-teacher', $targetUser->uuid), [
                'is_active' => true,
            ]);
    }

    public function test_superadmin_can_access_user_management_index_with_teachers()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        Teacher::factory(3)->create();

        $response = $this->actingAs($superAdmin)->get(route('user-management.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('UserManagement/Index')
            ->has('teachers', 3)
        );
    }

    public function test_superadmin_can_add_user_as_teacher()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $targetUser = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $teacherData = [
            'specialization' => 'Mathematics',
            'experience_years' => 5,
            'bio' => 'Experienced math teacher',
            'qualifications' => ['PhD', 'Teaching Certificate'],
            'phone' => '123-456-7890',
        ];

        $response = $this->actingAs($superAdmin)->post(
            route('user-management.add-teacher', $targetUser->uuid),
            $teacherData
        );

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Teacher added successfully',
        ]);

        $this->assertDatabaseHas('teachers', [
            'user_id' => $targetUser->id,
            'specialization' => 'Mathematics',
            'experience_years' => 5,
            'bio' => 'Experienced math teacher',
            'phone' => '123-456-7890',
            'is_active' => true,
        ]);
    }

    public function test_superadmin_can_add_teacher_with_minimal_data()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $targetUser = User::factory()->create();

        $response = $this->actingAs($superAdmin)->post(
            route('user-management.add-teacher', $targetUser->uuid),
            ['is_active' => true]
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('teachers', [
            'user_id' => $targetUser->id,
            'is_active' => true,
        ]);
    }

    public function test_cannot_add_user_as_teacher_if_already_teacher()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $targetUser = User::factory()->create();
        Teacher::factory()->create(['user_id' => $targetUser->id]);

        $response = $this->actingAs($superAdmin)->post(
            route('user-management.add-teacher', $targetUser->uuid),
            ['is_active' => true]
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'User is already a teacher',
        ]);
    }

    public function test_add_teacher_validates_specialization_max_length()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $targetUser = User::factory()->create();

        $response = $this->actingAs($superAdmin)->post(
            route('user-management.add-teacher', $targetUser->uuid),
            ['specialization' => str_repeat('a', 256)]
        );

        $response->assertSessionHasErrors(['specialization']);
    }

    public function test_add_teacher_validates_experience_years_minimum()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $targetUser = User::factory()->create();

        $response = $this->actingAs($superAdmin)->post(
            route('user-management.add-teacher', $targetUser->uuid),
            ['experience_years' => -1]
        );

        $response->assertSessionHasErrors(['experience_years']);
    }

    public function test_add_teacher_validates_qualifications_is_array()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $targetUser = User::factory()->create();

        $response = $this->actingAs($superAdmin)->post(
            route('user-management.add-teacher', $targetUser->uuid),
            ['qualifications' => 'not-an-array']
        );

        $response->assertSessionHasErrors(['qualifications']);
    }

    public function test_superadmin_can_remove_teacher()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $teacher = Teacher::factory()->create();

        $response = $this->actingAs($superAdmin)->delete(
            route('user-management.remove-teacher', $teacher->id)
        );

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Teacher removed successfully',
        ]);

        $this->assertDatabaseMissing('teachers', [
            'id' => $teacher->id,
        ]);
    }

    public function test_regular_user_cannot_remove_teacher()
    {
        $regularUser = User::factory()->create();
        $teacher = Teacher::factory()->create();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Access denied. super-admin role required.');

        $this->actingAs($regularUser)
            ->withoutExceptionHandling()
            ->delete(
                route('user-management.remove-teacher', $teacher->id)
            );
    }

    public function test_superadmin_can_update_teacher_information()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $teacher = Teacher::factory()->create([
            'specialization' => 'Mathematics',
            'experience_years' => 5,
            'bio' => 'Original bio',
            'is_active' => true,
        ]);

        $updateData = [
            'specialization' => 'Physics',
            'experience_years' => 10,
            'bio' => 'Updated bio',
            'qualifications' => ['PhD in Physics', 'Master Degree'],
            'phone' => '987-654-3210',
            'is_active' => false,
        ];

        $response = $this->actingAs($superAdmin)->put(
            route('user-management.update-teacher', $teacher->id),
            $updateData
        );

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Teacher updated successfully',
        ]);

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'specialization' => 'Physics',
            'experience_years' => 10,
            'bio' => 'Updated bio',
            'phone' => '987-654-3210',
            'is_active' => false,
        ]);

        $teacher->refresh();
        $this->assertEquals(['PhD in Physics', 'Master Degree'], $teacher->qualifications);
    }

    public function test_update_teacher_validates_experience_years_minimum()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $teacher = Teacher::factory()->create();

        $response = $this->actingAs($superAdmin)->put(
            route('user-management.update-teacher', $teacher->id),
            ['experience_years' => -5]
        );

        $response->assertSessionHasErrors(['experience_years']);
    }

    public function test_update_teacher_validates_is_active_is_boolean()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $teacher = Teacher::factory()->create();

        $response = $this->actingAs($superAdmin)->put(
            route('user-management.update-teacher', $teacher->id),
            ['is_active' => 'not-a-boolean']
        );

        $response->assertSessionHasErrors(['is_active']);
    }

    public function test_regular_user_cannot_update_teacher()
    {
        $regularUser = User::factory()->create();
        $teacher = Teacher::factory()->create();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Access denied. super-admin role required.');

        $this->actingAs($regularUser)
            ->withoutExceptionHandling()
            ->put(
                route('user-management.update-teacher', $teacher->id),
                ['specialization' => 'New Specialization']
            );
    }

    public function test_teacher_user_relationship_is_loaded_when_adding()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $targetUser = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $response = $this->actingAs($superAdmin)->post(
            route('user-management.add-teacher', $targetUser->uuid),
            ['is_active' => true]
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'teacher' => [
                'id',
                'user_id',
                'user' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                ],
            ],
        ]);
    }

    public function test_teacher_user_relationship_is_loaded_when_updating()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $teacher = Teacher::factory()->create();

        $response = $this->actingAs($superAdmin)->put(
            route('user-management.update-teacher', $teacher->id),
            ['specialization' => 'Updated Specialization']
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'teacher' => [
                'id',
                'user_id',
                'user',
            ],
        ]);
    }

    public function test_qualifications_are_stored_as_json_array()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $targetUser = User::factory()->create();

        $qualifications = ['PhD in Computer Science', 'Master in Education', 'Teaching Certificate'];

        $response = $this->actingAs($superAdmin)->post(
            route('user-management.add-teacher', $targetUser->uuid),
            [
                'qualifications' => $qualifications,
                'is_active' => true,
            ]
        );

        $response->assertStatus(200);

        $teacher = Teacher::where('user_id', $targetUser->id)->first();
        $this->assertIsArray($teacher->qualifications);
        $this->assertEquals($qualifications, $teacher->qualifications);
    }

    public function test_phone_number_validation_respects_max_length()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $targetUser = User::factory()->create();

        $response = $this->actingAs($superAdmin)->post(
            route('user-management.add-teacher', $targetUser->uuid),
            ['phone' => str_repeat('1', 21)]
        );

        $response->assertSessionHasErrors(['phone']);
    }
}
