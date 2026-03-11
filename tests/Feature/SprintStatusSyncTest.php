<?php

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->todoStatus = Status::factory()->create(['name' => 'todo', 'color' => '#6b7280']);
    $this->pendingStatus = Status::factory()->pending()->create();
    $this->inProgressStatus = Status::factory()->inProgress()->create();
    $this->completedStatus = Status::factory()->completed()->create();
    $this->cancelledStatus = Status::factory()->cancelled()->create();

    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['project_manager_id' => $this->user->id]);

    $this->sprint = Sprint::factory()->planned()->create([
        'project_id' => $this->project->id,
        'progress' => 0,
    ]);
});

it('syncs sprint to active when a task moves to in_progress', function () {
    $task = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->pendingStatus->id,
        'reporter_id' => $this->user->id,
        'program_id' => null,
    ]);

    $task->update(['status_id' => $this->inProgressStatus->id]);

    $this->sprint->refresh();
    expect($this->sprint->status)->toBe('active');
    expect($this->sprint->progress)->toBe(0);
});

it('syncs sprint progress based on completed tasks ratio', function () {
    $task1 = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task3 = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    // Complete 1 of 3 tasks → 33%
    $task1->update(['status_id' => $this->completedStatus->id]);

    $this->sprint->refresh();
    expect($this->sprint->progress)->toBe(33);
    expect($this->sprint->status)->toBe('active');
});

it('syncs sprint to completed when all tasks are completed', function () {
    $task1 = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->completedStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2->update(['status_id' => $this->completedStatus->id]);

    $this->sprint->refresh();
    expect($this->sprint->progress)->toBe(100);
    expect($this->sprint->status)->toBe('completed');
});

it('syncs sprint to completed when all tasks are completed or cancelled', function () {
    $task1 = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->completedStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2->update(['status_id' => $this->cancelledStatus->id]);

    $this->sprint->refresh();
    expect($this->sprint->progress)->toBe(50);
    expect($this->sprint->status)->toBe('completed');
});

it('syncs sprint to planned when all tasks are pending', function () {
    Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->todoStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $this->sprint->refresh();
    expect($this->sprint->status)->toBe('planned');
    expect($this->sprint->progress)->toBe(0);
});

it('recalculates sprint when a task is deleted', function () {
    $task1 = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->completedStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    // 1 of 2 completed → 50%, active
    $this->sprint->refresh();
    expect($this->sprint->progress)->toBe(50);

    // Delete the pending task → 1 of 1 completed → 100%
    $task2->delete();

    $this->sprint->refresh();
    expect($this->sprint->progress)->toBe(100);
    expect($this->sprint->status)->toBe('completed');
});

it('recalculates sprint when a task is restored', function () {
    $task1 = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->completedStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2->delete();
    $this->sprint->refresh();
    expect($this->sprint->progress)->toBe(100);

    $task2->restore();
    $this->sprint->refresh();
    expect($this->sprint->progress)->toBe(50);
    expect($this->sprint->status)->toBe('active');
});

it('does not sync when task has no sprint', function () {
    $task = Task::factory()->create([
        'sprint_id' => null,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task->update(['status_id' => $this->completedStatus->id]);

    $this->sprint->refresh();
    expect($this->sprint->status)->toBe('planned');
    expect($this->sprint->progress)->toBe(0);
});

it('does not override cancelled sprint status', function () {
    $this->sprint->update(['status' => 'cancelled']);

    $task = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task->update(['status_id' => $this->completedStatus->id]);

    $this->sprint->refresh();
    expect($this->sprint->status)->toBe('cancelled');
});

it('resets sprint to planned when all tasks are removed', function () {
    $task = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->completedStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $this->sprint->refresh();
    expect($this->sprint->progress)->toBe(100);

    $task->delete();

    $this->sprint->refresh();
    expect($this->sprint->progress)->toBe(0);
    expect($this->sprint->status)->toBe('planned');
});

it('syncs sprint progress incrementally as tasks complete', function () {
    $tasks = [];
    for ($i = 0; $i < 4; $i++) {
        $tasks[] = Task::factory()->create([
            'sprint_id' => $this->sprint->id,
            'status_id' => $this->pendingStatus->id,
            'program_id' => null,
            'reporter_id' => $this->user->id,
        ]);
    }

    $tasks[0]->update(['status_id' => $this->completedStatus->id]);
    $this->sprint->refresh();
    expect($this->sprint->progress)->toBe(25);
    expect($this->sprint->status)->toBe('active');

    $tasks[1]->update(['status_id' => $this->completedStatus->id]);
    $this->sprint->refresh();
    expect($this->sprint->progress)->toBe(50);

    $tasks[2]->update(['status_id' => $this->completedStatus->id]);
    $this->sprint->refresh();
    expect($this->sprint->progress)->toBe(75);

    $tasks[3]->update(['status_id' => $this->completedStatus->id]);
    $this->sprint->refresh();
    expect($this->sprint->progress)->toBe(100);
    expect($this->sprint->status)->toBe('completed');
});

it('keeps sprint active when mix of completed and in_progress tasks', function () {
    $task1 = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->completedStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'sprint_id' => $this->sprint->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2->update(['status_id' => $this->inProgressStatus->id]);

    $this->sprint->refresh();
    expect($this->sprint->status)->toBe('active');
    expect($this->sprint->progress)->toBe(50);
});
