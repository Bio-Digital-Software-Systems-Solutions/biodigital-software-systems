<?php

use App\Models\Department;
use App\Models\Routine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::create(['name' => 'manage departments']);
    Permission::create(['name' => 'view departments']);

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo(['manage departments', 'view departments']);

    $this->department = Department::factory()->create([
        'head_of_department' => $this->admin->id,
    ]);
    $this->department->users()->attach($this->admin);

    $this->member = User::factory()->create();
    $this->member->givePermissionTo('view departments');
    $this->department->users()->attach($this->member);
});

it('lists routines for a department', function () {
    Routine::factory()->count(3)->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->get("/departments/{$this->department->uuid}/routines");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Departments/Routines/Index')
        ->has('routines.data', 3)
    );
});

it('creates a routine', function () {
    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines", [
            'name' => 'Routine de test',
            'description' => 'Description de la routine',
            'frequency' => 'weekly',
            'responsible_id' => $this->member->id,
            'estimated_duration_minutes' => 60,
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('routines', [
        'department_id' => $this->department->id,
        'name' => 'Routine de test',
        'status' => 'draft',
        'frequency' => 'weekly',
        'responsible_id' => $this->member->id,
        'created_by' => $this->admin->id,
    ]);
});

it('shows a routine with details', function () {
    $routine = Routine::factory()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->get("/departments/{$this->department->uuid}/routines/{$routine->uuid}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Departments/Routines/Show')
        ->has('routine')
        ->has('departmentUsers')
    );
});

it('updates a routine', function () {
    $routine = Routine::factory()->draft()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->put("/departments/{$this->department->uuid}/routines/{$routine->uuid}", [
            'name' => 'Routine modifiée',
            'description' => 'Nouvelle description',
            'frequency' => 'daily',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('routines', [
        'id' => $routine->id,
        'name' => 'Routine modifiée',
        'frequency' => 'daily',
    ]);
});

it('deletes a draft routine', function () {
    $routine = Routine::factory()->draft()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->delete("/departments/{$this->department->uuid}/routines/{$routine->uuid}");

    $response->assertRedirect();
    $this->assertSoftDeleted('routines', ['id' => $routine->id]);
});

it('prevents deleting non-draft routine', function () {
    $routine = Routine::factory()->active()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->delete("/departments/{$this->department->uuid}/routines/{$routine->uuid}");

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertNotSoftDeleted('routines', ['id' => $routine->id]);
});

it('submits routine for approval', function () {
    $routine = Routine::factory()->draft()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$routine->uuid}/submit");

    $response->assertRedirect();
    $this->assertDatabaseHas('routines', [
        'id' => $routine->id,
        'status' => 'pending_approval',
    ]);
});

it('approves a pending routine', function () {
    $routine = Routine::factory()->pendingApproval()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$routine->uuid}/approve");

    $response->assertRedirect();
    $this->assertDatabaseHas('routines', [
        'id' => $routine->id,
        'status' => 'approved',
        'approved_by' => $this->admin->id,
    ]);
});

it('rejects a pending routine back to draft', function () {
    $routine = Routine::factory()->pendingApproval()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$routine->uuid}/reject");

    $response->assertRedirect();
    $this->assertDatabaseHas('routines', [
        'id' => $routine->id,
        'status' => 'draft',
    ]);
});

it('activates an approved routine', function () {
    $routine = Routine::factory()->approved()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$routine->uuid}/activate");

    $response->assertRedirect();
    $this->assertDatabaseHas('routines', [
        'id' => $routine->id,
        'status' => 'active',
        'is_active' => true,
    ]);
});

it('archives an active routine', function () {
    $routine = Routine::factory()->active()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$routine->uuid}/archive");

    $response->assertRedirect();
    $this->assertDatabaseHas('routines', [
        'id' => $routine->id,
        'status' => 'archived',
        'is_active' => false,
    ]);
});

it('prevents invalid status transitions', function () {
    $routine = Routine::factory()->draft()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$routine->uuid}/approve");

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('routines', [
        'id' => $routine->id,
        'status' => 'draft',
    ]);
});

it('prevents unauthorized access', function () {
    $unauthorized = User::factory()->create();

    $response = $this->actingAs($unauthorized)
        ->get("/departments/{$this->department->uuid}/routines");

    // Unauthorized users are redirected (302) or forbidden (403)
    expect($response->status())->toBeIn([302, 403]);
});

it('filters routines by status', function () {
    Routine::factory()->draft()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
        'name' => 'Draft routine',
    ]);
    Routine::factory()->active()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
        'name' => 'Active routine',
    ]);

    $response = $this->actingAs($this->admin)
        ->get("/departments/{$this->department->uuid}/routines?status=draft");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('routines.data', 1)
    );
});

it('adds an assignee to a routine', function () {
    $routine = Routine::factory()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$routine->uuid}/assignees", [
            'user_id' => $this->member->id,
            'role' => 'assignee',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('routine_assignees', [
        'routine_id' => $routine->id,
        'user_id' => $this->member->id,
        'role' => 'assignee',
    ]);
});
