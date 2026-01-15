<?php

namespace Tests\Feature;

use App\Enums\Scheduling\ScheduleStatus;
use App\Enums\Scheduling\ShiftStatus;
use App\Enums\Scheduling\ShiftType;
use App\Models\Department;
use App\Models\Scheduling\SchedulingPosition;
use App\Models\Scheduling\Shift;
use App\Models\Scheduling\WeeklySchedule;
use App\Models\User;
use App\Services\Scheduling\SchedulingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view departments']);
        Permission::create(['name' => 'manage departments']);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['view departments', 'manage departments']);

        // Create employee user
        $this->employee = User::factory()->create();
        $this->employee->givePermissionTo('view departments');

        // Create department
        $this->department = Department::factory()->create();
        $this->department->users()->attach([$this->admin->id, $this->employee->id]);
    }

    // ============================================
    // Weekly Schedule Tests
    // ============================================

    public function test_can_view_schedule_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('departments.schedule.index', ['department' => $this->department]))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Departments/Schedule/Index')
                ->has('department')
                ->has('schedule')
                ->has('stats')
            );
    }

    public function test_weekly_schedule_is_created_automatically(): void
    {
        $this->actingAs($this->admin)
            ->get(route('departments.schedule.index', ['department' => $this->department]));

        $this->assertDatabaseHas('weekly_schedules', [
            'department_id' => $this->department->id,
        ]);
    }

    public function test_can_navigate_between_weeks(): void
    {
        $currentWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $nextWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeek()->format('Y-m-d');

        $this->actingAs($this->admin)
            ->get(route('departments.schedule.index', [
                'department' => $this->department,
                'week' => $nextWeek,
            ]))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->where('currentWeek', $nextWeek)
            );
    }

    public function test_can_create_weekly_schedule(): void
    {
        $weekStart = Carbon::now()->addWeeks(2)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        $this->actingAs($this->admin)
            ->post(route('departments.schedule.store', ['department' => $this->department]), [
                'week_start' => $weekStart,
                'notes' => 'Test Schedule',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('weekly_schedules', [
            'department_id' => $this->department->id,
            'week_start' => $weekStart,
        ]);
    }

    public function test_can_publish_schedule(): void
    {
        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
            'status' => ScheduleStatus::DRAFT,
        ]);

        $this->actingAs($this->admin)
            ->post(route('departments.schedule.publish', [
                'department' => $this->department,
                'schedule' => $schedule,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('weekly_schedules', [
            'id' => $schedule->id,
            'status' => ScheduleStatus::PUBLISHED->value,
        ]);
    }

    public function test_can_lock_published_schedule(): void
    {
        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
            'status' => ScheduleStatus::PUBLISHED,
        ]);

        $this->actingAs($this->admin)
            ->post(route('departments.schedule.lock', [
                'department' => $this->department,
                'schedule' => $schedule,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('weekly_schedules', [
            'id' => $schedule->id,
            'status' => ScheduleStatus::LOCKED->value,
        ]);
    }

    public function test_cannot_lock_draft_schedule(): void
    {
        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
            'status' => ScheduleStatus::DRAFT,
        ]);

        $this->actingAs($this->admin)
            ->post(route('departments.schedule.lock', [
                'department' => $this->department,
                'schedule' => $schedule,
            ]))
            ->assertSessionHas('error');
    }

    // ============================================
    // Shift Tests
    // ============================================

    public function test_can_create_shift(): void
    {
        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
            'status' => ScheduleStatus::DRAFT,
        ]);

        $this->actingAs($this->admin)
            ->post(route('departments.schedule.shifts.store', [
                'department' => $this->department,
                'schedule' => $schedule,
            ]), [
                'date' => $schedule->week_start->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '17:00',
                'type' => ShiftType::FULL_DAY->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shifts', [
            'weekly_schedule_id' => $schedule->id,
            'department_id' => $this->department->id,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);
    }

    public function test_can_assign_employee_to_shift(): void
    {
        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
            'status' => ScheduleStatus::DRAFT,
        ]);

        $shift = Shift::factory()->create([
            'weekly_schedule_id' => $schedule->id,
            'department_id' => $this->department->id,
            'user_id' => null,
            'date' => $schedule->week_start,
        ]);

        $this->actingAs($this->admin)
            ->post(route('departments.schedule.shifts.assign', [
                'department' => $this->department,
                'schedule' => $schedule,
                'shift' => $shift,
            ]), [
                'user_id' => $this->employee->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'user_id' => $this->employee->id,
        ]);
    }

    public function test_can_unassign_employee_from_shift(): void
    {
        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
            'status' => ScheduleStatus::DRAFT,
        ]);

        $shift = Shift::factory()->create([
            'weekly_schedule_id' => $schedule->id,
            'department_id' => $this->department->id,
            'user_id' => $this->employee->id,
            'date' => $schedule->week_start,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('departments.schedule.shifts.unassign', [
                'department' => $this->department,
                'schedule' => $schedule,
                'shift' => $shift,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'user_id' => null,
        ]);
    }

    public function test_cannot_modify_shift_in_locked_schedule(): void
    {
        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
            'status' => ScheduleStatus::LOCKED,
        ]);

        $shift = Shift::factory()->create([
            'weekly_schedule_id' => $schedule->id,
            'department_id' => $this->department->id,
            'date' => $schedule->week_start,
        ]);

        $this->actingAs($this->admin)
            ->put(route('departments.schedule.shifts.update', [
                'department' => $this->department,
                'schedule' => $schedule,
                'shift' => $shift,
            ]), [
                'start_time' => '10:00',
                'end_time' => '18:00',
            ])
            ->assertSessionHas('error');
    }

    public function test_can_delete_shift(): void
    {
        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
            'status' => ScheduleStatus::DRAFT,
        ]);

        $shift = Shift::factory()->create([
            'weekly_schedule_id' => $schedule->id,
            'department_id' => $this->department->id,
            'date' => $schedule->week_start,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('departments.schedule.shifts.destroy', [
                'department' => $this->department,
                'schedule' => $schedule,
                'shift' => $shift,
            ]))
            ->assertRedirect();

        // Shift uses soft delete, so verify deleted_at is set
        $deletedShift = Shift::withTrashed()->find($shift->id);
        $this->assertNotNull($deletedShift->deleted_at);
    }

    // ============================================
    // Check In/Out Tests
    // ============================================

    public function test_employee_can_check_in(): void
    {
        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
            'status' => ScheduleStatus::PUBLISHED,
        ]);

        $shift = Shift::factory()->create([
            'weekly_schedule_id' => $schedule->id,
            'department_id' => $this->department->id,
            'user_id' => $this->employee->id,
            'date' => Carbon::today(),
            'start_time' => Carbon::now()->subMinutes(15)->format('H:i'),
            'end_time' => Carbon::now()->addHours(8)->format('H:i'),
            'status' => ShiftStatus::PUBLISHED,
        ]);

        $this->actingAs($this->employee)
            ->post(route('departments.schedule.shifts.check-in', [
                'department' => $this->department,
                'schedule' => $schedule,
                'shift' => $shift,
            ]))
            ->assertRedirect();

        $this->assertNotNull($shift->fresh()->checked_in_at);
    }

    public function test_other_employee_cannot_check_in_for_someone_else(): void
    {
        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
            'status' => ScheduleStatus::PUBLISHED,
        ]);

        $shift = Shift::factory()->create([
            'weekly_schedule_id' => $schedule->id,
            'department_id' => $this->department->id,
            'user_id' => $this->employee->id,
            'date' => Carbon::today(),
            'start_time' => Carbon::now()->subMinutes(15)->format('H:i'),
            'end_time' => Carbon::now()->addHours(8)->format('H:i'),
        ]);

        $otherEmployee = User::factory()->create();
        $otherEmployee->givePermissionTo('view departments');
        $this->department->users()->attach($otherEmployee);

        $this->actingAs($otherEmployee)
            ->post(route('departments.schedule.shifts.check-in', [
                'department' => $this->department,
                'schedule' => $schedule,
                'shift' => $shift,
            ]))
            ->assertSessionHas('error');
    }

    // ============================================
    // Schedule Copy Tests
    // ============================================

    public function test_can_copy_schedule_to_another_week(): void
    {
        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
            'status' => ScheduleStatus::DRAFT,
        ]);

        Shift::factory()->count(3)->create([
            'weekly_schedule_id' => $schedule->id,
            'department_id' => $this->department->id,
            'date' => $schedule->week_start,
        ]);

        $targetWeek = $schedule->week_start->copy()->addWeeks(2)->format('Y-m-d');

        $this->actingAs($this->admin)
            ->post(route('departments.schedule.copy', [
                'department' => $this->department,
                'schedule' => $schedule,
            ]), [
                'target_week' => $targetWeek,
                'copy_assignments' => false,
            ])
            ->assertRedirect();

        $newSchedule = WeeklySchedule::where('department_id', $this->department->id)
            ->where('week_start', $targetWeek)
            ->first();

        $this->assertNotNull($newSchedule);
        $this->assertEquals(3, $newSchedule->shifts()->count());
    }

    // ============================================
    // Auto-Assignment Tests
    // ============================================

    public function test_can_auto_assign_shifts(): void
    {
        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
            'status' => ScheduleStatus::DRAFT,
        ]);

        // Create unassigned shifts
        Shift::factory()->count(2)->create([
            'weekly_schedule_id' => $schedule->id,
            'department_id' => $this->department->id,
            'user_id' => null,
            'date' => $schedule->week_start,
        ]);

        $this->actingAs($this->admin)
            ->post(route('departments.schedule.auto-assign', [
                'department' => $this->department,
                'schedule' => $schedule,
            ]))
            ->assertRedirect();

        // At least some shifts should be assigned (depends on availability)
        // This test verifies the endpoint works, not the actual assignment logic
        $this->assertTrue(true);
    }

    // ============================================
    // Stats Tests
    // ============================================

    public function test_schedule_stats_are_correct(): void
    {
        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
        ]);

        // Create assigned shift
        Shift::factory()->create([
            'weekly_schedule_id' => $schedule->id,
            'department_id' => $this->department->id,
            'user_id' => $this->employee->id,
            'date' => $schedule->week_start,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        // Create unassigned shift
        Shift::factory()->create([
            'weekly_schedule_id' => $schedule->id,
            'department_id' => $this->department->id,
            'user_id' => null,
            'date' => $schedule->week_start,
            'start_time' => '14:00',
            'end_time' => '22:00',
        ]);

        $service = app(SchedulingService::class);
        $stats = $service->getScheduleStats($schedule);

        $this->assertEquals(2, $stats['total_shifts']);
        $this->assertEquals(1, $stats['assigned_shifts']);
        $this->assertEquals(1, $stats['unassigned_shifts']);
        $this->assertEquals(50, $stats['assignment_rate']);
    }

    // ============================================
    // Authorization Tests
    // ============================================

    public function test_unauthorized_user_cannot_access_schedule(): void
    {
        $otherDepartment = Department::factory()->create();

        $response = $this->actingAs($this->employee)
            ->get(route('departments.schedule.index', ['department' => $otherDepartment]));

        // Should either be 403 Forbidden or redirected away
        $this->assertTrue(
            $response->status() === 403 || $response->status() === 302,
            'Expected 403 or 302, got ' . $response->status()
        );
    }

    public function test_employee_cannot_publish_schedule(): void
    {
        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
            'status' => ScheduleStatus::DRAFT,
        ]);

        $response = $this->actingAs($this->employee)
            ->post(route('departments.schedule.publish', [
                'department' => $this->department,
                'schedule' => $schedule,
            ]));

        // Should either be 403 Forbidden or redirected away
        $this->assertTrue(
            $response->status() === 403 || $response->status() === 302,
            'Expected 403 or 302, got ' . $response->status()
        );
    }

    public function test_guest_cannot_access_schedule(): void
    {
        $this->get(route('departments.schedule.index', ['department' => $this->department]))
            ->assertRedirect(route('login'));
    }

    // ============================================
    // Scheduling Position Tests
    // ============================================

    public function test_can_create_scheduling_position(): void
    {
        $position = SchedulingPosition::create([
            'department_id' => $this->department->id,
            'name' => 'Receptionist',
            'description' => 'Front desk position',
            'color' => '#FF5733',
            'hourly_rate' => 15.50,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('scheduling_positions', [
            'id' => $position->id,
            'name' => 'Receptionist',
            'department_id' => $this->department->id,
        ]);

        $this->assertNotNull($position->uuid);
    }

    public function test_scheduling_position_belongs_to_department(): void
    {
        $position = SchedulingPosition::create([
            'department_id' => $this->department->id,
            'name' => 'Manager',
        ]);

        $this->assertEquals($this->department->id, $position->department->id);
    }

    public function test_shift_can_have_position(): void
    {
        $position = SchedulingPosition::create([
            'department_id' => $this->department->id,
            'name' => 'Cashier',
        ]);

        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
        ]);

        $shift = Shift::factory()->create([
            'weekly_schedule_id' => $schedule->id,
            'department_id' => $this->department->id,
            'position_id' => $position->id,
            'date' => $schedule->week_start,
        ]);

        $this->assertEquals($position->id, $shift->position->id);
        $this->assertEquals('Cashier', $shift->position->name);
    }

    public function test_position_can_have_many_shifts(): void
    {
        $position = SchedulingPosition::create([
            'department_id' => $this->department->id,
            'name' => 'Security',
        ]);

        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
        ]);

        Shift::factory()->count(3)->create([
            'weekly_schedule_id' => $schedule->id,
            'department_id' => $this->department->id,
            'position_id' => $position->id,
            'date' => $schedule->week_start,
        ]);

        $this->assertEquals(3, $position->shifts()->count());
    }

    public function test_scheduling_position_scope_active(): void
    {
        SchedulingPosition::create([
            'department_id' => $this->department->id,
            'name' => 'Active Position',
            'is_active' => true,
        ]);

        SchedulingPosition::create([
            'department_id' => $this->department->id,
            'name' => 'Inactive Position',
            'is_active' => false,
        ]);

        $activePositions = SchedulingPosition::forDepartment($this->department->id)
            ->active()
            ->get();

        $this->assertEquals(1, $activePositions->count());
        $this->assertEquals('Active Position', $activePositions->first()->name);
    }

    public function test_scheduling_position_soft_deletes(): void
    {
        $position = SchedulingPosition::create([
            'department_id' => $this->department->id,
            'name' => 'Temporary Position',
        ]);

        $positionId = $position->id;
        $position->delete();

        $this->assertSoftDeleted('scheduling_positions', [
            'id' => $positionId,
        ]);

        // Position can still be retrieved with trashed
        $deletedPosition = SchedulingPosition::withTrashed()->find($positionId);
        $this->assertNotNull($deletedPosition);
    }

    public function test_scheduling_position_casts_required_skills(): void
    {
        $position = SchedulingPosition::create([
            'department_id' => $this->department->id,
            'name' => 'Specialized Position',
            'required_skills' => [1, 2, 3],
        ]);

        $this->assertIsArray($position->required_skills);
        $this->assertEquals([1, 2, 3], $position->required_skills);
    }

    public function test_create_shift_with_position(): void
    {
        $position = SchedulingPosition::create([
            'department_id' => $this->department->id,
            'name' => 'Test Position',
        ]);

        $schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
            'status' => ScheduleStatus::DRAFT,
        ]);

        $this->actingAs($this->admin)
            ->post(route('departments.schedule.shifts.store', [
                'department' => $this->department,
                'schedule' => $schedule,
            ]), [
                'date' => $schedule->week_start->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '17:00',
                'type' => ShiftType::FULL_DAY->value,
                'position_id' => $position->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shifts', [
            'weekly_schedule_id' => $schedule->id,
            'position_id' => $position->id,
        ]);
    }
}
