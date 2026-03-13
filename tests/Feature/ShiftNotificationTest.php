<?php

use App\Models\Department;
use App\Models\Scheduling\Shift;
use App\Models\Scheduling\WeeklySchedule;
use App\Models\User;
use App\Notifications\Scheduling\ShiftAssigned;
use App\Notifications\Scheduling\ShiftUnassigned;
use App\Notifications\Scheduling\ShiftUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view departments']);
    Permission::firstOrCreate(['name' => 'manage departments']);

    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $adminRole->givePermissionTo(['view departments', 'manage departments']);

    $this->admin = User::factory()->create(['first_name' => 'Admin', 'last_name' => 'User']);
    $this->admin->assignRole('admin');

    $this->assignee = User::factory()->create(['first_name' => 'Jean', 'last_name' => 'Dupont']);

    $this->department = Department::factory()->create(['name' => 'Logistique']);
    $this->schedule = WeeklySchedule::factory()->create([
        'department_id' => $this->department->id,
    ]);
});

function createShift(array $attributes = []): Shift
{
    return Shift::factory()->create(array_merge([
        'weekly_schedule_id' => test()->schedule->id,
        'department_id' => test()->department->id,
        'date' => '2026-03-14',
        'start_time' => '08:00',
        'end_time' => '16:00',
    ], $attributes));
}

function shiftUrl(Shift $shift, string $action): string
{
    return '/departments/'.test()->department->uuid.'/schedule/'.test()->schedule->uuid.'/shifts/'.$shift->uuid.'/'.$action;
}

// ==========================================
// ADD USER → ShiftAssigned notification
// ==========================================

it('sends ShiftAssigned notification when user is added to a shift', function () {
    Notification::fake();

    $shift = createShift();

    $this->actingAs($this->admin)->post(shiftUrl($shift, 'add-user'), [
        'user_id' => $this->assignee->id,
        'time_slot' => '08:00',
    ]);

    Notification::assertSentTo($this->assignee, ShiftAssigned::class, function ($notification) use ($shift) {
        return $notification->shift->id === $shift->id
            && $notification->timeSlot === '08:00'
            && $notification->assignedBy->id === $this->admin->id;
    });
});

it('does not send ShiftAssigned notification when user is already on the slot', function () {
    Notification::fake();

    $shift = createShift();
    $shift->users()->attach($this->assignee->id, ['time_slot' => '08:00']);

    $this->actingAs($this->admin)->post(shiftUrl($shift, 'add-user'), [
        'user_id' => $this->assignee->id,
        'time_slot' => '08:00',
    ]);

    Notification::assertNotSentTo($this->assignee, ShiftAssigned::class);
});

it('does not send ShiftAssigned notification when duplicate shift exists', function () {
    Notification::fake();

    $shift1 = createShift();
    $shift1->users()->attach($this->assignee->id, ['time_slot' => '08:00']);

    $shift2 = createShift(); // same date/time/department

    $this->actingAs($this->admin)->post(shiftUrl($shift2, 'add-user'), [
        'user_id' => $this->assignee->id,
        'time_slot' => '08:00',
    ]);

    Notification::assertNotSentTo($this->assignee, ShiftAssigned::class);
});

// ==========================================
// REMOVE USER → ShiftUnassigned notification
// ==========================================

it('sends ShiftUnassigned notification when user is removed from a shift', function () {
    Notification::fake();

    $shift = createShift();
    $shift->users()->attach($this->assignee->id, ['time_slot' => '10:00']);

    $this->actingAs($this->admin)->delete(shiftUrl($shift, 'remove-user'), [
        'user_id' => $this->assignee->id,
        'time_slot' => '10:00',
    ]);

    Notification::assertSentTo($this->assignee, ShiftUnassigned::class, function ($notification) use ($shift) {
        return $notification->shift->id === $shift->id
            && $notification->timeSlot === '10:00'
            && $notification->removedBy->id === $this->admin->id;
    });
});

// ==========================================
// UPDATE SHIFT → ShiftUpdated notification
// ==========================================

it('sends ShiftUpdated notification to remaining users when shift details change', function () {
    Notification::fake();

    $shift = createShift();
    $shift->users()->attach($this->assignee->id, ['time_slot' => '00:00']);

    $this->actingAs($this->admin)->put(
        '/departments/'.$this->department->uuid.'/schedule/'.$this->schedule->uuid.'/shifts/'.$shift->uuid,
        [
            'start_time' => '09:00',
            'end_time' => '17:00',
            'user_ids' => [$this->assignee->id],
        ]
    );

    Notification::assertSentTo($this->assignee, ShiftUpdated::class, function ($notification) {
        return isset($notification->changes['start_time'])
            && $notification->changes['start_time']['old'] === '08:00'
            && $notification->changes['start_time']['new'] === '09:00';
    });
});

it('sends ShiftAssigned to newly added users during shift update', function () {
    Notification::fake();

    $shift = createShift();
    $newUser = User::factory()->create();

    $this->actingAs($this->admin)->put(
        '/departments/'.$this->department->uuid.'/schedule/'.$this->schedule->uuid.'/shifts/'.$shift->uuid,
        [
            'user_ids' => [$newUser->id],
        ]
    );

    Notification::assertSentTo($newUser, ShiftAssigned::class);
});

it('sends ShiftUnassigned to removed users during shift update', function () {
    Notification::fake();

    $shift = createShift();
    $shift->users()->attach($this->assignee->id, ['time_slot' => '00:00']);

    $this->actingAs($this->admin)->put(
        '/departments/'.$this->department->uuid.'/schedule/'.$this->schedule->uuid.'/shifts/'.$shift->uuid,
        [
            'user_ids' => [],
        ]
    );

    Notification::assertSentTo($this->assignee, ShiftUnassigned::class);
});

it('does not send ShiftUpdated when no tracked fields change', function () {
    Notification::fake();

    $shift = createShift(['break_duration' => 30]);
    $shift->users()->attach($this->assignee->id, ['time_slot' => '00:00']);

    $this->actingAs($this->admin)->put(
        '/departments/'.$this->department->uuid.'/schedule/'.$this->schedule->uuid.'/shifts/'.$shift->uuid,
        [
            'user_ids' => [$this->assignee->id],
            'break_duration' => 45,
        ]
    );

    Notification::assertNotSentTo($this->assignee, ShiftUpdated::class);
});

// ==========================================
// NOTIFICATION CONTENT TESTS
// ==========================================

it('ShiftAssigned notification has correct mail content', function () {
    $shift = createShift(['title' => 'Shift Matin', 'location' => 'Hall A']);
    $shift->loadMissing(['department', 'weeklySchedule']);

    $notification = new ShiftAssigned($shift, '08:00', $this->admin);
    $mail = $notification->toMail($this->assignee);

    expect($mail->subject)->toContain('14/03/2026')
        ->and($mail->greeting)->toContain('Jean')
        ->and(collect($mail->introLines)->implode(' '))->toContain('Admin User');
});

it('ShiftAssigned notification has correct database payload', function () {
    $shift = createShift();
    $shift->loadMissing(['department', 'weeklySchedule']);

    $notification = new ShiftAssigned($shift, '08:00', $this->admin);
    $data = $notification->toDatabase($this->assignee);

    expect($data['type'])->toBe('shift_assigned')
        ->and($data['shift_id'])->toBe($shift->id)
        ->and($data['time_slot'])->toBe('08:00')
        ->and($data['assigned_by_id'])->toBe($this->admin->id)
        ->and($data['department_name'])->toBe('Logistique')
        ->and($data['action_url'])->toContain($shift->uuid);
});

it('ShiftUpdated notification includes changes in mail', function () {
    $shift = createShift();
    $shift->loadMissing(['department', 'weeklySchedule']);

    $changes = [
        'start_time' => ['old' => '08:00', 'new' => '09:00'],
        'location' => ['old' => 'Hall A', 'new' => 'Hall B'],
    ];

    $notification = new ShiftUpdated($shift, $changes, $this->admin);
    $mail = $notification->toMail($this->assignee);

    $lines = collect($mail->introLines)->implode(' ');
    expect($lines)->toContain('08:00')
        ->and($lines)->toContain('09:00')
        ->and($lines)->toContain('Hall A')
        ->and($lines)->toContain('Hall B');
});

it('ShiftUpdated notification stores changes in database payload', function () {
    $shift = createShift();
    $shift->loadMissing(['department', 'weeklySchedule']);

    $changes = ['start_time' => ['old' => '08:00', 'new' => '09:00']];

    $notification = new ShiftUpdated($shift, $changes, $this->admin);
    $data = $notification->toDatabase($this->assignee);

    expect($data['type'])->toBe('shift_updated')
        ->and($data['changes'])->toBe($changes)
        ->and($data['updated_by_name'])->toBe('Admin User');
});

it('ShiftUnassigned notification has correct database payload', function () {
    $shift = createShift();
    $shift->loadMissing(['department', 'weeklySchedule']);

    $notification = new ShiftUnassigned($shift, '10:00', $this->admin);
    $data = $notification->toDatabase($this->assignee);

    expect($data['type'])->toBe('shift_unassigned')
        ->and($data['time_slot'])->toBe('10:00')
        ->and($data['removed_by_name'])->toBe('Admin User');
});

// ==========================================
// DELIVERY CHANNELS
// ==========================================

it('ShiftAssigned uses mail and database channels', function () {
    $shift = createShift();
    $notification = new ShiftAssigned($shift, '08:00', $this->admin);

    expect($notification->via($this->assignee))->toBe(['mail', 'database']);
});

it('ShiftUpdated uses mail and database channels', function () {
    $shift = createShift();
    $notification = new ShiftUpdated($shift, [], $this->admin);

    expect($notification->via($this->assignee))->toBe(['mail', 'database']);
});

it('ShiftUnassigned uses mail and database channels', function () {
    $shift = createShift();
    $notification = new ShiftUnassigned($shift, '08:00', $this->admin);

    expect($notification->via($this->assignee))->toBe(['mail', 'database']);
});

// ==========================================
// EDGE CASES
// ==========================================

it('handles notification when assignedBy is null (system assignment)', function () {
    $shift = createShift();
    $shift->loadMissing(['department', 'weeklySchedule']);

    $notification = new ShiftAssigned($shift, '08:00', null);
    $data = $notification->toDatabase($this->assignee);

    expect($data['assigned_by_name'])->toBe('Le système')
        ->and($data['assigned_by_id'])->toBeNull();
});

it('sends notifications to multiple users when added via shift update', function () {
    Notification::fake();

    $shift = createShift();
    $user2 = User::factory()->create();

    $this->actingAs($this->admin)->put(
        '/departments/'.$this->department->uuid.'/schedule/'.$this->schedule->uuid.'/shifts/'.$shift->uuid,
        [
            'user_ids' => [$this->assignee->id, $user2->id],
        ]
    );

    Notification::assertSentTo($this->assignee, ShiftAssigned::class);
    Notification::assertSentTo($user2, ShiftAssigned::class);
});
