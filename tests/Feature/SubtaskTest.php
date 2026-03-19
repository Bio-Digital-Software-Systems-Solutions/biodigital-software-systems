<?php

use App\Models\Program;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view tasks']);
    Permission::firstOrCreate(['name' => 'create tasks']);
    Permission::firstOrCreate(['name' => 'edit tasks']);
    Permission::firstOrCreate(['name' => 'delete tasks']);

    $this->user = User::factory()->create();
    $this->program = Program::factory()->create(['user_id' => $this->user->id]);
    $this->status = Status::factory()->create(['name' => 'todo']);
});

it('loads subtasks in show response', function () {
    $this->user->givePermissionTo('view tasks');

    $parentTask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
    ]);

    Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
        'parent_id' => $parentTask->id,
        'title' => 'My Subtask',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('tasks.show', $parentTask));

    $response->assertOk();

    // Verify subtasks are loaded on the task model
    $parentTask->load('subtasks.status', 'subtasks.assignedUser');
    expect($parentTask->subtasks)->toHaveCount(1)
        ->and($parentTask->subtasks->first()->title)->toBe('My Subtask');
});

it('can create a subtask for a task', function () {
    $this->user->givePermissionTo('create tasks');

    $parentTask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
    ]);

    $subtaskData = [
        'title' => 'New Subtask',
        'description' => 'Subtask description',
        'priority' => 'medium',
        'status_id' => $this->status->id,
    ];

    $response = $this->actingAs($this->user)
        ->post(route('tasks.subtasks.store', $parentTask->uuid), $subtaskData);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Sous-tâche créée avec succès.');

    $this->assertDatabaseHas('tasks', [
        'title' => 'New Subtask',
        'parent_id' => $parentTask->id,
        'program_id' => $parentTask->program_id,
    ]);
});

it('inherits parent task context when creating a subtask', function () {
    $this->user->givePermissionTo('create tasks');

    $project = \App\Models\Project::factory()->create();

    $parentTask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
        'project_id' => $project->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $project->id,
    ]);

    $subtaskData = [
        'title' => 'Inherited Context Subtask',
        'priority' => 'low',
        'status_id' => $this->status->id,
    ];

    $this->actingAs($this->user)
        ->post(route('tasks.subtasks.store', $parentTask->uuid), $subtaskData);

    $subtask = Task::where('title', 'Inherited Context Subtask')->first();

    expect($subtask)->not->toBeNull()
        ->and($subtask->parent_id)->toBe($parentTask->id)
        ->and($subtask->project_id)->toBe($project->id)
        ->and($subtask->program_id)->toBe($parentTask->program_id)
        ->and($subtask->taskable_type)->toBe(\App\Models\Project::class)
        ->and($subtask->taskable_id)->toBe($project->id);
});

it('validates required fields when creating a subtask', function () {
    $this->user->givePermissionTo('create tasks');

    $parentTask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
    ]);

    $response = $this->actingAs($this->user)
        ->post(route('tasks.subtasks.store', $parentTask->uuid), []);

    $response->assertSessionHasErrors(['title', 'priority', 'status_id']);
});

it('can delete a subtask', function () {
    $this->user->givePermissionTo('delete tasks');

    $parentTask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
    ]);

    $subtask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
        'parent_id' => $parentTask->id,
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('tasks.subtasks.destroy', [
            'task' => $parentTask->uuid,
            'subtask' => $subtask->uuid,
        ]));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Sous-tâche supprimée avec succès.');

    $this->assertSoftDeleted($subtask);
});

it('cannot delete a subtask that does not belong to the parent task', function () {
    $this->user->givePermissionTo('delete tasks');

    $parentTask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
    ]);

    $otherTask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
    ]);

    $subtask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
        'parent_id' => $otherTask->id,
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('tasks.subtasks.destroy', [
            'task' => $parentTask->uuid,
            'subtask' => $subtask->uuid,
        ]));

    // App redirects with 'unauthorized' flash on 403
    $response->assertRedirect();
    $response->assertSessionHas('unauthorized');
});

it('requires create tasks permission to create subtask', function () {
    $parentTask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
    ]);

    $response = $this->actingAs($this->user)
        ->post(route('tasks.subtasks.store', $parentTask->uuid), [
            'title' => 'Unauthorized Subtask',
            'priority' => 'medium',
            'status_id' => $this->status->id,
        ]);

    // App redirects with 'unauthorized' flash on 403
    $response->assertRedirect();
    $response->assertSessionHas('unauthorized');

    $this->assertDatabaseMissing('tasks', ['title' => 'Unauthorized Subtask']);
});

it('requires delete tasks permission to delete subtask', function () {
    $parentTask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
    ]);

    $subtask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
        'parent_id' => $parentTask->id,
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('tasks.subtasks.destroy', [
            'task' => $parentTask->uuid,
            'subtask' => $subtask->uuid,
        ]));

    // App redirects with 'unauthorized' flash on 403
    $response->assertRedirect();
    $response->assertSessionHas('unauthorized');

    // Subtask should not be deleted
    $this->assertDatabaseHas('tasks', ['id' => $subtask->id, 'deleted_at' => null]);
});

it('can create multiple subtasks for a task', function () {
    $this->user->givePermissionTo('create tasks');

    $parentTask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
    ]);

    for ($i = 1; $i <= 3; $i++) {
        $this->actingAs($this->user)
            ->post(route('tasks.subtasks.store', $parentTask->uuid), [
                'title' => "Subtask {$i}",
                'priority' => 'medium',
                'status_id' => $this->status->id,
            ]);
    }

    expect($parentTask->subtasks()->count())->toBe(3);
});

it('has subtasks relationship on Task model', function () {
    $parentTask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
    ]);

    $subtask1 = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
        'parent_id' => $parentTask->id,
    ]);

    $subtask2 = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
        'parent_id' => $parentTask->id,
    ]);

    $parentTask->load('subtasks');

    expect($parentTask->subtasks)->toHaveCount(2)
        ->and($parentTask->subtasks->pluck('id')->toArray())
        ->toContain($subtask1->id, $subtask2->id);
});

it('logs activity when creating a subtask', function () {
    $this->user->givePermissionTo('create tasks');

    $parentTask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
    ]);

    $this->actingAs($this->user)
        ->post(route('tasks.subtasks.store', $parentTask->uuid), [
            'title' => 'Logged Subtask',
            'priority' => 'medium',
            'status_id' => $this->status->id,
        ]);

    $activity = \Spatie\Activitylog\Models\Activity::where('subject_type', Task::class)
        ->where('subject_id', $parentTask->id)
        ->where('event', 'subtask_added')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['type'])->toBe('subtask_added')
        ->and($activity->properties['subtask_title'])->toBe('Logged Subtask');
});

it('logs activity when deleting a subtask', function () {
    $this->user->givePermissionTo('delete tasks');

    $parentTask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
    ]);

    $subtask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
        'parent_id' => $parentTask->id,
        'title' => 'Subtask To Delete',
    ]);

    $this->actingAs($this->user)
        ->delete(route('tasks.subtasks.destroy', [
            'task' => $parentTask->uuid,
            'subtask' => $subtask->uuid,
        ]));

    $activity = \Spatie\Activitylog\Models\Activity::where('subject_type', Task::class)
        ->where('subject_id', $parentTask->id)
        ->where('event', 'subtask_removed')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['type'])->toBe('subtask_removed')
        ->and($activity->properties['subtask_title'])->toBe('Subtask To Delete');
});

it('has parent relationship on subtask', function () {
    $parentTask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
    ]);

    $subtask = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->status->id,
        'parent_id' => $parentTask->id,
    ]);

    $subtask->load('parent');

    expect($subtask->parent)->not->toBeNull()
        ->and($subtask->parent->id)->toBe($parentTask->id);
});
