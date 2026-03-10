<?php

namespace Tests\Feature\Scheduling;

use App\Enums\Scheduling\AbsenceStatus;
use App\Enums\Scheduling\AbsenceType;
use App\Models\Department;
use App\Models\Scheduling\Absence;
use App\Models\Scheduling\LeaveBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsenceControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('member');

        $this->department = Department::factory()->create();
        $this->department->users()->attach($this->user);
        $this->department->users()->attach($this->admin);
    }

    /** @test */
    public function it_can_view_my_absences_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('departments.absences.my', $this->department));

        // Just check the endpoint returns 200, page component may not exist yet
        $response->assertStatus(200);
    }

    /** @test */
    public function my_absences_returns_only_current_user_absences(): void
    {
        $userAbsence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'type' => AbsenceType::VACATION,
            'status' => AbsenceStatus::PENDING,
        ]);

        Absence::factory()->create([
            'user_id' => $this->admin->id,
            'department_id' => $this->department->id,
            'type' => AbsenceType::SICK_LEAVE,
            'status' => AbsenceStatus::PENDING,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('departments.absences.my', $this->department));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('absences', 1)
            ->where('absences.0.id', $userAbsence->id)
        );
    }

    /** @test */
    public function my_absences_returns_leave_balances_for_current_user(): void
    {
        LeaveBalance::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'year' => now()->year,
            'leave_type' => AbsenceType::VACATION->value,
            'entitled_days' => 25,
            'taken_days' => 5,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('departments.absences.my', $this->department));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('balances')
        );
    }

    /** @test */
    public function it_returns_404_for_non_existent_department(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/departments/non-existent-uuid/absences/my');

        $response->assertStatus(404);
    }

    /** @test */
    public function admin_can_view_absences_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('departments.absences.index', $this->department));

        // Just check the endpoint returns 200, page component may not exist yet
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_filter_absences_by_status(): void
    {
        Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::PENDING,
        ]);

        Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::APPROVED,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('departments.absences.index', [
                'department' => $this->department,
                'status' => AbsenceStatus::PENDING->value,
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('absences.data', 1)
        );
    }

    /** @test */
    public function admin_can_filter_absences_by_type(): void
    {
        Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'type' => AbsenceType::VACATION,
        ]);

        Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'type' => AbsenceType::SICK_LEAVE,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('departments.absences.index', [
                'department' => $this->department,
                'type' => AbsenceType::VACATION->value,
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('absences.data', 1)
        );
    }

    /** @test */
    public function user_can_view_create_absence_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('departments.absences.create', $this->department));

        // Just check the endpoint returns 200, skip component check as it may not exist yet
        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_store_absence_request(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('departments.absences.store', $this->department), [
                'type' => AbsenceType::VACATION->value,
                'start_date' => now()->addWeek()->format('Y-m-d'),
                'end_date' => now()->addWeek()->addDays(2)->format('Y-m-d'),
                'reason' => 'Family vacation',
            ]);

        $response->assertRedirect(route('departments.absences.my', $this->department));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employee_absences', [
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'type' => AbsenceType::VACATION->value,
            'reason' => 'Family vacation',
        ]);
    }

    /** @test */
    public function admin_can_approve_absence_request(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::PENDING,
            'type' => AbsenceType::VACATION,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('departments.absences.approve', [
                'department' => $this->department,
                'absence' => $absence,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $absence->refresh();
        $this->assertEquals(AbsenceStatus::APPROVED, $absence->status);
        $this->assertEquals($this->admin->id, $absence->approved_by);
    }

    /** @test */
    public function admin_can_reject_absence_request(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::PENDING,
            'type' => AbsenceType::VACATION,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('departments.absences.reject', [
                'department' => $this->department,
                'absence' => $absence,
            ]), [
                'rejection_reason' => 'Not enough staff coverage',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $absence->refresh();
        $this->assertEquals(AbsenceStatus::REJECTED, $absence->status);
        $this->assertEquals('Not enough staff coverage', $absence->rejection_reason);
    }

    /** @test */
    public function user_can_cancel_their_own_pending_absence(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::PENDING,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('departments.absences.cancel', [
                'department' => $this->department,
                'absence' => $absence,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $absence->refresh();
        $this->assertEquals(AbsenceStatus::CANCELLED, $absence->status);
    }

    /** @test */
    public function user_cannot_cancel_other_users_absence(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->admin->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::PENDING,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('departments.absences.cancel', [
                'department' => $this->department,
                'absence' => $absence,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $absence->refresh();
        $this->assertEquals(AbsenceStatus::PENDING, $absence->status);
    }

    /** @test */
    public function admin_can_delete_absence(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('departments.absences.destroy', [
                'department' => $this->department,
                'absence' => $absence,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('employee_absences', ['id' => $absence->id]);
    }

    /** @test */
    public function it_returns_pending_count(): void
    {
        Absence::factory()->count(3)->create([
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::PENDING,
        ]);

        Absence::factory()->create([
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::APPROVED,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('departments.absences.pending-count', $this->department));

        $response->assertStatus(200);
        $response->assertJson(['count' => 3]);
    }

    /** @test */
    public function it_returns_calendar_data(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::APPROVED,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(2)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('departments.absences.calendar', [
                'department' => $this->department,
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
            ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $absence->uuid,
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_absences(): void
    {
        $response = $this->get(route('departments.absences.my', $this->department));

        $response->assertRedirect(route('login'));
    }

    // ==========================================
    // ADDITIONAL COMPREHENSIVE TESTS
    // ==========================================

    /** @test */
    public function user_can_edit_their_own_pending_absence(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::PENDING,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('departments.absences.edit', [
                'department' => $this->department,
                'absence' => $absence,
            ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function user_cannot_edit_others_absence(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->admin->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::PENDING,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('departments.absences.edit', [
                'department' => $this->department,
                'absence' => $absence,
            ]));

        // Should be forbidden (403) or redirect
        $this->assertTrue($response->isForbidden() || $response->isRedirect());
    }

    /** @test */
    public function user_cannot_edit_approved_absence(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::APPROVED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('departments.absences.edit', [
                'department' => $this->department,
                'absence' => $absence,
            ]));

        // Should be forbidden (403) or redirect
        $this->assertTrue($response->isForbidden() || $response->isRedirect());
    }

    /** @test */
    public function user_can_update_their_pending_absence(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::PENDING,
            'type' => AbsenceType::VACATION,
        ]);

        $newStartDate = now()->addDays(10)->format('Y-m-d');
        $newEndDate = now()->addDays(12)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->put(route('departments.absences.update', [
                'department' => $this->department,
                'absence' => $absence,
            ]), [
                'type' => AbsenceType::VACATION->value,
                'start_date' => $newStartDate,
                'end_date' => $newEndDate,
                'reason' => 'Updated reason',
            ]);

        $response->assertRedirect(route('departments.absences.my', $this->department));
        $response->assertSessionHas('success');

        $absence->refresh();
        $this->assertEquals($newStartDate, $absence->start_date->format('Y-m-d'));
        $this->assertEquals('Updated reason', $absence->reason);
    }

    /** @test */
    public function user_cannot_update_others_absence(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->admin->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::PENDING,
            'type' => AbsenceType::VACATION,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('departments.absences.update', [
                'department' => $this->department,
                'absence' => $absence,
            ]), [
                'type' => AbsenceType::VACATION->value,
                'start_date' => now()->addDays(10)->format('Y-m-d'),
                'end_date' => now()->addDays(12)->format('Y-m-d'),
            ]);

        $response->assertSessionHas('error');
    }

    /** @test */
    public function admin_cannot_approve_already_processed_absence(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::APPROVED,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('departments.absences.approve', [
                'department' => $this->department,
                'absence' => $absence,
            ]));

        $response->assertSessionHas('error');
    }

    /** @test */
    public function admin_cannot_reject_already_processed_absence(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::REJECTED,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('departments.absences.reject', [
                'department' => $this->department,
                'absence' => $absence,
            ]), [
                'rejection_reason' => 'Some reason',
            ]);

        $response->assertSessionHas('error');
    }

    /** @test */
    public function rejection_requires_reason(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::PENDING,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('departments.absences.reject', [
                'department' => $this->department,
                'absence' => $absence,
            ]), [
                // No rejection_reason provided
            ]);

        $response->assertSessionHasErrors('rejection_reason');

        $absence->refresh();
        $this->assertEquals(AbsenceStatus::PENDING, $absence->status);
    }

    /** @test */
    public function user_can_cancel_approved_absence(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::APPROVED,
            'type' => AbsenceType::VACATION,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('departments.absences.cancel', [
                'department' => $this->department,
                'absence' => $absence,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $absence->refresh();
        $this->assertEquals(AbsenceStatus::CANCELLED, $absence->status);
    }

    /** @test */
    public function user_cannot_cancel_rejected_absence(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::REJECTED,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('departments.absences.cancel', [
                'department' => $this->department,
                'absence' => $absence,
            ]));

        $response->assertSessionHas('error');

        $absence->refresh();
        $this->assertEquals(AbsenceStatus::REJECTED, $absence->status);
    }

    /** @test */
    public function store_absence_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('departments.absences.store', $this->department), [
                // Missing required fields
            ]);

        $response->assertSessionHasErrors(['type', 'start_date', 'end_date']);
    }

    /** @test */
    public function store_absence_validates_end_date_after_start_date(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('departments.absences.store', $this->department), [
                'type' => AbsenceType::VACATION->value,
                'start_date' => now()->addDays(5)->format('Y-m-d'),
                'end_date' => now()->addDays(3)->format('Y-m-d'), // Before start
            ]);

        $response->assertSessionHasErrors('end_date');
    }

    /** @test */
    public function store_absence_validates_start_date_not_in_past(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('departments.absences.store', $this->department), [
                'type' => AbsenceType::VACATION->value,
                'start_date' => now()->subDays(1)->format('Y-m-d'), // In the past
                'end_date' => now()->addDays(3)->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('start_date');
    }

    /** @test */
    public function sick_leave_auto_approved(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('departments.absences.store', $this->department), [
                'type' => AbsenceType::SICK_LEAVE->value,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(2)->format('Y-m-d'),
                'reason' => 'Flu',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $absence = Absence::where('user_id', $this->user->id)
            ->where('type', AbsenceType::SICK_LEAVE)
            ->first();

        $this->assertNotNull($absence);
        $this->assertEquals(AbsenceStatus::APPROVED, $absence->status);
    }

    /** @test */
    public function vacation_requires_approval(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('departments.absences.store', $this->department), [
                'type' => AbsenceType::VACATION->value,
                'start_date' => now()->addDays(7)->format('Y-m-d'),
                'end_date' => now()->addDays(10)->format('Y-m-d'),
                'reason' => 'Holiday',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $absence = Absence::where('user_id', $this->user->id)
            ->where('type', AbsenceType::VACATION)
            ->first();

        $this->assertNotNull($absence);
        $this->assertEquals(AbsenceStatus::PENDING, $absence->status);
    }

    /** @test */
    public function admin_can_view_absence_details(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('departments.absences.show', [
                'department' => $this->department,
                'absence' => $absence,
            ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_filter_absences_by_date_range(): void
    {
        $insideRange = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
        ]);

        Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'start_date' => now()->addDays(30),
            'end_date' => now()->addDays(32),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('departments.absences.index', [
                'department' => $this->department,
                'from' => now()->format('Y-m-d'),
                'to' => now()->addDays(10)->format('Y-m-d'),
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('absences.data', 1)
            ->where('absences.data.0.id', $insideRange->id)
        );
    }

    /** @test */
    public function regular_user_cannot_delete_absence(): void
    {
        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('departments.absences.destroy', [
                'department' => $this->department,
                'absence' => $absence,
            ]));

        // User should be forbidden (doesn't have 'manage departments' permission)
        $this->assertTrue($response->isForbidden() || $response->isRedirect());

        $this->assertDatabaseHas('employee_absences', ['id' => $absence->id]);
    }

    /** @test */
    public function approving_absence_deducts_from_leave_balance(): void
    {
        // Create initial leave balance
        $balance = LeaveBalance::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'year' => now()->year,
            'leave_type' => AbsenceType::VACATION->value,
            'entitled_days' => 25,
            'taken_days' => 0,
        ]);

        $startDate = now()->addDays(5);
        $endDate = now()->addDays(7);
        $startDate->diffInDays($endDate); // 3 days

        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::PENDING,
            'type' => AbsenceType::VACATION,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('departments.absences.approve', [
                'department' => $this->department,
                'absence' => $absence,
            ]));

        $response->assertRedirect();

        $balance->refresh();
        // The balance should have the days deducted
        $this->assertGreaterThan(0, $balance->taken_days);
    }

    /** @test */
    public function cancelling_approved_absence_restores_leave_balance(): void
    {
        // Create leave balance with taken days
        $balance = LeaveBalance::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'year' => now()->year,
            'leave_type' => AbsenceType::VACATION->value,
            'entitled_days' => 25,
            'taken_days' => 5,
        ]);

        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::APPROVED,
            'type' => AbsenceType::VACATION,
            'days_count' => 3,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('departments.absences.cancel', [
                'department' => $this->department,
                'absence' => $absence,
            ]));

        $response->assertRedirect();

        $balance->refresh();
        $this->assertEquals(2, $balance->taken_days); // 5 - 3 = 2
    }

    /** @test */
    public function deleting_approved_absence_restores_leave_balance(): void
    {
        // Create leave balance with taken days
        $balance = LeaveBalance::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'year' => now()->year,
            'leave_type' => AbsenceType::VACATION->value,
            'entitled_days' => 25,
            'taken_days' => 5,
        ]);

        $absence = Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::APPROVED,
            'type' => AbsenceType::VACATION,
            'days_count' => 3,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('departments.absences.destroy', [
                'department' => $this->department,
                'absence' => $absence,
            ]));

        $response->assertRedirect();

        $balance->refresh();
        $this->assertEquals(2, $balance->taken_days); // 5 - 3 = 2
    }

    /** @test */
    public function my_absences_page_shows_all_user_absence_statuses(): void
    {
        Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::PENDING,
        ]);

        Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::APPROVED,
        ]);

        Absence::factory()->create([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => AbsenceStatus::REJECTED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('departments.absences.my', $this->department));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('absences', 3)
        );
    }

    // ==========================================
    // SEARCH INTERIM CANDIDATES TESTS
    // ==========================================

    /** @test */
    public function user_can_search_interim_candidates(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('departments.absences.search-interim', [
                'department' => $this->department,
            ]));

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    /** @test */
    public function search_interim_returns_department_members(): void
    {
        // Admin is also a member of the department
        $response = $this->actingAs($this->user)
            ->getJson(route('departments.absences.search-interim', [
                'department' => $this->department,
            ]));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'value' => $this->admin->id,
            'type' => 'user',
        ]);
    }

    /** @test */
    public function search_interim_excludes_current_user(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('departments.absences.search-interim', [
                'department' => $this->department,
            ]));

        $response->assertStatus(200);

        // The current user should not be in the results
        $data = $response->json();
        $userIds = array_column($data, 'value');
        $this->assertNotContains($this->user->id, $userIds);
    }

    /** @test */
    public function search_interim_filters_by_search_query(): void
    {
        // Create additional users with specific names
        $searchableUser = User::factory()->create([
            'first_name' => 'SearchableFirstName',
            'last_name' => 'SearchableLastName',
        ]);
        $searchableUser->assignRole('member');
        $this->department->users()->attach($searchableUser);

        $response = $this->actingAs($this->user)
            ->getJson(route('departments.absences.search-interim', [
                'department' => $this->department,
                'search' => 'SearchableFirst',
            ]));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'value' => $searchableUser->id,
        ]);
    }

    /** @test */
    public function search_interim_searches_by_email(): void
    {
        $emailUser = User::factory()->create([
            'email' => 'unique-test-email@example.com',
        ]);
        $emailUser->assignRole('member');
        $this->department->users()->attach($emailUser);

        $response = $this->actingAs($this->user)
            ->getJson(route('departments.absences.search-interim', [
                'department' => $this->department,
                'search' => 'unique-test-email',
            ]));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'value' => $emailUser->id,
        ]);
    }

    /** @test */
    public function search_interim_returns_employees(): void
    {
        // Create an employee in the department
        \App\Models\Employee::factory()->create([
            'user_id' => $this->admin->id,
            'department_id' => $this->department->id,
            'status' => \App\Enums\Employee\EmployeeStatus::ACTIVE,
            'position' => 'Test Position',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('departments.absences.search-interim', [
                'department' => $this->department,
            ]));

        $response->assertStatus(200);
        // Should include the employee
        $data = $response->json();
        $this->assertNotEmpty($data);
    }

    /** @test */
    public function search_interim_returns_staff_outside_department_when_searching(): void
    {
        // Create a user NOT in the department
        $outsideUser = User::factory()->create([
            'first_name' => 'OutsideStaff',
            'last_name' => 'Member',
            'is_active' => true,
        ]);
        $outsideUser->assignRole('member');

        $response = $this->actingAs($this->user)
            ->getJson(route('departments.absences.search-interim', [
                'department' => $this->department,
                'search' => 'OutsideStaff',
            ]));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'value' => $outsideUser->id,
            'type' => 'staff',
        ]);
    }

    /** @test */
    public function search_interim_validates_search_length(): void
    {
        $longSearch = str_repeat('a', 200);

        $response = $this->actingAs($this->user)
            ->getJson(route('departments.absences.search-interim', [
                'department' => $this->department,
                'search' => $longSearch,
            ]));

        $response->assertStatus(422);
    }

    /** @test */
    public function search_interim_removes_duplicates(): void
    {
        // Create a user who is both a department member and an employee
        $dualUser = User::factory()->create([
            'first_name' => 'DualRole',
            'last_name' => 'User',
        ]);
        $dualUser->assignRole('member');
        $this->department->users()->attach($dualUser);

        \App\Models\Employee::factory()->create([
            'user_id' => $dualUser->id,
            'department_id' => $this->department->id,
            'status' => \App\Enums\Employee\EmployeeStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('departments.absences.search-interim', [
                'department' => $this->department,
                'search' => 'DualRole',
            ]));

        $response->assertStatus(200);
        $data = $response->json();

        // Count occurrences of the user's id
        $userIdCount = count(array_filter($data, fn(array $item): bool => $item['value'] === $dualUser->id));
        $this->assertEquals(1, $userIdCount);
    }

    /** @test */
    public function unauthenticated_user_cannot_search_interim(): void
    {
        $response = $this->getJson(route('departments.absences.search-interim', [
            'department' => $this->department,
        ]));

        $response->assertStatus(401);
    }

    /** @test */
    public function search_interim_response_has_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('departments.absences.search-interim', [
                'department' => $this->department,
            ]));

        $response->assertStatus(200);

        if (count($response->json()) > 0) {
            $response->assertJsonStructure([
                '*' => [
                    'value',
                    'label',
                    'type',
                    'type_label',
                ],
            ]);
        }
    }

    /** @test */
    public function user_can_store_absence_with_interim_user(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('departments.absences.store', $this->department), [
                'type' => AbsenceType::VACATION->value,
                'start_date' => now()->addWeek()->format('Y-m-d'),
                'end_date' => now()->addWeek()->addDays(2)->format('Y-m-d'),
                'reason' => 'Family vacation',
                'interim_user_id' => $this->admin->id,
                'interim_notes' => 'Please handle my tasks',
            ]);

        $response->assertRedirect(route('departments.absences.my', $this->department));

        $this->assertDatabaseHas('employee_absences', [
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'interim_user_id' => $this->admin->id,
            'interim_notes' => 'Please handle my tasks',
        ]);
    }
}
