<?php

use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create required statuses
    $this->todoStatus = Status::factory()->create(['name' => 'todo', 'color' => '#6b7280']);
    $this->pendingStatus = Status::factory()->pending()->create();
    $this->inProgressStatus = Status::factory()->inProgress()->create();
    $this->underReviewStatus = Status::factory()->create(['name' => 'under_review', 'color' => '#f59e0b']);
    $this->completedStatus = Status::factory()->completed()->create();
    $this->cancelledStatus = Status::factory()->cancelled()->create();

    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['project_manager_id' => $this->user->id]);

    // Create epic
    $this->epic = Task::factory()->create([
        'type' => 'epic',
        'title' => 'Test Epic',
        'status_id' => $this->todoStatus->id,
        'progress' => 0,
        'taskable_type' => Project::class,
        'taskable_id' => $this->project->id,
        'reporter_id' => $this->user->id,
        'program_id' => null,
    ]);
});

it('syncs epic to in_progress when a child task moves to in_progress', function (): void {
    $task = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->pendingStatus->id,
        'progress' => 0,
        'reporter_id' => $this->user->id,
        'program_id' => null,
    ]);

    $task->update(['status_id' => $this->inProgressStatus->id]);

    $this->epic->refresh();
    expect($this->epic->status->name)->toBe('in_progress');
    expect($this->epic->progress)->toBe(0);
});

it('syncs epic progress based on completed child tasks ratio', function (): void {
    $task1 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task3 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    // Complete 1 of 3 tasks → 33%
    $task1->update(['status_id' => $this->completedStatus->id]);

    $this->epic->refresh();
    expect($this->epic->progress)->toBe(33);
    expect($this->epic->status->name)->toBe('in_progress');
});

it('syncs epic to completed when all child tasks are completed', function (): void {
    $task1 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->completedStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    // Complete the second task
    $task2->update(['status_id' => $this->completedStatus->id]);

    $this->epic->refresh();
    expect($this->epic->progress)->toBe(100);
    expect($this->epic->status->name)->toBe('completed');
});

it('syncs epic to completed when all tasks are completed or cancelled', function (): void {
    $task1 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->completedStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    // Cancel the second task
    $task2->update(['status_id' => $this->cancelledStatus->id]);

    $this->epic->refresh();
    // Only 1 of 2 is completed (cancelled doesn't count as completed for progress)
    expect($this->epic->progress)->toBe(50);
    expect($this->epic->status->name)->toBe('completed');
});

it('syncs epic to pending when all child tasks are pending', function (): void {
    Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->todoStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $this->epic->refresh();
    expect($this->epic->status->name)->toBe('pending');
    expect($this->epic->progress)->toBe(0);
});

it('syncs epic to under_review when tasks are under review and none in progress', function (): void {
    $task1 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    // Move one task to under_review
    $task1->update(['status_id' => $this->underReviewStatus->id]);

    $this->epic->refresh();
    expect($this->epic->status->name)->toBe('under_review');
    expect($this->epic->progress)->toBe(0);
});

it('recalculates epic when a child task is deleted', function (): void {
    $task1 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->completedStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->completedStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task3 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    // 2 of 3 completed → 67%
    $this->epic->refresh();
    expect($this->epic->progress)->toBe(67);

    // Delete the pending task → 2 of 2 completed → 100%
    $task3->delete();

    $this->epic->refresh();
    expect($this->epic->progress)->toBe(100);
    expect($this->epic->status->name)->toBe('completed');
});

it('recalculates epic when a child task is restored', function (): void {
    $task1 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->completedStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    // Delete the pending task → 1 of 1 completed
    $task2->delete();
    $this->epic->refresh();
    expect($this->epic->progress)->toBe(100);

    // Restore → back to 1 of 2 completed → 50%
    $task2->restore();
    $this->epic->refresh();
    expect($this->epic->progress)->toBe(50);
    expect($this->epic->status->name)->toBe('in_progress');
});

it('does not sync when the task has no epic', function (): void {
    $task = Task::factory()->create([
        'epic_id' => null,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task->update(['status_id' => $this->completedStatus->id]);

    $this->epic->refresh();
    // Epic should remain unchanged
    expect($this->epic->status->name)->toBe('todo');
    expect($this->epic->progress)->toBe(0);
});

it('sets epic progress to 0 when all child tasks are removed', function (): void {
    $task = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->completedStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $this->epic->refresh();
    expect($this->epic->progress)->toBe(100);

    $task->delete();

    $this->epic->refresh();
    expect($this->epic->progress)->toBe(0);
});

it('keeps epic in_progress when mix of completed and in_progress tasks', function (): void {
    $task1 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->completedStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    $task2 = Task::factory()->create([
        'epic_id' => $this->epic->id,
        'status_id' => $this->pendingStatus->id,
        'program_id' => null,
        'reporter_id' => $this->user->id,
    ]);

    // Move task2 to in_progress
    $task2->update(['status_id' => $this->inProgressStatus->id]);

    $this->epic->refresh();
    expect($this->epic->status->name)->toBe('in_progress');
    expect($this->epic->progress)->toBe(50);
});

it('syncs epic progress incrementally as tasks complete one by one', function (): void {
    $tasks = [];
    for ($i = 0; $i < 4; $i++) {
        $tasks[] = Task::factory()->create([
            'epic_id' => $this->epic->id,
            'status_id' => $this->pendingStatus->id,
            'program_id' => null,
            'reporter_id' => $this->user->id,
        ]);
    }

    // Complete tasks one by one: 25%, 50%, 75%, 100%
    $tasks[0]->update(['status_id' => $this->completedStatus->id]);
    $this->epic->refresh();
    expect($this->epic->progress)->toBe(25);

    $tasks[1]->update(['status_id' => $this->completedStatus->id]);
    $this->epic->refresh();
    expect($this->epic->progress)->toBe(50);

    $tasks[2]->update(['status_id' => $this->completedStatus->id]);
    $this->epic->refresh();
    expect($this->epic->progress)->toBe(75);

    $tasks[3]->update(['status_id' => $this->completedStatus->id]);
    $this->epic->refresh();
    expect($this->epic->progress)->toBe(100);
    expect($this->epic->status->name)->toBe('completed');
});
