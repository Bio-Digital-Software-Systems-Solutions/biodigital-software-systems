<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->headUser = User::factory()->create();
    }

    public function test_index_displays_departments(): void
    {
        $departments = Department::factory()->count(3)->create();

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Departments/Index')
            ->has('departments.data', 3)
        );
    }

    public function test_create_displays_form(): void
    {
        $this->user->givePermissionTo('manage departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.create'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Departments/Create')
            ->has('users')
        );
    }

    public function test_store_creates_department(): void
    {
        $this->user->givePermissionTo('manage departments');

        $departmentData = [
            'name' => 'Test Department',
            'code' => 'TEST-DEPT',
            'description' => 'Test department description',
            'head_of_department' => $this->headUser->id,
            'budget' => '100000.00',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('departments.store'), $departmentData);

        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHas('success', 'Department created successfully.');

        $this->assertDatabaseHas('departments', [
            'name' => 'Test Department',
            'code' => 'TEST-DEPT',
            'head_of_department' => $this->headUser->id,
            'budget' => 100000.00,
            'is_active' => true,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->user->givePermissionTo('manage departments');

        $response = $this->actingAs($this->user)
            ->post(route('departments.store'), []);

        $response->assertSessionHasErrors(['name', 'code']);
    }

    public function test_store_validates_unique_code(): void
    {
        Department::factory()->create(['code' => 'DUPLICATE']);

        $this->user->givePermissionTo('manage departments');

        $response = $this->actingAs($this->user)
            ->post(route('departments.store'), [
                'name' => 'Test Department',
                'code' => 'DUPLICATE',
            ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_store_validates_budget_positive(): void
    {
        $this->user->givePermissionTo('manage departments');

        $response = $this->actingAs($this->user)
            ->post(route('departments.store'), [
                'name' => 'Test Department',
                'code' => 'TEST',
                'budget' => -1000,
            ]);

        $response->assertSessionHasErrors(['budget']);
    }

    public function test_show_displays_department_for_member(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->user);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Departments/Show')
            ->where('department.id', $department->id)
            ->where('department.name', $department->name)
        );
    }

    public function test_show_displays_department_for_manager(): void
    {
        $department = Department::factory()->create();

        $this->user->givePermissionTo(['view departments', 'manage departments']);

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Departments/Show')
            ->where('department.id', $department->id)
            ->where('department.name', $department->name)
        );
    }

    public function test_edit_displays_form(): void
    {
        $department = Department::factory()->create();

        $this->user->givePermissionTo('manage departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.edit', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Departments/Edit')
            ->where('department.id', $department->id)
            ->has('users')
        );
    }

    public function test_update_modifies_department(): void
    {
        $department = Department::factory()->create([
            'name' => 'Original Name',
            'code' => 'ORIG',
            'budget' => 50000.00,
        ]);

        $this->user->givePermissionTo('manage departments');

        $updateData = [
            'name' => 'Updated Name',
            'code' => 'UPD',
            'description' => 'Updated description',
            'head_of_department' => $this->headUser->id,
            'budget' => '75000.00',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('departments.update', $department), $updateData);

        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHas('success', 'Department updated successfully.');

        $department->refresh();
        $this->assertEquals('Updated Name', $department->name);
        $this->assertEquals('UPD', $department->code);
        $this->assertEquals($this->headUser->id, $department->head_of_department);
        $this->assertEquals(75000.00, (float) $department->budget);
        $this->assertFalse($department->is_active);
    }

    public function test_update_validates_unique_code_excluding_current(): void
    {
        $dept1 = Department::factory()->create(['code' => 'DEPT1']);
        $dept2 = Department::factory()->create(['code' => 'DEPT2']);

        $this->user->givePermissionTo('manage departments');

        $response = $this->actingAs($this->user)
            ->put(route('departments.update', $dept2), [
                'name' => 'Updated Department',
                'code' => 'DEPT1', // Try to use existing code
            ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_destroy_deletes_department(): void
    {
        $department = Department::factory()->create();

        $this->user->givePermissionTo('manage departments');

        $response = $this->actingAs($this->user)
            ->delete(route('departments.destroy', $department));

        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHas('success', 'Department deleted successfully.');

        $this->assertSoftDeleted($department);
    }

    public function test_assign_user_to_department(): void
    {
        $department = Department::factory()->create();
        $userToAssign = User::factory()->create();

        $this->user->givePermissionTo('manage departments');

        $response = $this->actingAs($this->user)
            ->post(route('departments.assign-user', $department), [
                'user_id' => $userToAssign->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'User assigned to department successfully.');

        $this->assertTrue($department->users->contains($userToAssign));
    }

    public function test_assign_user_validates_user_exists(): void
    {
        $department = Department::factory()->create();

        $this->user->givePermissionTo('manage departments');

        $response = $this->actingAs($this->user)
            ->post(route('departments.assign-user', $department), [
                'user_id' => 99999, // Non-existent user
            ]);

        $response->assertSessionHasErrors(['user_id']);
    }

    public function test_remove_user_from_department(): void
    {
        $department = Department::factory()->create();
        $userToRemove = User::factory()->create();

        $department->users()->attach($userToRemove);

        $this->user->givePermissionTo('manage departments');

        $response = $this->actingAs($this->user)
            ->delete(route('departments.remove-user', [$department, $userToRemove]));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'User removed from department successfully.');

        $this->assertFalse($department->users->contains($userToRemove));
    }

    public function test_can_filter_active_departments(): void
    {
        Department::factory()->count(2)->create(['is_active' => true]);
        Department::factory()->create(['is_active' => false]);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.index', ['status' => 'active']));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Departments/Index')
            ->has('departments.data', 2)
        );
    }

    public function test_unauthorized_user_cannot_manage_departments(): void
    {
        $department = Department::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('departments.index'));

        $this->assertContains($response->status(), [403, 302]);
    }

    public function test_department_route_key_uses_uuid(): void
    {
        $department = Department::factory()->create(['code' => 'TEST-DEPT']);
        $department->users()->attach($this->user);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department->uuid));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->where('department.uuid', $department->uuid)
        );
    }

    public function test_code_max_length_validation(): void
    {
        $this->user->givePermissionTo('manage departments');

        $response = $this->actingAs($this->user)
            ->post(route('departments.store'), [
                'name' => 'Test Department',
                'code' => str_repeat('A', 51), // 51 characters, max is 50
            ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_head_of_department_must_exist(): void
    {
        $this->user->givePermissionTo('manage departments');

        $response = $this->actingAs($this->user)
            ->post(route('departments.store'), [
                'name' => 'Test Department',
                'code' => 'TEST',
                'head_of_department' => 99999, // Non-existent user
            ]);

        $response->assertSessionHasErrors(['head_of_department']);
    }

    // ==================== Department Calendar/Appointments Tests ====================

    public function test_show_displays_department_appointments(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->user);
        $organizer = User::factory()->create();

        // Create appointments for this department
        $appointments = Appointment::factory()->count(3)->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $organizer->id,
        ]);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->has('appointments', 3)
            ->has('appointments.0', fn ($assert) => $assert
                ->has('uuid')
                ->has('title')
                ->has('type')
                ->has('status')
                ->has('start_datetime')
                ->has('end_datetime')
                ->has('formatted_time_range')
                ->has('participants_count')
                ->etc()
            )
        );
    }

    public function test_show_appointments_are_ordered_by_start_datetime(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->user);
        $organizer = User::factory()->create();

        // Create appointments with specific dates
        $laterAppointment = Appointment::factory()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $organizer->id,
            'start_datetime' => now()->addDays(10),
            'end_datetime' => now()->addDays(10)->addHour(),
            'title' => 'Later Appointment',
        ]);

        $earlierAppointment = Appointment::factory()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $organizer->id,
            'start_datetime' => now()->addDays(1),
            'end_datetime' => now()->addDays(1)->addHour(),
            'title' => 'Earlier Appointment',
        ]);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->has('appointments', 2)
            ->where('appointments.0.title', 'Earlier Appointment')
            ->where('appointments.1.title', 'Later Appointment')
        );
    }

    public function test_show_appointments_include_organizer_info(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->user);
        $organizer = User::factory()->create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);

        Appointment::factory()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $organizer->id,
        ]);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->has('appointments', 1)
            ->has('appointments.0.organizer', fn ($assert) => $assert
                ->where('id', $organizer->id)
                ->where('uuid', (string) $organizer->uuid)
                ->where('name', 'Jean Dupont')
            )
        );
    }

    public function test_show_appointments_include_participants_count(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->user);
        $organizer = User::factory()->create();
        $participants = User::factory()->count(5)->create();

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $organizer->id,
        ]);

        // Attach participants to the appointment
        $appointment->participants()->attach($participants->pluck('id'));

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->has('appointments', 1)
            ->where('appointments.0.participants_count', 5)
        );
    }

    public function test_show_returns_empty_appointments_array_for_department_without_appointments(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->user);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->has('appointments', 0)
        );
    }

    public function test_show_only_returns_appointments_for_this_department(): void
    {
        $department1 = Department::factory()->create();
        $department1->users()->attach($this->user);
        $department2 = Department::factory()->create();
        $organizer = User::factory()->create();

        // Create appointments for department1
        Appointment::factory()->count(2)->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department1->id,
            'user_id' => $organizer->id,
        ]);

        // Create appointments for department2
        Appointment::factory()->count(3)->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department2->id,
            'user_id' => $organizer->id,
        ]);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department1));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->has('appointments', 2) // Only department1's appointments
        );
    }

    public function test_show_appointments_include_all_statuses(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->user);
        $organizer = User::factory()->create();

        // Create appointments with different statuses
        Appointment::factory()->pending()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $organizer->id,
        ]);

        Appointment::factory()->confirmed()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $organizer->id,
        ]);

        Appointment::factory()->cancelled()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $organizer->id,
        ]);

        Appointment::factory()->completed()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $organizer->id,
        ]);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->has('appointments', 4)
        );
    }

    public function test_show_appointments_include_all_types(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->user);
        $organizer = User::factory()->create();

        // Create appointments with different types
        Appointment::factory()->individual()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $organizer->id,
        ]);

        Appointment::factory()->group()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $organizer->id,
        ]);

        Appointment::factory()->consultation()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $organizer->id,
        ]);

        Appointment::factory()->meeting()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $organizer->id,
        ]);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->has('appointments', 4)
        );
    }

    public function test_show_appointments_datetime_format_is_iso_string(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->user);
        $organizer = User::factory()->create();

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => Department::class,
            'appointmentable_id' => $department->id,
            'user_id' => $organizer->id,
            'start_datetime' => '2024-06-15 10:00:00',
            'end_datetime' => '2024-06-15 11:00:00',
        ]);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->has('appointments', 1)
            // ISO 8601 format check
            ->where('appointments.0.start_datetime', fn ($value) => preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value) === 1
            )
            ->where('appointments.0.end_datetime', fn ($value) => preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value) === 1
            )
        );
    }

    public function test_show_can_manage_is_passed_for_appointments(): void
    {
        $department = Department::factory()->create();

        // User with manage permission
        $this->user->givePermissionTo(['view departments', 'manage departments']);

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->where('canManage', true)
        );
    }

    public function test_show_cannot_manage_for_regular_user(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->user);

        // User with only view permission
        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->where('canManage', false)
        );
    }

    // ==================== Statistics Tests ====================

    public function test_show_includes_statistics_for_department_member(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->user);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->where('canViewStatistics', true)
            ->has('statistics', fn ($assert) => $assert
                ->has('members')
                ->has('workflows')
                ->has('forms')
                ->has('needs')
                ->has('documents')
                ->has('scheduling')
                ->has('todos')
                ->has('task_evolution')
                ->has('tasks_by_member')
                ->has('performance')
            )
        );
    }

    public function test_show_includes_statistics_for_manage_departments_permission(): void
    {
        $department = Department::factory()->create();

        $this->user->givePermissionTo(['view departments', 'manage departments']);

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->where('canViewStatistics', true)
            ->has('statistics')
        );
    }

    public function test_show_includes_statistics_for_head_of_department(): void
    {
        $department = Department::factory()->create([
            'head_of_department' => $this->user->id,
        ]);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->where('canViewStatistics', true)
            ->has('statistics')
        );
    }

    public function test_show_denies_access_for_non_member_without_permission(): void
    {
        $department = Department::factory()->create();

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        // Authorization can return 403 or redirect (302)
        $this->assertContains($response->status(), [403, 302]);
    }

    public function test_statistics_todos_breakdown_is_correct(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->user);

        $this->user->givePermissionTo('view departments');

        // Create todos with different statuses
        \App\Models\Scheduling\DepartmentTodo::factory()->count(3)->create([
            'department_id' => $department->id,
            'created_by' => $this->user->id,
            'status' => \App\Enums\Scheduling\ShiftTaskStatus::COMPLETED,
            'completed_at' => now(),
            'completed_by' => $this->user->id,
        ]);

        \App\Models\Scheduling\DepartmentTodo::factory()->count(2)->create([
            'department_id' => $department->id,
            'created_by' => $this->user->id,
            'status' => \App\Enums\Scheduling\ShiftTaskStatus::IN_PROGRESS,
        ]);

        \App\Models\Scheduling\DepartmentTodo::factory()->create([
            'department_id' => $department->id,
            'created_by' => $this->user->id,
            'status' => \App\Enums\Scheduling\ShiftTaskStatus::TODO,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->has('statistics.todos', fn ($assert) => $assert
                ->where('total', 6)
                ->where('completed', 3)
                ->where('in_progress', 2)
                ->where('pending', 1)
                ->etc()
            )
        );
    }

    public function test_statistics_performance_includes_collective_metrics(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->user);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->has('statistics.performance.collective', fn ($assert) => $assert
                ->has('total_tasks')
                ->has('completed_tasks')
                ->has('completion_rate')
                ->has('overdue_tasks')
                ->has('overdue_rate')
                ->has('velocity_this_month')
                ->has('velocity_last_month')
                ->has('velocity_change')
                ->has('avg_completion_days')
            )
        );
    }

    public function test_statistics_task_evolution_has_all_periods(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->user);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->has('statistics.task_evolution.weekly')
            ->has('statistics.task_evolution.monthly')
            ->has('statistics.task_evolution.quarterly')
            ->has('statistics.task_evolution.semester')
        );
    }

    // ==================== Deputy Tests ====================

    public function test_store_creates_department_with_deputies(): void
    {
        $this->user->givePermissionTo('manage departments');

        $firstDeputy = User::factory()->create();
        $secondDeputy = User::factory()->create();

        $departmentData = [
            'name' => 'Test Department with Deputies',
            'code' => 'TEST-DEP',
            'description' => 'Test department description',
            'head_of_department' => $this->headUser->id,
            'first_deputy_id' => $firstDeputy->id,
            'second_deputy_id' => $secondDeputy->id,
            'budget' => '100000.00',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('departments.store'), $departmentData);

        $response->assertRedirect(route('departments.index'));

        $this->assertDatabaseHas('departments', [
            'name' => 'Test Department with Deputies',
            'code' => 'TEST-DEP',
            'head_of_department' => $this->headUser->id,
            'first_deputy_id' => $firstDeputy->id,
            'second_deputy_id' => $secondDeputy->id,
        ]);
    }

    public function test_store_creates_department_without_deputies(): void
    {
        $this->user->givePermissionTo('manage departments');

        $departmentData = [
            'name' => 'Test Department No Deputies',
            'code' => 'TEST-ND',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('departments.store'), $departmentData);

        $response->assertRedirect(route('departments.index'));

        $this->assertDatabaseHas('departments', [
            'name' => 'Test Department No Deputies',
            'code' => 'TEST-ND',
            'first_deputy_id' => null,
            'second_deputy_id' => null,
        ]);
    }

    public function test_update_modifies_department_deputies(): void
    {
        $department = Department::factory()->create();
        $newFirstDeputy = User::factory()->create();
        $newSecondDeputy = User::factory()->create();

        $this->user->givePermissionTo('manage departments');

        $updateData = [
            'name' => $department->name,
            'code' => $department->code,
            'first_deputy_id' => $newFirstDeputy->id,
            'second_deputy_id' => $newSecondDeputy->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('departments.update', $department), $updateData);

        $response->assertRedirect(route('departments.index'));

        $department->refresh();
        $this->assertEquals($newFirstDeputy->id, $department->first_deputy_id);
        $this->assertEquals($newSecondDeputy->id, $department->second_deputy_id);
    }

    public function test_first_deputy_must_exist(): void
    {
        $this->user->givePermissionTo('manage departments');

        $response = $this->actingAs($this->user)
            ->post(route('departments.store'), [
                'name' => 'Test Department',
                'code' => 'TEST-INV-D',
                'first_deputy_id' => 99999, // Non-existent user
            ]);

        $response->assertSessionHasErrors(['first_deputy_id']);
    }

    public function test_second_deputy_must_exist(): void
    {
        $this->user->givePermissionTo('manage departments');

        $response = $this->actingAs($this->user)
            ->post(route('departments.store'), [
                'name' => 'Test Department',
                'code' => 'TEST-INV-D2',
                'second_deputy_id' => 99999, // Non-existent user
            ]);

        $response->assertSessionHasErrors(['second_deputy_id']);
    }

    public function test_show_displays_deputies_info(): void
    {
        $firstDeputy = User::factory()->create(['first_name' => 'Premier', 'last_name' => 'Adjoint']);
        $secondDeputy = User::factory()->create(['first_name' => 'Deuxième', 'last_name' => 'Adjoint']);

        $department = Department::factory()->create([
            'first_deputy_id' => $firstDeputy->id,
            'second_deputy_id' => $secondDeputy->id,
        ]);

        $this->user->givePermissionTo(['view departments', 'manage departments']);

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->has('department.first_deputy', fn ($assert) => $assert
                ->where('id', $firstDeputy->id)
                ->where('name', 'Premier Adjoint')
                ->etc()
            )
            ->has('department.second_deputy', fn ($assert) => $assert
                ->where('id', $secondDeputy->id)
                ->where('name', 'Deuxième Adjoint')
                ->etc()
            )
        );
    }

    // ==================== Department Accessibility Tests ====================

    public function test_index_returns_is_accessible_for_each_department(): void
    {
        $department = Department::factory()->create();

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Index')
            ->has('departments.data.0.is_accessible')
        );
    }

    public function test_index_member_sees_department_as_accessible(): void
    {
        $department = Department::factory()->create();
        $department->users()->attach($this->user);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Index')
            ->where('departments.data.0.is_accessible', true)
        );
    }

    public function test_index_non_member_sees_department_as_not_accessible(): void
    {
        $department = Department::factory()->create();

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Index')
            ->where('departments.data.0.is_accessible', false)
        );
    }

    public function test_index_manager_sees_all_departments_as_accessible(): void
    {
        $department = Department::factory()->create();

        $this->user->givePermissionTo(['view departments', 'manage departments']);

        $response = $this->actingAs($this->user)
            ->get(route('departments.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Index')
            ->where('departments.data.0.is_accessible', true)
        );
    }

    public function test_index_head_of_department_sees_department_as_accessible(): void
    {
        $department = Department::factory()->create([
            'head_of_department' => $this->user->id,
        ]);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Index')
            ->where('departments.data.0.is_accessible', true)
        );
    }

    public function test_index_first_deputy_sees_department_as_accessible(): void
    {
        $department = Department::factory()->create([
            'first_deputy_id' => $this->user->id,
        ]);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Index')
            ->where('departments.data.0.is_accessible', true)
        );
    }

    public function test_index_second_deputy_sees_department_as_accessible(): void
    {
        $department = Department::factory()->create([
            'second_deputy_id' => $this->user->id,
        ]);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Index')
            ->where('departments.data.0.is_accessible', true)
        );
    }

    public function test_show_allows_access_for_head_of_department(): void
    {
        $department = Department::factory()->create([
            'head_of_department' => $this->user->id,
        ]);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->where('department.id', $department->id)
        );
    }

    public function test_show_allows_access_for_first_deputy(): void
    {
        $department = Department::factory()->create([
            'first_deputy_id' => $this->user->id,
        ]);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->where('department.id', $department->id)
        );
    }

    public function test_show_allows_access_for_second_deputy(): void
    {
        $department = Department::factory()->create([
            'second_deputy_id' => $this->user->id,
        ]);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->where('department.id', $department->id)
        );
    }

    public function test_index_shows_mixed_accessibility_for_multiple_departments(): void
    {
        // Create department where user is member
        $memberDepartment = Department::factory()->create(['name' => 'AAA Member Dept']);
        $memberDepartment->users()->attach($this->user);

        // Create department where user is not member
        $nonMemberDepartment = Department::factory()->create(['name' => 'BBB Non-Member Dept']);

        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Index')
            ->has('departments.data', 2)
            // First department (AAA Member Dept) should be accessible
            ->where('departments.data.0.is_accessible', true)
            // Second department (BBB Non-Member Dept) should not be accessible
            ->where('departments.data.1.is_accessible', false)
        );
    }

    // ==================== Access All Departments Permission Tests ====================

    public function test_index_user_with_access_all_departments_permission_sees_all_as_accessible(): void
    {
        // Create departments where user is NOT a member
        Department::factory()->create(['name' => 'AAA Dept']);
        Department::factory()->create(['name' => 'BBB Dept']);

        $this->user->givePermissionTo(['view departments', 'access all departments']);

        $response = $this->actingAs($this->user)
            ->get(route('departments.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Index')
            ->has('departments.data', 2)
            ->where('departments.data.0.is_accessible', true)
            ->where('departments.data.1.is_accessible', true)
        );
    }

    public function test_show_allows_access_for_user_with_access_all_departments_permission(): void
    {
        $department = Department::factory()->create();

        // User is not a member but has 'access all departments' permission
        $this->user->givePermissionTo(['view departments', 'access all departments']);

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->where('department.id', $department->id)
        );
    }

    public function test_user_with_access_all_departments_can_view_statistics(): void
    {
        $department = Department::factory()->create();

        $this->user->givePermissionTo(['view departments', 'access all departments', 'view department statistics']);

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Departments/Show')
            ->where('canViewStatistics', true)
            ->has('statistics')
        );
    }

    public function test_user_without_access_all_departments_but_with_view_departments_cannot_access_show(): void
    {
        $department = Department::factory()->create();

        // User only has 'view departments' but not 'access all departments' or 'manage departments'
        $this->user->givePermissionTo('view departments');

        $response = $this->actingAs($this->user)
            ->get(route('departments.show', $department));

        // Should be denied access (403 or redirect)
        $this->assertContains($response->status(), [403, 302]);
    }
}
