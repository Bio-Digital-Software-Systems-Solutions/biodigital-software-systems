<?php

use App\Models\Department;
use App\Models\Scheduling\Shift;
use App\Models\Scheduling\ShiftSeries;
use App\Models\Scheduling\WeeklySchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
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
    $this->schedule = WeeklySchedule::factory()->create([
        'department_id' => $this->department->id,
    ]);
});

// ==========================================
// SINGLE SHIFT CREATION
// ==========================================

it('creates a single shift with creation_mode=single', function () {
    $weekStart = $this->schedule->week_start->format('Y-m-d');

    $response = $this->actingAs($this->admin)->post(
        "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts",
        [
            'creation_mode' => 'single',
            'date' => $weekStart,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'type' => 'morning',
        ]
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect(Shift::count())->toBe(1);
    expect(Shift::first()->series_id)->toBeNull();
});

// ==========================================
// MULTIPLE DATES CREATION
// ==========================================

it('creates multiple shifts for multiple_dates mode', function () {
    $weekStart = $this->schedule->week_start;
    $dates = [
        $weekStart->format('Y-m-d'),
        $weekStart->copy()->addDay()->format('Y-m-d'),
        $weekStart->copy()->addDays(2)->format('Y-m-d'),
    ];

    $response = $this->actingAs($this->admin)->post(
        "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts",
        [
            'creation_mode' => 'multiple_dates',
            'dates' => $dates,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'type' => 'morning',
        ]
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect(Shift::count())->toBe(3);

    $series = ShiftSeries::first();
    expect($series)->not->toBeNull();
    expect($series->recurrence_type)->toBeNull();
    expect(Shift::where('series_id', $series->id)->count())->toBe(3);
});

it('requires at least one date in multiple_dates mode', function () {
    $response = $this->actingAs($this->admin)->post(
        "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts",
        [
            'creation_mode' => 'multiple_dates',
            'dates' => [],
            'start_time' => '08:00',
            'end_time' => '16:00',
            'type' => 'morning',
        ]
    );

    $response->assertSessionHasErrors('dates');
    expect(Shift::count())->toBe(0);
});

// ==========================================
// RECURRING SHIFT CREATION
// ==========================================

it('creates weekly recurring shifts with correct series', function () {
    $startDate = $this->schedule->week_start->format('Y-m-d');
    $endDate = $this->schedule->week_start->copy()->addWeeks(3)->format('Y-m-d');

    $response = $this->actingAs($this->admin)->post(
        "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts",
        [
            'creation_mode' => 'recurring',
            'date' => $startDate,
            'recurrence_type' => 'weekly',
            'recurrence_end_date' => $endDate,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'type' => 'morning',
        ]
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect(Shift::count())->toBe(4); // start + 3 more weeks

    $series = ShiftSeries::first();
    expect($series->recurrence_type)->toBe('weekly');
    expect(Shift::where('series_id', $series->id)->count())->toBe(4);
});

it('creates daily recurring shifts', function () {
    $startDate = $this->schedule->week_start->format('Y-m-d');
    $endDate = $this->schedule->week_start->copy()->addDays(4)->format('Y-m-d');

    $response = $this->actingAs($this->admin)->post(
        "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts",
        [
            'creation_mode' => 'recurring',
            'date' => $startDate,
            'recurrence_type' => 'daily',
            'recurrence_end_date' => $endDate,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'type' => 'morning',
        ]
    );

    $response->assertRedirect();
    expect(Shift::count())->toBe(5); // 5 days inclusive
    expect(ShiftSeries::first()->recurrence_type)->toBe('daily');
});

it('creates monthly recurring shifts', function () {
    $startDate = $this->schedule->week_start->format('Y-m-d');
    $endDate = $this->schedule->week_start->copy()->addMonths(2)->format('Y-m-d');

    $response = $this->actingAs($this->admin)->post(
        "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts",
        [
            'creation_mode' => 'recurring',
            'date' => $startDate,
            'recurrence_type' => 'monthly',
            'recurrence_end_date' => $endDate,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'type' => 'morning',
        ]
    );

    $response->assertRedirect();
    expect(Shift::count())->toBe(3); // start + 2 months
});

it('creates weekly schedules automatically for recurring shifts spanning multiple weeks', function () {
    $startDate = $this->schedule->week_start->format('Y-m-d');
    $endDate = $this->schedule->week_start->copy()->addWeeks(2)->format('Y-m-d');

    $this->actingAs($this->admin)->post(
        "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts",
        [
            'creation_mode' => 'recurring',
            'date' => $startDate,
            'recurrence_type' => 'weekly',
            'recurrence_end_date' => $endDate,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'type' => 'morning',
        ]
    );

    // Should have 3 weekly schedules (original + 2 auto-created)
    expect(WeeklySchedule::where('department_id', $this->department->id)->count())->toBe(3);
});

it('validates recurrence_end_date is after start date', function () {
    $startDate = $this->schedule->week_start->format('Y-m-d');

    $response = $this->actingAs($this->admin)->post(
        "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts",
        [
            'creation_mode' => 'recurring',
            'date' => $startDate,
            'recurrence_type' => 'weekly',
            'recurrence_end_date' => $this->schedule->week_start->copy()->subDay()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'type' => 'morning',
        ]
    );

    $response->assertSessionHasErrors('recurrence_end_date');
    expect(Shift::count())->toBe(0);
});

// ==========================================
// UPDATE SINGLE SHIFT IN SERIES
// ==========================================

it('updates only a single shift when update_scope=single', function () {
    $series = ShiftSeries::create(['recurrence_type' => 'weekly']);
    $weekStart = $this->schedule->week_start;

    $shift1 = Shift::factory()->create([
        'weekly_schedule_id' => $this->schedule->id,
        'department_id' => $this->department->id,
        'series_id' => $series->id,
        'date' => $weekStart->format('Y-m-d'),
        'title' => 'Original',
    ]);
    $shift2 = Shift::factory()->create([
        'weekly_schedule_id' => $this->schedule->id,
        'department_id' => $this->department->id,
        'series_id' => $series->id,
        'date' => $weekStart->copy()->addWeek()->format('Y-m-d'),
        'title' => 'Original',
    ]);

    $response = $this->actingAs($this->admin)->put(
        "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift1->uuid}",
        [
            'update_scope' => 'single',
            'title' => 'Modified',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'type' => 'morning',
        ]
    );

    $response->assertRedirect();
    expect($shift1->fresh()->title)->toBe('Modified');
    expect($shift2->fresh()->title)->toBe('Original');
});

it('updates all shifts in series when update_scope=all', function () {
    $series = ShiftSeries::create(['recurrence_type' => 'weekly']);
    $weekStart = $this->schedule->week_start;

    $shift1 = Shift::factory()->create([
        'weekly_schedule_id' => $this->schedule->id,
        'department_id' => $this->department->id,
        'series_id' => $series->id,
        'date' => $weekStart->format('Y-m-d'),
        'title' => 'Original',
    ]);
    $shift2 = Shift::factory()->create([
        'weekly_schedule_id' => $this->schedule->id,
        'department_id' => $this->department->id,
        'series_id' => $series->id,
        'date' => $weekStart->copy()->addWeek()->format('Y-m-d'),
        'title' => 'Original',
    ]);

    $this->actingAs($this->admin)->put(
        "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift1->uuid}",
        [
            'update_scope' => 'all',
            'title' => 'Updated All',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'type' => 'full_day',
        ]
    );

    expect($shift1->fresh()->title)->toBe('Updated All');
    expect($shift2->fresh()->title)->toBe('Updated All');
});

it('updates only following shifts when update_scope=following', function () {
    $series = ShiftSeries::create(['recurrence_type' => 'weekly']);
    $weekStart = $this->schedule->week_start;

    $shiftPast = Shift::factory()->create([
        'weekly_schedule_id' => $this->schedule->id,
        'department_id' => $this->department->id,
        'series_id' => $series->id,
        'date' => $weekStart->copy()->subWeek()->format('Y-m-d'),
        'title' => 'Past',
    ]);
    $shiftCurrent = Shift::factory()->create([
        'weekly_schedule_id' => $this->schedule->id,
        'department_id' => $this->department->id,
        'series_id' => $series->id,
        'date' => $weekStart->format('Y-m-d'),
        'title' => 'Current',
    ]);
    $shiftFuture = Shift::factory()->create([
        'weekly_schedule_id' => $this->schedule->id,
        'department_id' => $this->department->id,
        'series_id' => $series->id,
        'date' => $weekStart->copy()->addWeek()->format('Y-m-d'),
        'title' => 'Future',
    ]);

    $this->actingAs($this->admin)->put(
        "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shiftCurrent->uuid}",
        [
            'update_scope' => 'following',
            'title' => 'Updated Following',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'type' => 'morning',
        ]
    );

    expect($shiftPast->fresh()->title)->toBe('Past');
    expect($shiftCurrent->fresh()->title)->toBe('Updated Following');
    expect($shiftFuture->fresh()->title)->toBe('Updated Following');
});

// ==========================================
// DELETE SERIES SHIFTS
// ==========================================

it('deletes only one shift when delete_scope=single', function () {
    $series = ShiftSeries::create(['recurrence_type' => 'weekly']);
    $weekStart = $this->schedule->week_start;

    $shift1 = Shift::factory()->create([
        'weekly_schedule_id' => $this->schedule->id,
        'department_id' => $this->department->id,
        'series_id' => $series->id,
        'date' => $weekStart->format('Y-m-d'),
    ]);
    $shift2 = Shift::factory()->create([
        'weekly_schedule_id' => $this->schedule->id,
        'department_id' => $this->department->id,
        'series_id' => $series->id,
        'date' => $weekStart->copy()->addWeek()->format('Y-m-d'),
    ]);

    $this->actingAs($this->admin)->delete(
        "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift1->uuid}",
        ['delete_scope' => 'single']
    );

    $this->assertSoftDeleted('shifts', ['id' => $shift1->id]);
    expect(Shift::withTrashed()->find($shift2->id)->deleted_at)->toBeNull();
});

it('deletes all shifts in series when delete_scope=all', function () {
    $series = ShiftSeries::create(['recurrence_type' => 'weekly']);
    $weekStart = $this->schedule->week_start;

    $shift1 = Shift::factory()->create([
        'weekly_schedule_id' => $this->schedule->id,
        'department_id' => $this->department->id,
        'series_id' => $series->id,
        'date' => $weekStart->format('Y-m-d'),
    ]);
    $shift2 = Shift::factory()->create([
        'weekly_schedule_id' => $this->schedule->id,
        'department_id' => $this->department->id,
        'series_id' => $series->id,
        'date' => $weekStart->copy()->addWeek()->format('Y-m-d'),
    ]);

    $this->actingAs($this->admin)->delete(
        "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shift1->uuid}",
        ['delete_scope' => 'all']
    );

    $this->assertSoftDeleted('shifts', ['id' => $shift1->id]);
    $this->assertSoftDeleted('shifts', ['id' => $shift2->id]);
});

it('deletes following shifts when delete_scope=following', function () {
    $series = ShiftSeries::create(['recurrence_type' => 'weekly']);
    $weekStart = $this->schedule->week_start;

    $shiftPast = Shift::factory()->create([
        'weekly_schedule_id' => $this->schedule->id,
        'department_id' => $this->department->id,
        'series_id' => $series->id,
        'date' => $weekStart->copy()->subWeek()->format('Y-m-d'),
    ]);
    $shiftCurrent = Shift::factory()->create([
        'weekly_schedule_id' => $this->schedule->id,
        'department_id' => $this->department->id,
        'series_id' => $series->id,
        'date' => $weekStart->format('Y-m-d'),
    ]);
    $shiftFuture = Shift::factory()->create([
        'weekly_schedule_id' => $this->schedule->id,
        'department_id' => $this->department->id,
        'series_id' => $series->id,
        'date' => $weekStart->copy()->addWeek()->format('Y-m-d'),
    ]);

    $this->actingAs($this->admin)->delete(
        "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts/{$shiftCurrent->uuid}",
        ['delete_scope' => 'following']
    );

    expect(Shift::withTrashed()->find($shiftPast->id)->deleted_at)->toBeNull();
    $this->assertSoftDeleted('shifts', ['id' => $shiftCurrent->id]);
    $this->assertSoftDeleted('shifts', ['id' => $shiftFuture->id]);
});

// ==========================================
// AUTHORIZATION
// ==========================================

it('prevents member from creating series shifts', function () {
    $response = $this->actingAs($this->member)->post(
        "/departments/{$this->department->uuid}/schedule/{$this->schedule->uuid}/shifts",
        [
            'creation_mode' => 'multiple_dates',
            'dates' => [$this->schedule->week_start->format('Y-m-d')],
            'start_time' => '08:00',
            'end_time' => '16:00',
            'type' => 'morning',
        ]
    );

    expect($response->isForbidden() || $response->isRedirect())->toBeTrue();
    expect(Shift::count())->toBe(0);
});
