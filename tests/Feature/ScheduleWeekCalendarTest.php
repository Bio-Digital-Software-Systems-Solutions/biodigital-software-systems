<?php

namespace Tests\Feature;

use App\Enums\Scheduling\ShiftStatus;
use App\Models\Department;
use App\Models\Scheduling\Shift;
use App\Models\Scheduling\WeeklySchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScheduleWeekCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $member;

    protected Department $department;

    protected WeeklySchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'view departments']);
        Permission::firstOrCreate(['name' => 'manage departments']);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(['view departments', 'manage departments']);

        $memberRole = Role::firstOrCreate(['name' => 'member']);
        $memberRole->givePermissionTo(['view departments']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->member = User::factory()->create();
        $this->member->assignRole('member');

        $this->department = Department::factory()->create();
        $this->department->users()->attach([$this->admin->id, $this->member->id]);

        $this->schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
            'week_start' => '2026-03-09',
            'week_end' => '2026-03-15',
        ]);
    }

    private function createShift(array $attributes = []): Shift
    {
        return Shift::factory()->create(array_merge([
            'weekly_schedule_id' => $this->schedule->id,
            'department_id' => $this->department->id,
        ], $attributes));
    }

    // ==========================================
    // SCHEDULE INDEX PAGE RENDERING
    // ==========================================

    public function test_schedule_index_page_loads_with_shifts_and_users(): void
    {
        $shift = $this->createShift([
            'date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $assignee = User::factory()->create();
        $shift->users()->attach($assignee->id, ['time_slot' => '08:00']);

        $response = $this->actingAs($this->admin)->get(
            "/departments/{$this->department->uuid}/schedule?week=2026-03-09"
        );

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Departments/Schedule/Index')
            ->has('schedule.shifts')
            ->has('members')
        );
    }

    public function test_schedule_index_returns_members_with_id(): void
    {
        $response = $this->actingAs($this->admin)->get(
            "/departments/{$this->department->uuid}/schedule?week=2026-03-09"
        );

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Departments/Schedule/Index')
            ->has('members', 2)
            ->has('members.0.id')
            ->has('members.0.uuid')
            ->has('members.0.name')
            ->has('members.0.email')
            ->has('members.1.id')
        );
    }

    public function test_schedule_index_shifts_include_users_relation(): void
    {
        $shift = $this->createShift([
            'date' => '2026-03-10',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        $user1 = User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Smith']);
        $user2 = User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Jones']);

        $shift->users()->attach($user1->id, ['time_slot' => '09:00']);
        $shift->users()->attach($user2->id, ['time_slot' => '10:00']);

        $response = $this->actingAs($this->admin)->get(
            "/departments/{$this->department->uuid}/schedule?week=2026-03-09"
        );

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Departments/Schedule/Index')
            ->has('schedule.shifts.0.users', 2)
        );
    }

    // ==========================================
    // ADD USER VIA CALENDAR (SLOT ASSIGNMENT)
    // ==========================================

    public function test_add_user_to_shift_slot_from_schedule_context(): void
    {
        $shift = $this->createShift([
            'date' => '2026-03-11',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $assignee = User::factory()->create();

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/add-user",
            ['user_id' => $assignee->id, 'time_slot' => '10:00']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('shift_user', [
            'shift_id' => $shift->id,
            'user_id' => $assignee->id,
            'time_slot' => '10:00',
        ]);
    }

    public function test_add_multiple_users_to_same_slot(): void
    {
        $shift = $this->createShift([
            'date' => '2026-03-11',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/add-user",
            ['user_id' => $user1->id, 'time_slot' => '10:00']
        );

        $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/add-user",
            ['user_id' => $user2->id, 'time_slot' => '10:00']
        );

        $this->assertDatabaseHas('shift_user', [
            'shift_id' => $shift->id,
            'user_id' => $user1->id,
            'time_slot' => '10:00',
        ]);
        $this->assertDatabaseHas('shift_user', [
            'shift_id' => $shift->id,
            'user_id' => $user2->id,
            'time_slot' => '10:00',
        ]);
    }

    public function test_remove_user_from_slot_via_schedule_context(): void
    {
        $shift = $this->createShift([
            'date' => '2026-03-12',
            'start_time' => '14:00:00',
            'end_time' => '22:00:00',
        ]);

        $assignee = User::factory()->create();
        $shift->users()->attach($assignee->id, ['time_slot' => '14:00']);
        $shift->users()->attach($assignee->id, ['time_slot' => '16:00']);

        $response = $this->actingAs($this->admin)->delete(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/remove-user",
            ['user_id' => $assignee->id, 'time_slot' => '14:00']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('shift_user', [
            'shift_id' => $shift->id,
            'user_id' => $assignee->id,
            'time_slot' => '14:00',
        ]);
        $this->assertDatabaseHas('shift_user', [
            'shift_id' => $shift->id,
            'user_id' => $assignee->id,
            'time_slot' => '16:00',
        ]);
    }

    // ==========================================
    // AUTHORIZATION TESTS
    // ==========================================

    public function test_member_cannot_add_user_to_slot(): void
    {
        $shift = $this->createShift([
            'date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);
        $assignee = User::factory()->create();

        $response = $this->actingAs($this->member)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/add-user",
            ['user_id' => $assignee->id, 'time_slot' => '09:00']
        );

        $this->assertTrue($response->isForbidden() || $response->isRedirect());
        $this->assertDatabaseMissing('shift_user', [
            'shift_id' => $shift->id,
            'user_id' => $assignee->id,
        ]);
    }

    public function test_member_cannot_remove_user_from_slot(): void
    {
        $shift = $this->createShift([
            'date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);
        $assignee = User::factory()->create();
        $shift->users()->attach($assignee->id, ['time_slot' => '09:00']);

        $response = $this->actingAs($this->member)->delete(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/remove-user",
            ['user_id' => $assignee->id, 'time_slot' => '09:00']
        );

        $this->assertTrue($response->isForbidden() || $response->isRedirect());
        $this->assertDatabaseHas('shift_user', [
            'shift_id' => $shift->id,
            'user_id' => $assignee->id,
            'time_slot' => '09:00',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_schedule(): void
    {
        $response = $this->get(
            "/departments/{$this->department->uuid}/schedule?week=2026-03-09"
        );

        $response->assertRedirect('/login');
    }

    // ==========================================
    // VALIDATION TESTS
    // ==========================================

    public function test_add_user_requires_valid_user_id(): void
    {
        $shift = $this->createShift([
            'date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/add-user",
            ['user_id' => 99999, 'time_slot' => '09:00']
        );

        $response->assertSessionHasErrors('user_id');
    }

    public function test_add_user_requires_time_slot(): void
    {
        $shift = $this->createShift([
            'date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $assignee = User::factory()->create();

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/add-user",
            ['user_id' => $assignee->id]
        );

        $response->assertSessionHasErrors('time_slot');
    }

    public function test_add_user_time_slot_must_be_5_chars(): void
    {
        $shift = $this->createShift([
            'date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $assignee = User::factory()->create();

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/add-user",
            ['user_id' => $assignee->id, 'time_slot' => '8:00:00']
        );

        $response->assertSessionHasErrors('time_slot');
    }

    // ==========================================
    // DUPLICATE PREVENTION TESTS
    // ==========================================

    public function test_cannot_add_same_user_to_same_slot_twice(): void
    {
        $shift = $this->createShift([
            'date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $assignee = User::factory()->create();
        $shift->users()->attach($assignee->id, ['time_slot' => '10:00']);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/add-user",
            ['user_id' => $assignee->id, 'time_slot' => '10:00']
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(1, $shift->users()->wherePivot('time_slot', '10:00')->count());
    }

    public function test_cannot_add_user_to_identical_shift_same_day(): void
    {
        $assignee = User::factory()->create(['first_name' => 'Test', 'last_name' => 'User']);

        $shiftA = $this->createShift([
            'date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'status' => ShiftStatus::PUBLISHED,
        ]);
        $shiftB = $this->createShift([
            'date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'status' => ShiftStatus::PUBLISHED,
        ]);

        $shiftA->users()->attach($assignee->id, ['time_slot' => '09:00']);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shiftB->uuid}/add-user",
            ['user_id' => $assignee->id, 'time_slot' => '09:00']
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_user_can_be_added_to_different_shifts_on_same_day(): void
    {
        $assignee = User::factory()->create();

        $morningShift = $this->createShift([
            'date' => '2026-03-10',
            'start_time' => '06:00:00',
            'end_time' => '14:00:00',
        ]);
        $eveningShift = $this->createShift([
            'date' => '2026-03-10',
            'start_time' => '14:00:00',
            'end_time' => '22:00:00',
        ]);

        $morningShift->users()->attach($assignee->id, ['time_slot' => '08:00']);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$eveningShift->uuid}/add-user",
            ['user_id' => $assignee->id, 'time_slot' => '16:00']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // ==========================================
    // MULTIPLE SHIFTS IN WEEK VIEW
    // ==========================================

    public function test_schedule_index_returns_all_week_shifts_with_assignments(): void
    {
        $shift1 = $this->createShift([
            'date' => '2026-03-09',
            'start_time' => '06:00:00',
            'end_time' => '14:00:00',
        ]);
        $shift2 = $this->createShift([
            'date' => '2026-03-11',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);
        $shift3 = $this->createShift([
            'date' => '2026-03-13',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'type' => 'night',
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $shift1->users()->attach($user1->id, ['time_slot' => '08:00']);
        $shift2->users()->attach($user2->id, ['time_slot' => '10:00']);
        $shift3->users()->attach($user1->id, ['time_slot' => '00:00']);

        $response = $this->actingAs($this->admin)->get(
            "/departments/{$this->department->uuid}/schedule?week=2026-03-09"
        );

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Departments/Schedule/Index')
            ->has('schedule.shifts', 3)
        );
    }

    public function test_schedule_index_shifts_have_pivot_time_slot(): void
    {
        $shift = $this->createShift([
            'date' => '2026-03-10',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        $user = User::factory()->create();
        $shift->users()->attach($user->id, ['time_slot' => '12:00']);

        $response = $this->actingAs($this->admin)->get(
            "/departments/{$this->department->uuid}/schedule?week=2026-03-09"
        );

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Departments/Schedule/Index')
            ->has('schedule.shifts.0.users.0.pivot')
        );
    }
}
