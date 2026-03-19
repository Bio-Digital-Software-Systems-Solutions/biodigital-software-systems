<?php

use App\Enums\RoutineStepValidationStatus;
use App\Models\Department;
use App\Models\Routine;
use App\Models\RoutineStep;
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

    $this->routine = Routine::factory()->draft()->create([
        'department_id' => $this->department->id,
        'created_by' => $this->admin->id,
    ]);
});

it('adds a step to a routine', function () {
    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$this->routine->uuid}/steps", [
            'name' => 'Étape 1',
            'description' => 'Première étape',
            'duration_minutes' => 30,
            'is_required' => true,
            'requires_validation' => true,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('routine_steps', [
        'routine_id' => $this->routine->id,
        'name' => 'Étape 1',
        'duration_minutes' => 30,
        'parent_id' => null,
    ]);
});

it('adds a sub-step to a step', function () {
    $parentStep = RoutineStep::factory()->create([
        'routine_id' => $this->routine->id,
        'name' => 'Étape parente',
    ]);

    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$this->routine->uuid}/steps", [
            'name' => 'Sous-étape 1',
            'description' => 'Une sous-étape',
            'parent_id' => $parentStep->id,
            'is_required' => true,
            'requires_validation' => false,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('routine_steps', [
        'routine_id' => $this->routine->id,
        'name' => 'Sous-étape 1',
        'parent_id' => $parentStep->id,
    ]);
});

it('updates a step', function () {
    $step = RoutineStep::factory()->create([
        'routine_id' => $this->routine->id,
        'name' => 'Ancien nom',
    ]);

    $response = $this->actingAs($this->admin)
        ->put("/departments/{$this->department->uuid}/routines/{$this->routine->uuid}/steps/{$step->uuid}", [
            'name' => 'Nouveau nom',
            'description' => 'Description mise à jour',
            'is_required' => true,
            'requires_validation' => true,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('routine_steps', [
        'id' => $step->id,
        'name' => 'Nouveau nom',
    ]);
});

it('deletes a step', function () {
    $step = RoutineStep::factory()->create([
        'routine_id' => $this->routine->id,
    ]);

    $response = $this->actingAs($this->admin)
        ->delete("/departments/{$this->department->uuid}/routines/{$this->routine->uuid}/steps/{$step->uuid}");

    $response->assertRedirect();
    $this->assertSoftDeleted('routine_steps', ['id' => $step->id]);
});

it('reorders steps', function () {
    $step1 = RoutineStep::factory()->create([
        'routine_id' => $this->routine->id,
        'sort_order' => 0,
    ]);
    $step2 = RoutineStep::factory()->create([
        'routine_id' => $this->routine->id,
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$this->routine->uuid}/steps/reorder", [
            'steps' => [
                ['id' => $step1->id, 'sort_order' => 1, 'parent_id' => null],
                ['id' => $step2->id, 'sort_order' => 0, 'parent_id' => null],
            ],
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('routine_steps', ['id' => $step1->id, 'sort_order' => 1]);
    $this->assertDatabaseHas('routine_steps', ['id' => $step2->id, 'sort_order' => 0]);
});

it('validates a step', function () {
    $step = RoutineStep::factory()->create([
        'routine_id' => $this->routine->id,
        'requires_validation' => true,
        'validation_status' => RoutineStepValidationStatus::Pending,
    ]);

    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$this->routine->uuid}/steps/{$step->uuid}/validate", [
            'notes' => 'Tout est en ordre',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('routine_steps', [
        'id' => $step->id,
        'validation_status' => 'validated',
        'validated_by' => $this->admin->id,
    ]);
});

it('rejects a step with reason', function () {
    $step = RoutineStep::factory()->create([
        'routine_id' => $this->routine->id,
        'requires_validation' => true,
        'validation_status' => RoutineStepValidationStatus::Pending,
    ]);

    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$this->routine->uuid}/steps/{$step->uuid}/reject", [
            'notes' => 'Manque des informations',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('routine_steps', [
        'id' => $step->id,
        'validation_status' => 'rejected',
        'validation_notes' => 'Manque des informations',
    ]);
});

it('assigns a person to a step', function () {
    $step = RoutineStep::factory()->create([
        'routine_id' => $this->routine->id,
    ]);

    $member = User::factory()->create();
    $this->department->users()->attach($member);

    $response = $this->actingAs($this->admin)
        ->post("/departments/{$this->department->uuid}/routines/{$this->routine->uuid}/assignees", [
            'user_id' => $member->id,
            'role' => 'assignee',
            'routine_step_id' => $step->id,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('routine_assignees', [
        'routine_id' => $this->routine->id,
        'routine_step_id' => $step->id,
        'user_id' => $member->id,
        'role' => 'assignee',
    ]);
});
