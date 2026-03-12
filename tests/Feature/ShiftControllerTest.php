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

class ShiftControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $member;

    protected Department $department;

    protected WeeklySchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions and roles
        Permission::firstOrCreate(['name' => 'view departments']);
        Permission::firstOrCreate(['name' => 'manage departments']);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(['view departments', 'manage departments']);

        $memberRole = Role::firstOrCreate(['name' => 'member']);
        $memberRole->givePermissionTo(['view departments']);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->member = User::factory()->create();
        $this->member->assignRole('member');

        // Create department and schedule
        $this->department = Department::factory()->create();
        $this->schedule = WeeklySchedule::factory()->create([
            'department_id' => $this->department->id,
        ]);
    }

    /**
     * Helper to create a shift with default department and schedule
     */
    private function createShift(array $attributes = []): Shift
    {
        return Shift::factory()->create(array_merge([
            'weekly_schedule_id' => $this->schedule->id,
            'department_id' => $this->department->id,
        ], $attributes));
    }

    // ==========================================
    // CANCEL SHIFT TESTS
    // ==========================================

    public function test_user_with_permission_can_cancel_draft_shift(): void
    {
        $shift = $this->createShift(['status' => ShiftStatus::DRAFT]);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/cancel",
            ['cancellation_reason' => 'Test cancellation']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $shift->refresh();
        $this->assertEquals(ShiftStatus::CANCELLED, $shift->status);
        $this->assertStringContainsString('Test cancellation', $shift->notes ?? '');
    }

    public function test_user_with_permission_can_cancel_published_shift(): void
    {
        $shift = $this->createShift(['status' => ShiftStatus::PUBLISHED]);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/cancel",
            ['cancellation_reason' => 'Employee sick']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $shift->refresh();
        $this->assertEquals(ShiftStatus::CANCELLED, $shift->status);
    }

    public function test_user_with_permission_can_cancel_confirmed_shift(): void
    {
        $shift = $this->createShift(['status' => ShiftStatus::CONFIRMED]);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/cancel"
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $shift->refresh();
        $this->assertEquals(ShiftStatus::CANCELLED, $shift->status);
    }

    public function test_user_with_permission_can_cancel_in_progress_shift(): void
    {
        $shift = $this->createShift(['status' => ShiftStatus::IN_PROGRESS]);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/cancel",
            ['cancellation_reason' => 'Emergency situation']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $shift->refresh();
        $this->assertEquals(ShiftStatus::CANCELLED, $shift->status);
    }

    public function test_cannot_cancel_already_cancelled_shift(): void
    {
        $shift = $this->createShift(['status' => ShiftStatus::CANCELLED]);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/cancel"
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $shift->refresh();
        $this->assertEquals(ShiftStatus::CANCELLED, $shift->status);
    }

    public function test_cannot_cancel_completed_shift(): void
    {
        $shift = $this->createShift(['status' => ShiftStatus::COMPLETED]);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/cancel"
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $shift->refresh();
        $this->assertEquals(ShiftStatus::COMPLETED, $shift->status);
    }

    public function test_cannot_cancel_no_show_shift(): void
    {
        $shift = $this->createShift(['status' => ShiftStatus::NO_SHOW]);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/cancel"
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $shift->refresh();
        $this->assertEquals(ShiftStatus::NO_SHOW, $shift->status);
    }

    public function test_user_without_permission_cannot_cancel_shift(): void
    {
        $shift = $this->createShift(['status' => ShiftStatus::DRAFT]);

        $response = $this->actingAs($this->member)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/cancel"
        );

        $this->assertTrue($response->isForbidden() || $response->isRedirect());

        $shift->refresh();
        $this->assertEquals(ShiftStatus::DRAFT, $shift->status);
    }

    public function test_unauthenticated_user_cannot_cancel_shift(): void
    {
        $shift = $this->createShift(['status' => ShiftStatus::DRAFT]);

        $response = $this->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/cancel"
        );

        $response->assertRedirect('/login');

        $shift->refresh();
        $this->assertEquals(ShiftStatus::DRAFT, $shift->status);
    }

    public function test_cancel_shift_without_reason_works(): void
    {
        $shift = $this->createShift([
            'status' => ShiftStatus::DRAFT,
            'notes' => null,
        ]);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/cancel"
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $shift->refresh();
        $this->assertEquals(ShiftStatus::CANCELLED, $shift->status);
        $this->assertNull($shift->notes);
    }

    public function test_cancel_shift_appends_reason_to_existing_notes(): void
    {
        $shift = $this->createShift([
            'status' => ShiftStatus::DRAFT,
            'notes' => 'Original notes',
        ]);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/cancel",
            ['cancellation_reason' => 'New cancellation reason']
        );

        $response->assertRedirect();

        $shift->refresh();
        $this->assertStringContainsString('Original notes', $shift->notes);
        $this->assertStringContainsString('New cancellation reason', $shift->notes);
    }

    public function test_cancel_validates_reason_max_length(): void
    {
        $shift = $this->createShift(['status' => ShiftStatus::DRAFT]);

        $longReason = str_repeat('a', 501);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/cancel",
            ['cancellation_reason' => $longReason]
        );

        $response->assertSessionHasErrors('cancellation_reason');

        $shift->refresh();
        $this->assertEquals(ShiftStatus::DRAFT, $shift->status);
    }

    public function test_cancel_redirects_to_shift_show_page(): void
    {
        $shift = $this->createShift(['status' => ShiftStatus::DRAFT]);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/cancel"
        );

        $response->assertRedirect(route('departments.schedule.shifts.show', [
            'department' => $this->department->uuid,
            'schedule' => $this->schedule->uuid,
            'shift' => $shift->uuid,
        ]));
    }

    // ==========================================
    // ADDITIONAL SHIFT CONTROLLER TESTS
    // ==========================================

    public function test_user_can_view_shift_details(): void
    {
        $shift = $this->createShift();

        $response = $this->actingAs($this->member)->get(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}"
        );

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Departments/Schedule/Shifts/Show'));
    }

    public function test_user_with_permission_can_access_edit_page(): void
    {
        $shift = $this->createShift();

        $response = $this->actingAs($this->admin)->get(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}/edit"
        );

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Departments/Schedule/Shifts/Edit'));
    }

    public function test_user_with_permission_can_delete_shift(): void
    {
        $shift = $this->createShift();

        $response = $this->actingAs($this->admin)->delete(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}"
        );

        $response->assertRedirect();
        $this->assertSoftDeleted('shifts', ['id' => $shift->id]);
    }

    public function test_user_without_permission_cannot_delete_shift(): void
    {
        $shift = $this->createShift();

        $response = $this->actingAs($this->member)->delete(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}"
        );

        $this->assertTrue($response->isForbidden() || $response->isRedirect());
        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'deleted_at' => null]);
    }

    public function test_shift_status_transitions_are_validated(): void
    {
        // Test that status can only transition to allowed states
        $shift = $this->createShift(['status' => ShiftStatus::COMPLETED]);

        // Completed shifts cannot be cancelled via normal flow
        $this->assertFalse($shift->status->canTransitionTo(ShiftStatus::CANCELLED));

        // Draft shifts can be cancelled
        $draftShift = $this->createShift(['status' => ShiftStatus::DRAFT]);

        $this->assertTrue($draftShift->status->canTransitionTo(ShiftStatus::CANCELLED));
    }

    public function test_cancelled_shift_can_be_reverted_to_draft(): void
    {
        // According to the enum, cancelled shifts can transition back to draft
        $shift = $this->createShift(['status' => ShiftStatus::CANCELLED]);

        $this->assertTrue($shift->status->canTransitionTo(ShiftStatus::DRAFT));
    }

    // ==========================================
    // SHIFT SHOW PAGE — WEEK CALENDAR DATA TESTS
    // ==========================================

    public function test_show_page_returns_shift_with_date_for_week_calendar(): void
    {
        $shift = $this->createShift([
            'date' => '2026-03-12',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $response = $this->actingAs($this->member)->get(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}"
        );

        $response->assertInertia(fn ($page) => $page
            ->component('Departments/Schedule/Shifts/Show')
            ->has('shift')
            ->where('shift.start_time', '08:00:00')
            ->where('shift.end_time', '16:00:00')
        );
    }

    public function test_show_page_returns_shift_with_title_for_week_calendar(): void
    {
        $shift = $this->createShift([
            'title' => 'Morning Shift',
            'date' => '2026-03-12',
            'start_time' => '06:00:00',
            'end_time' => '14:00:00',
        ]);

        $response = $this->actingAs($this->member)->get(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}"
        );

        $response->assertInertia(fn ($page) => $page
            ->component('Departments/Schedule/Shifts/Show')
            ->where('shift.title', 'Morning Shift')
        );
    }

    public function test_show_page_returns_shift_type_for_week_calendar(): void
    {
        $shift = $this->createShift([
            'type' => 'night',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
        ]);

        $response = $this->actingAs($this->member)->get(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}"
        );

        $response->assertInertia(fn ($page) => $page
            ->component('Departments/Schedule/Shifts/Show')
            ->where('shift.type', 'night')
        );
    }

    public function test_show_page_requires_authentication_for_week_calendar(): void
    {
        $shift = $this->createShift();

        $response = $this->get(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}"
        );

        $response->assertRedirect('/login');
    }

    public function test_show_page_returns_members_with_id_for_calendar_assignment(): void
    {
        // Add admin as a department member
        $this->department->members()->attach($this->admin->id);

        $shift = $this->createShift();

        $response = $this->actingAs($this->admin)->get(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift->uuid}"
        );

        $response->assertInertia(fn ($page) => $page
            ->component('Departments/Schedule/Shifts/Show')
            ->has('members')
            ->has('members.0', fn ($member) => $member
                ->has('id')
                ->has('uuid')
                ->has('name')
                ->has('email')
            )
        );
    }

    public function test_admin_can_create_shift_from_calendar_cell(): void
    {
        $shift = $this->createShift();

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts",
            [
                'creation_mode' => 'single',
                'date' => '2026-03-12',
                'start_time' => '10:00',
                'end_time' => '11:00',
                'type' => 'morning',
                'user_ids' => [$this->admin->id],
                'break_duration' => 0,
                'is_overtime' => false,
                'requires_approval' => false,
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('shifts', [
            'department_id' => $this->department->id,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);
    }

    // ==========================================
    // CALENDAR ASSIGNMENT — SHIFT + USER PIVOT
    // ==========================================

    public function test_calendar_assignment_persists_user_in_pivot_table(): void
    {
        $assignee = User::factory()->create();

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts",
            [
                'creation_mode' => 'single',
                'date' => '2026-03-12',
                'start_time' => '14:00',
                'end_time' => '15:00',
                'type' => 'afternoon',
                'user_ids' => [$assignee->id],
                'break_duration' => 0,
                'is_overtime' => false,
                'requires_approval' => false,
                '_from_calendar' => true,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $newShift = Shift::where('department_id', $this->department->id)
            ->whereDate('date', '2026-03-12')
            ->where('start_time', 'like', '14:00%')
            ->first();

        $this->assertNotNull($newShift);
        $this->assertDatabaseHas('shift_user', [
            'shift_id' => $newShift->id,
            'user_id' => $assignee->id,
        ]);
    }

    public function test_calendar_assignment_redirects_back_instead_of_index(): void
    {
        $assignee = User::factory()->create();

        // Simulate coming from a shift show page
        $response = $this->actingAs($this->admin)
            ->from("/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/some-uuid")
            ->post(
                "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts",
                [
                    'creation_mode' => 'single',
                    'date' => '2026-03-12',
                    'start_time' => '09:00',
                    'end_time' => '10:00',
                    'type' => 'morning',
                    'user_ids' => [$assignee->id],
                    'break_duration' => 0,
                    'is_overtime' => false,
                    'requires_approval' => false,
                    '_from_calendar' => true,
                ]
            );

        // Should redirect back to the show page, not to the schedule index
        $response->assertRedirect("/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/some-uuid");
    }

    public function test_calendar_assignment_without_flag_redirects_to_index(): void
    {
        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts",
            [
                'creation_mode' => 'single',
                'date' => '2026-03-12',
                'start_time' => '07:00',
                'end_time' => '08:00',
                'type' => 'morning',
                'user_ids' => [],
                'break_duration' => 0,
                'is_overtime' => false,
                'requires_approval' => false,
            ]
        );

        $response->assertRedirect();
        // Should redirect to schedule index (contains 'week=')
        $this->assertStringContainsString('week=', $response->headers->get('Location') ?? '');
    }

    public function test_show_page_returns_week_shifts_with_users(): void
    {
        $assignee = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);

        $mainShift = $this->createShift([
            'date' => '2026-03-12',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);
        $mainShift->users()->attach($assignee->id);

        $response = $this->actingAs($this->member)->get(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$mainShift->uuid}"
        );

        $response->assertInertia(fn ($page) => $page
            ->component('Departments/Schedule/Shifts/Show')
            ->has('weekShifts', 1)
            ->has('weekShifts.0', fn ($ws) => $ws
                ->where('id', $mainShift->id)
                ->where('uuid', $mainShift->uuid)
                ->where('date', '2026-03-12')
                ->where('start_time', '08:00:00')
                ->where('end_time', '16:00:00')
                ->has('users', 1)
                ->has('users.0', fn ($u) => $u
                    ->where('id', $assignee->id)
                    ->where('name', 'Jean Dupont')
                )
                ->etc()
            )
        );
    }

    public function test_show_page_week_shifts_only_contains_this_shift(): void
    {
        $mainShift = $this->createShift([
            'date' => '2026-03-12',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        // Another shift in the same week but NOT in the same series — should NOT appear
        $this->createShift([
            'date' => '2026-03-10',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        $response = $this->actingAs($this->member)->get(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$mainShift->uuid}"
        );

        $response->assertInertia(fn ($page) => $page
            ->component('Departments/Schedule/Shifts/Show')
            ->has('weekShifts', 1)
            ->where('weekShifts.0.id', $mainShift->id)
        );
    }

    // ==========================================
    // ADD / REMOVE USER FROM SHIFT (CALENDAR)
    // ==========================================

    public function test_add_user_to_shift_persists_in_pivot_table(): void
    {
        $shiftToAssign = $this->createShift([
            'date' => '2026-03-12',
            'start_time' => '14:00:00',
            'end_time' => '22:00:00',
        ]);
        $assignee = User::factory()->create();

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shiftToAssign->uuid}/add-user",
            ['user_id' => $assignee->id]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('shift_user', [
            'shift_id' => $shiftToAssign->id,
            'user_id' => $assignee->id,
        ]);
    }

    public function test_add_user_to_shift_does_not_duplicate(): void
    {
        $shiftToAssign = $this->createShift();
        $assignee = User::factory()->create();
        $shiftToAssign->users()->attach($assignee->id);

        $response = $this->actingAs($this->admin)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shiftToAssign->uuid}/add-user",
            ['user_id' => $assignee->id]
        );

        $response->assertRedirect();
        $this->assertEquals(1, $shiftToAssign->users()->count());
    }

    public function test_remove_user_from_shift(): void
    {
        $shiftToModify = $this->createShift();
        $assignee = User::factory()->create();
        $shiftToModify->users()->attach($assignee->id);

        $response = $this->actingAs($this->admin)->delete(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shiftToModify->uuid}/remove-user",
            ['user_id' => $assignee->id]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('shift_user', [
            'shift_id' => $shiftToModify->id,
            'user_id' => $assignee->id,
        ]);
    }

    public function test_member_cannot_add_user_to_shift(): void
    {
        $shiftToAssign = $this->createShift();
        $assignee = User::factory()->create();

        $response = $this->actingAs($this->member)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shiftToAssign->uuid}/add-user",
            ['user_id' => $assignee->id]
        );

        $this->assertTrue($response->isForbidden() || $response->isRedirect());
        $this->assertDatabaseMissing('shift_user', [
            'shift_id' => $shiftToAssign->id,
            'user_id' => $assignee->id,
        ]);
    }

    public function test_member_cannot_create_shift_from_calendar(): void
    {
        $response = $this->actingAs($this->member)->post(
            "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts",
            [
                'creation_mode' => 'single',
                'date' => '2026-03-12',
                'start_time' => '10:00',
                'end_time' => '11:00',
                'type' => 'morning',
                'user_ids' => [$this->member->id],
                'break_duration' => 0,
                'is_overtime' => false,
                'requires_approval' => false,
                '_from_calendar' => true,
            ]
        );

        $this->assertTrue($response->isForbidden() || $response->isRedirect());
        $this->assertDatabaseMissing('shifts', [
            'department_id' => $this->department->id,
            'start_time' => '10:00',
            'date' => '2026-03-12',
        ]);
    }
}
