<?php

use App\Models\Department;
use App\Models\DepartmentPosition;
use App\Models\DepartmentPositionNomination;
use App\Models\User;

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('manage departments');
});

it('lists active nominations for a department', function (): void {
    $department = Department::factory()->create();
    $position = DepartmentPosition::factory()->create(['department_id' => $department->id]);
    $member = User::factory()->create();
    $department->users()->attach($member);

    DepartmentPositionNomination::create([
        'department_id' => $department->id,
        'department_position_id' => $position->id,
        'user_id' => $member->id,
        'nominated_by' => $this->admin->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('departments.nominations.index', $department));

    $response->assertOk();
    $response->assertJsonCount(1);
});

it('creates a nomination for a department member', function (): void {
    $department = Department::factory()->create();
    $position = DepartmentPosition::factory()->create(['department_id' => $department->id]);
    $member = User::factory()->create();
    $department->users()->attach($member);

    $response = $this->actingAs($this->admin)
        ->post(route('departments.nominations.store', $department), [
            'department_position_id' => $position->id,
            'user_id' => $member->id,
            'start_date' => '2025-01-01',
            'notes' => 'Test nomination',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('department_position_nominations', [
        'department_id' => $department->id,
        'department_position_id' => $position->id,
        'user_id' => $member->id,
        'is_active' => true,
    ]);
});

it('cannot nominate a non-member to a position', function (): void {
    $department = Department::factory()->create();
    $position = DepartmentPosition::factory()->create(['department_id' => $department->id]);
    $nonMember = User::factory()->create();

    $response = $this->actingAs($this->admin)
        ->post(route('departments.nominations.store', $department), [
            'department_position_id' => $position->id,
            'user_id' => $nonMember->id,
        ]);

    $response->assertSessionHasErrors(['user_id']);
});

it('cannot nominate to a position from another department', function (): void {
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $position = DepartmentPosition::factory()->create(['department_id' => $otherDepartment->id]);
    $member = User::factory()->create();
    $department->users()->attach($member);

    $response = $this->actingAs($this->admin)
        ->post(route('departments.nominations.store', $department), [
            'department_position_id' => $position->id,
            'user_id' => $member->id,
        ]);

    $response->assertSessionHasErrors(['department_position_id']);
});

it('cannot create duplicate active nomination for same position and user', function (): void {
    $department = Department::factory()->create();
    $position = DepartmentPosition::factory()->create(['department_id' => $department->id]);
    $member = User::factory()->create();
    $department->users()->attach($member);

    // Create first nomination
    DepartmentPositionNomination::create([
        'department_id' => $department->id,
        'department_position_id' => $position->id,
        'user_id' => $member->id,
        'nominated_by' => $this->admin->id,
        'is_active' => true,
    ]);

    // Try to create duplicate
    $response = $this->actingAs($this->admin)
        ->post(route('departments.nominations.store', $department), [
            'department_position_id' => $position->id,
            'user_id' => $member->id,
        ]);

    $response->assertSessionHasErrors(['user_id']);
});

it('updates a nomination', function (): void {
    $department = Department::factory()->create();
    $position = DepartmentPosition::factory()->create(['department_id' => $department->id]);
    $member = User::factory()->create();
    $department->users()->attach($member);

    $nomination = DepartmentPositionNomination::create([
        'department_id' => $department->id,
        'department_position_id' => $position->id,
        'user_id' => $member->id,
        'nominated_by' => $this->admin->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->put(route('departments.nominations.update', [$department, $nomination]), [
            'notes' => 'Updated notes',
            'end_date' => '2025-12-31',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $nomination->refresh();
    expect($nomination->notes)->toBe('Updated notes');
    expect($nomination->end_date->format('Y-m-d'))->toBe('2025-12-31');
});

it('deletes a nomination by deactivating it', function (): void {
    $department = Department::factory()->create();
    $position = DepartmentPosition::factory()->create(['department_id' => $department->id]);
    $member = User::factory()->create();
    $department->users()->attach($member);

    $nomination = DepartmentPositionNomination::create([
        'department_id' => $department->id,
        'department_position_id' => $position->id,
        'user_id' => $member->id,
        'nominated_by' => $this->admin->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->delete(route('departments.nominations.destroy', [$department, $nomination]));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $nomination->refresh();
    expect($nomination->is_active)->toBeFalse();
    expect($nomination->deleted_at)->not->toBeNull();
});

it('requires manage departments permission', function (): void {
    $user = User::factory()->create();
    $department = Department::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('departments.nominations.index', $department));

    expect($response->status())->toBeIn([403, 302]);
});
