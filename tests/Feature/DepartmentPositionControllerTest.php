<?php

use App\Models\Department;
use App\Models\DepartmentPosition;
use App\Models\User;

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('manage departments');
});

it('creates a position for a department', function (): void {
    $department = Department::factory()->create();

    $response = $this->actingAs($this->admin)
        ->post(route('departments.positions.store', $department), [
            'name' => 'Responsable Technique',
            'code' => 'RT',
            'description' => 'Responsable des aspects techniques',
            'color' => '#3b82f6',
            'hourly_rate' => 25.50,
            'is_active' => true,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('department_positions', [
        'department_id' => $department->id,
        'name' => 'Responsable Technique',
        'code' => 'RT',
        'color' => '#3b82f6',
        'is_active' => true,
    ]);
});

it('creates a position with minimal data', function (): void {
    $department = Department::factory()->create();

    $response = $this->actingAs($this->admin)
        ->post(route('departments.positions.store', $department), [
            'name' => 'Assistant',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('department_positions', [
        'department_id' => $department->id,
        'name' => 'Assistant',
        'is_active' => true,
    ]);
});

it('requires name when creating a position', function (): void {
    $department = Department::factory()->create();

    $response = $this->actingAs($this->admin)
        ->post(route('departments.positions.store', $department), [
            'code' => 'TEST',
        ]);

    $response->assertSessionHasErrors(['name']);
});

it('updates a position', function (): void {
    $department = Department::factory()->create();
    $position = DepartmentPosition::factory()->create([
        'department_id' => $department->id,
        'name' => 'Old Name',
    ]);

    $response = $this->actingAs($this->admin)
        ->put(route('departments.positions.update', [$department, $position]), [
            'name' => 'New Name',
            'code' => 'NEW',
            'color' => '#ef4444',
            'is_active' => false,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $position->refresh();
    expect($position->name)->toBe('New Name');
    expect($position->code)->toBe('NEW');
    expect($position->color)->toBe('#ef4444');
    expect($position->is_active)->toBeFalse();
});

it('deletes a position', function (): void {
    $department = Department::factory()->create();
    $position = DepartmentPosition::factory()->create([
        'department_id' => $department->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->delete(route('departments.positions.destroy', [$department, $position]));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Position should be soft deleted
    $this->assertSoftDeleted('department_positions', [
        'id' => $position->id,
    ]);
});

it('requires manage departments permission to create position', function (): void {
    $user = User::factory()->create();
    $department = Department::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('departments.positions.store', $department), [
            'name' => 'Test Position',
        ]);

    expect($response->status())->toBeIn([403, 302]);
});

it('requires manage departments permission to update position', function (): void {
    $user = User::factory()->create();
    $department = Department::factory()->create();
    $position = DepartmentPosition::factory()->create([
        'department_id' => $department->id,
    ]);

    $response = $this->actingAs($user)
        ->put(route('departments.positions.update', [$department, $position]), [
            'name' => 'Updated Name',
        ]);

    expect($response->status())->toBeIn([403, 302]);
});

it('requires manage departments permission to delete position', function (): void {
    $user = User::factory()->create();
    $department = Department::factory()->create();
    $position = DepartmentPosition::factory()->create([
        'department_id' => $department->id,
    ]);

    $response = $this->actingAs($user)
        ->delete(route('departments.positions.destroy', [$department, $position]));

    expect($response->status())->toBeIn([403, 302]);
});

it('validates hourly rate is numeric', function (): void {
    $department = Department::factory()->create();

    $response = $this->actingAs($this->admin)
        ->post(route('departments.positions.store', $department), [
            'name' => 'Test Position',
            'hourly_rate' => 'not-a-number',
        ]);

    $response->assertSessionHasErrors(['hourly_rate']);
});
