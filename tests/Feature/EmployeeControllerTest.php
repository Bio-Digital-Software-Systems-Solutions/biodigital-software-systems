<?php

namespace Tests\Feature;

use App\Enums\Employee\EmployeeStatus;
use App\Enums\Employee\EmploymentType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view employees']);
        Permission::create(['name' => 'create employees']);
        Permission::create(['name' => 'edit employees']);
        Permission::create(['name' => 'delete employees']);
        Permission::create(['name' => 'manage employees']);

        // Create admin role with all permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'manage employees',
        ]);

        // Create member role with view only
        $memberRole = Role::create(['name' => 'member']);
        $memberRole->givePermissionTo(['view employees']);
    }

    // ==========================================
    // Index Tests
    // ==========================================

    public function test_user_with_permission_can_view_employees_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/employees');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page->component('Employees/Index'));
    }

    public function test_index_returns_correct_data_structure(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Employee::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/employees');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Employees/Index')
            ->has('employees')
            ->has('filters')
            ->has('statuses')
            ->has('employmentTypes')
            ->has('departments')
            ->has('stats')
        );
    }

    public function test_guest_cannot_access_employees(): void
    {
        $response = $this->get('/employees');
        $response->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_view_employees(): void
    {
        $user = User::factory()->create();
        // No role assigned

        $response = $this->actingAs($user)->get('/employees');

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    // ==========================================
    // Search and Filter Tests
    // ==========================================

    public function test_employees_can_be_filtered_by_search(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $userToFind = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        Employee::factory()->create(['user_id' => $userToFind->id]);
        Employee::factory()->count(2)->create();

        $response = $this->actingAs($user)->get('/employees?search=John');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Employees/Index')
            ->where('filters.search', 'John')
        );
    }

    public function test_employees_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        Employee::factory()->active()->count(2)->create();
        Employee::factory()->onLeave()->create();

        $response = $this->actingAs($user)->get('/employees?status=active');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Employees/Index')
            ->where('filters.status', 'active')
        );
    }

    public function test_employees_can_be_filtered_by_employment_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        Employee::factory()->fullTime()->count(2)->create();
        Employee::factory()->partTime()->create();

        $response = $this->actingAs($user)->get('/employees?employment_type=full_time');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Employees/Index')
            ->where('filters.employment_type', 'full_time')
        );
    }

    public function test_employees_can_be_filtered_by_department(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $department = Department::factory()->create();
        Employee::factory()->inDepartment($department)->count(2)->create();
        Employee::factory()->create();

        $response = $this->actingAs($user)->get("/employees?department={$department->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Employees/Index')
            ->where('filters.department', (string) $department->id)
        );
    }

    // ==========================================
    // Show Tests
    // ==========================================

    public function test_user_can_view_single_employee(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $employee = Employee::factory()->create();

        $response = $this->actingAs($user)->get("/employees/{$employee->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Employees/Show')
            ->has('employee')
            ->has('canManage')
        );
    }

    public function test_show_returns_employee_details(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $employee = Employee::factory()->create([
            'position' => 'Developer',
            'job_title' => 'Senior Developer',
        ]);

        $response = $this->actingAs($user)->get("/employees/{$employee->uuid}");

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Employees/Show')
            ->where('employee.position', 'Developer')
            ->where('employee.job_title', 'Senior Developer')
        );
    }

    // ==========================================
    // Create Tests
    // ==========================================

    public function test_user_with_permission_can_view_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/employees/create');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Employees/Create')
            ->has('users')
            ->has('departments')
            ->has('managers')
            ->has('statuses')
            ->has('employmentTypes')
            ->has('paymentMethods')
        );
    }

    public function test_user_without_permission_cannot_view_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/employees/create');

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    public function test_admin_can_create_employee(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $employeeUser = User::factory()->create();
        $department = Department::factory()->create();

        $employeeData = [
            'user_id' => $employeeUser->id,
            'department_id' => $department->id,
            'position' => 'Developer',
            'job_title' => 'Senior Developer',
            'status' => EmployeeStatus::ACTIVE->value,
            'employment_type' => EmploymentType::FULL_TIME->value,
            'hire_date' => now()->format('Y-m-d'),
            'weekly_hours' => 40,
            'annual_leave_days' => 30,
        ];

        $response = $this->actingAs($admin)->post('/employees', $employeeData);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', [
            'user_id' => $employeeUser->id,
            'position' => 'Developer',
            'job_title' => 'Senior Developer',
        ]);
    }

    public function test_cannot_create_employee_for_same_user_twice(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $employeeUser = User::factory()->create();
        Employee::factory()->create(['user_id' => $employeeUser->id]);

        $employeeData = [
            'user_id' => $employeeUser->id,
            'position' => 'New Position',
            'status' => EmployeeStatus::ACTIVE->value,
            'employment_type' => EmploymentType::FULL_TIME->value,
        ];

        $response = $this->actingAs($admin)->post('/employees', $employeeData);

        $response->assertSessionHasErrors('user_id');
    }

    public function test_validation_errors_on_create(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/employees', [
            'user_id' => '',
            'status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors(['user_id', 'status']);
    }

    // ==========================================
    // Edit Tests
    // ==========================================

    public function test_user_with_permission_can_view_edit_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $employee = Employee::factory()->create();

        $response = $this->actingAs($user)->get("/employees/{$employee->uuid}/edit");

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Employees/Edit')
            ->has('employee')
            ->has('departments')
            ->has('managers')
            ->has('statuses')
            ->has('employmentTypes')
            ->has('paymentMethods')
        );
    }

    public function test_user_without_permission_cannot_view_edit_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $employee = Employee::factory()->create();

        $response = $this->actingAs($user)->get("/employees/{$employee->uuid}/edit");

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
    }

    // ==========================================
    // Update Tests
    // ==========================================

    public function test_admin_can_update_employee(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $employee = Employee::factory()->create([
            'position' => 'Original Position',
        ]);

        $updateData = [
            'position' => 'Updated Position',
            'job_title' => 'New Job Title',
            'status' => EmployeeStatus::ACTIVE->value,
            'employment_type' => EmploymentType::FULL_TIME->value,
        ];

        $response = $this->actingAs($admin)->put("/employees/{$employee->uuid}", $updateData);

        $response->assertRedirect("/employees/{$employee->uuid}");
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'position' => 'Updated Position',
            'job_title' => 'New Job Title',
        ]);
    }

    public function test_cannot_assign_employee_as_own_manager(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $employee = Employee::factory()->create();

        $updateData = [
            'manager_id' => $employee->id,
            'status' => EmployeeStatus::ACTIVE->value,
            'employment_type' => EmploymentType::FULL_TIME->value,
        ];

        $response = $this->actingAs($admin)->put("/employees/{$employee->uuid}", $updateData);

        $response->assertSessionHasErrors('manager_id');
    }

    // ==========================================
    // Delete Tests
    // ==========================================

    public function test_admin_can_delete_employee(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $employee = Employee::factory()->create();

        $response = $this->actingAs($admin)->delete("/employees/{$employee->uuid}");

        $response->assertRedirect('/employees');
        $this->assertSoftDeleted('employees', [
            'id' => $employee->id,
        ]);
    }

    public function test_member_cannot_delete_employee(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $employee = Employee::factory()->create();

        $response = $this->actingAs($user)->delete("/employees/{$employee->uuid}");

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect'
        );
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
        ]);
    }

    // ==========================================
    // Status Change Tests
    // ==========================================

    public function test_admin_can_terminate_employee(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $employee = Employee::factory()->active()->create();

        $response = $this->actingAs($admin)->post("/employees/{$employee->uuid}/terminate", [
            'termination_date' => now()->format('Y-m-d'),
            'termination_reason' => 'Resignation',
        ]);

        $response->assertRedirect();
        $employee->refresh();
        $this->assertEquals(EmployeeStatus::TERMINATED, $employee->status);
        $this->assertEquals('Resignation', $employee->termination_reason);
    }

    public function test_admin_can_activate_employee(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $employee = Employee::factory()->terminated()->create();

        $response = $this->actingAs($admin)->post("/employees/{$employee->uuid}/activate");

        $response->assertRedirect();
        $employee->refresh();
        $this->assertEquals(EmployeeStatus::ACTIVE, $employee->status);
        $this->assertNull($employee->termination_date);
    }

    public function test_admin_can_set_employee_on_leave(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $employee = Employee::factory()->active()->create();

        $response = $this->actingAs($admin)->post("/employees/{$employee->uuid}/on-leave");

        $response->assertRedirect();
        $employee->refresh();
        $this->assertEquals(EmployeeStatus::ON_LEAVE, $employee->status);
    }

    public function test_admin_can_reset_employee_leave(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $employee = Employee::factory()->create([
            'annual_leave_days' => 30,
            'remaining_leave_days' => 10,
            'sick_days_taken' => 5,
        ]);

        $response = $this->actingAs($admin)->post("/employees/{$employee->uuid}/reset-leave");

        $response->assertRedirect();
        $employee->refresh();
        $this->assertEquals(30, $employee->remaining_leave_days);
        $this->assertEquals(0, $employee->sick_days_taken);
    }

    // ==========================================
    // Stats Tests
    // ==========================================

    public function test_stats_are_calculated_correctly(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Employee::factory()->active()->count(5)->create();
        Employee::factory()->onLeave()->count(2)->create();
        Employee::factory()->terminated()->create();
        // Create one employee who was hired this month for the new_this_month stat
        Employee::factory()->active()->create([
            'hire_date' => now()->startOfMonth()->addDays(1),
        ]);

        $response = $this->actingAs($admin)->get('/employees');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Employees/Index')
            ->where('stats.total', 9)
            ->where('stats.active', 6)
            ->where('stats.on_leave', 2)
            // new_this_month only counts those with hire_date >= start of month
            // Factory default creates employees with hire_date in the past
            ->has('stats.new_this_month')
        );
    }

    // ==========================================
    // Export Tests
    // ==========================================

    public function test_admin_can_export_employees(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Employee::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/employees-export');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'employees' => [
                '*' => [
                    'employee_number',
                    'name',
                    'email',
                    'position',
                    'department',
                    'status',
                    'employment_type',
                    'hire_date',
                ],
            ],
        ]);
    }

    public function test_export_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Employee::factory()->active()->count(3)->create();
        Employee::factory()->terminated()->count(2)->create();

        $response = $this->actingAs($admin)->get('/employees-export?status=active');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'employees');
    }
}
