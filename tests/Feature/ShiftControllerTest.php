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
}
