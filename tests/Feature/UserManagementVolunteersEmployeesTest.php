<?php

namespace Tests\Feature;

use App\Enums\Employee\EmployeeStatus;
use App\Enums\Employee\EmploymentType;
use App\Enums\Volunteer\VolunteerCategory;
use App\Enums\Volunteer\VolunteerStatus;
use App\Enums\Volunteer\VolunteerType;
use App\Models\Employee;
use App\Models\Volunteer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementVolunteersEmployeesTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $regularUser;

    protected User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create super-admin role
        Role::create(['name' => 'super-admin']);

        // Create a super-admin user
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');

        // Create a regular user (non-admin)
        $this->regularUser = User::factory()->create();

        // Create a target user for adding as Volunteer/Employee
        $this->targetUser = User::factory()->create();
    }

    /**
     * Test that the user management page loads with volunteers and employees data.
     */
    public function test_index_page_includes_volunteers_and_employees(): void
    {
        // Create some volunteers and employees
        Volunteer::factory()->create(['user_id' => $this->targetUser->id]);
        Employee::factory()->create(['user_id' => $this->superAdmin->id]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('user-management.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('UserManagement/Index')
                ->has('volunteers')
                ->has('employees')
                ->has('teachers')
        );
    }

    /**
     * Test that SuperAdmin can add a user as a Volunteer.
     */
    public function test_superadmin_can_add_volunteer(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.add-volunteer', $this->targetUser->uuid), [
                'title' => 'New Volunteer',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Volunteer added successfully',
        ]);

        // Verify the volunteer was created
        $this->assertDatabaseHas('volunteers', [
            'user_id' => $this->targetUser->id,
            'title' => 'New Volunteer',
            'status' => VolunteerStatus::ACTIVE->value,
        ]);
    }

    /**
     * Test that SuperAdmin can add a user as a Volunteer with default values.
     */
    public function test_superadmin_can_add_volunteer_with_defaults(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.add-volunteer', $this->targetUser->uuid), []);

        $response->assertStatus(200);

        // Verify the volunteer was created with default values
        $this->assertDatabaseHas('volunteers', [
            'user_id' => $this->targetUser->id,
            'type' => VolunteerType::VOLUNTEER->value,
            'category' => VolunteerCategory::SERVICE->value,
            'status' => VolunteerStatus::ACTIVE->value,
            'level' => 1,
            'points' => 0,
        ]);
    }

    /**
     * Test that cannot add a user as Volunteer if they already are a Volunteer.
     */
    public function test_cannot_add_duplicate_volunteer(): void
    {
        // First add the user as volunteer
        Volunteer::factory()->create(['user_id' => $this->targetUser->id]);

        // Try to add again
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.add-volunteer', $this->targetUser->uuid), []);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'User is already a volunteer',
        ]);
    }

    /**
     * Test that SuperAdmin can remove a Volunteer.
     */
    public function test_superadmin_can_remove_volunteer(): void
    {
        $volunteer = Volunteer::factory()->create(['user_id' => $this->targetUser->id]);

        $response = $this->actingAs($this->superAdmin)
            ->deleteJson(route('user-management.remove-volunteer', $volunteer->uuid));

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Volunteer removed successfully',
        ]);

        // Verify the volunteer was soft deleted
        $this->assertSoftDeleted('volunteers', [
            'id' => $volunteer->id,
        ]);
    }

    /**
     * Test that SuperAdmin can add a user as an Employee.
     */
    public function test_superadmin_can_add_employee(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.add-employee', $this->targetUser->uuid), [
                'position' => 'Software Developer',
                'job_title' => 'Junior Developer',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Employee added successfully',
        ]);

        // Verify the employee was created
        $this->assertDatabaseHas('employees', [
            'user_id' => $this->targetUser->id,
            'position' => 'Software Developer',
            'job_title' => 'Junior Developer',
            'status' => EmployeeStatus::ACTIVE->value,
        ]);
    }

    /**
     * Test that SuperAdmin can add a user as an Employee with defaults.
     */
    public function test_superadmin_can_add_employee_with_defaults(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.add-employee', $this->targetUser->uuid), []);

        $response->assertStatus(200);

        // Verify the employee was created with default values
        $this->assertDatabaseHas('employees', [
            'user_id' => $this->targetUser->id,
            'employment_type' => EmploymentType::FULL_TIME->value,
            'status' => EmployeeStatus::ACTIVE->value,
            'annual_leave_days' => 25,
            'remaining_leave_days' => 25,
            'sick_days_taken' => 0,
        ]);
    }

    /**
     * Test that cannot add a user as Employee if they already are an Employee.
     */
    public function test_cannot_add_duplicate_employee(): void
    {
        // First add the user as employee
        Employee::factory()->create(['user_id' => $this->targetUser->id]);

        // Try to add again
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.add-employee', $this->targetUser->uuid), []);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'User is already an employee',
        ]);
    }

    /**
     * Test that SuperAdmin can remove an Employee.
     */
    public function test_superadmin_can_remove_employee(): void
    {
        $employee = Employee::factory()->create(['user_id' => $this->targetUser->id]);

        $response = $this->actingAs($this->superAdmin)
            ->deleteJson(route('user-management.remove-employee', $employee->uuid));

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Employee removed successfully',
        ]);

        // Verify the employee was soft deleted
        $this->assertSoftDeleted('employees', [
            'id' => $employee->id,
        ]);
    }

    /**
     * Test that non-SuperAdmin cannot add a Volunteer.
     */
    public function test_non_superadmin_cannot_add_volunteer(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson(route('user-management.add-volunteer', $this->targetUser->uuid), []);

        $response->assertStatus(403);
    }

    /**
     * Test that non-SuperAdmin cannot remove a Volunteer.
     */
    public function test_non_superadmin_cannot_remove_volunteer(): void
    {
        $volunteer = Volunteer::factory()->create(['user_id' => $this->targetUser->id]);

        $response = $this->actingAs($this->regularUser)
            ->deleteJson(route('user-management.remove-volunteer', $volunteer->uuid));

        $response->assertStatus(403);
    }

    /**
     * Test that non-SuperAdmin cannot add an Employee.
     */
    public function test_non_superadmin_cannot_add_employee(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson(route('user-management.add-employee', $this->targetUser->uuid), []);

        $response->assertStatus(403);
    }

    /**
     * Test that non-SuperAdmin cannot remove an Employee.
     */
    public function test_non_superadmin_cannot_remove_employee(): void
    {
        $employee = Employee::factory()->create(['user_id' => $this->targetUser->id]);

        $response = $this->actingAs($this->regularUser)
            ->deleteJson(route('user-management.remove-employee', $employee->uuid));

        $response->assertStatus(403);
    }

    /**
     * Test that Volunteers and Employees are correctly loaded with user relationships.
     */
    public function test_volunteers_and_employees_include_user_relationship(): void
    {
        Volunteer::factory()->create(['user_id' => $this->targetUser->id]);
        Employee::factory()->create(['user_id' => $this->superAdmin->id]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('user-management.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->has('volunteers.0.user')
                ->has('employees.0.user')
        );
    }

    /**
     * Test adding Volunteer validates input types.
     */
    public function test_add_volunteer_validates_title_length(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.add-volunteer', $this->targetUser->uuid), [
                'title' => str_repeat('a', 256), // Exceeds max:255
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    /**
     * Test adding Employee validates input types.
     */
    public function test_add_employee_validates_position_length(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.add-employee', $this->targetUser->uuid), [
                'position' => str_repeat('a', 256), // Exceeds max:255
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['position']);
    }

    /**
     * Test that unauthenticated users cannot access volunteer/employee endpoints.
     */
    public function test_unauthenticated_cannot_add_volunteer(): void
    {
        $response = $this->postJson(route('user-management.add-volunteer', $this->targetUser->uuid), []);

        $response->assertStatus(401);
    }

    /**
     * Test that unauthenticated users cannot access volunteer/employee endpoints.
     */
    public function test_unauthenticated_cannot_add_employee(): void
    {
        $response = $this->postJson(route('user-management.add-employee', $this->targetUser->uuid), []);

        $response->assertStatus(401);
    }
}
