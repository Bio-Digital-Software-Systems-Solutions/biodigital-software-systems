<?php

use App\Enums\ProjectStatus;
use App\Models\Program;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create permissions
    Permission::create(['name' => 'view tasks']);
    Permission::create(['name' => 'create tasks']);
    Permission::create(['name' => 'edit tasks']);
    Permission::create(['name' => 'delete tasks']);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo(['view tasks', 'create tasks', 'edit tasks', 'delete tasks']);

    $this->program = Program::factory()->create(['user_id' => $this->user->id]);

    // Create all statuses
    $this->pendingStatus = Status::factory()->create(['name' => 'pending']);
    $this->todoStatus = Status::factory()->create(['name' => 'todo']);
    $this->inProgressStatus = Status::factory()->create(['name' => 'in_progress']);
    $this->underReviewStatus = Status::factory()->create(['name' => 'under_review']);
    $this->completedStatus = Status::factory()->create(['name' => 'completed']);
    $this->cancelledStatus = Status::factory()->create(['name' => 'cancelled']);
    $this->blockedStatus = Status::factory()->create(['name' => 'blocked']);
    $this->onHoldStatus = Status::factory()->create(['name' => 'on_hold']);
});

// =========================================
// Task Progress Auto-Sync Tests (Model Level)
// =========================================

it('sets progress to 100 when status changes to completed', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->inProgressStatus->id,
        'progress' => 50,
    ]);

    $task->update(['status_id' => $this->completedStatus->id]);
    $task->refresh();

    expect($task->progress)->toBe(100);
});

it('sets progress to 0 when status changes to pending', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->completedStatus->id,
        'progress' => 100,
    ]);

    $task->update(['status_id' => $this->pendingStatus->id]);
    $task->refresh();

    expect($task->progress)->toBe(0);
});

it('sets progress to 0 when status changes to todo', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->inProgressStatus->id,
        'progress' => 30,
    ]);

    $task->update(['status_id' => $this->todoStatus->id]);
    $task->refresh();

    expect($task->progress)->toBe(0);
});

it('sets progress to 0 when status changes to cancelled', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->inProgressStatus->id,
        'progress' => 60,
    ]);

    $task->update(['status_id' => $this->cancelledStatus->id]);
    $task->refresh();

    expect($task->progress)->toBe(0);
});

it('sets minimum progress of 10 when status changes to in_progress from 0', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->pendingStatus->id,
        'progress' => 0,
    ]);

    $task->update(['status_id' => $this->inProgressStatus->id]);
    $task->refresh();

    expect($task->progress)->toBe(10);
});

it('keeps existing progress when status changes to in_progress if already above 10', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->blockedStatus->id,
        'progress' => 45,
    ]);

    $task->update(['status_id' => $this->inProgressStatus->id]);
    $task->refresh();

    expect($task->progress)->toBe(45);
});

it('sets minimum progress of 75 when status changes to under_review from lower', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->inProgressStatus->id,
        'progress' => 50,
    ]);

    $task->update(['status_id' => $this->underReviewStatus->id]);
    $task->refresh();

    expect($task->progress)->toBe(75);
});

it('keeps existing progress when status changes to under_review if already above 75', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->inProgressStatus->id,
        'progress' => 90,
    ]);

    $task->update(['status_id' => $this->underReviewStatus->id]);
    $task->refresh();

    expect($task->progress)->toBe(90);
});

it('keeps current progress when status changes to blocked', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->inProgressStatus->id,
        'progress' => 35,
    ]);

    $task->update(['status_id' => $this->blockedStatus->id]);
    $task->refresh();

    expect($task->progress)->toBe(35);
});

it('keeps current progress when status changes to on_hold', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->inProgressStatus->id,
        'progress' => 55,
    ]);

    $task->update(['status_id' => $this->onHoldStatus->id]);
    $task->refresh();

    expect($task->progress)->toBe(55);
});

// =========================================
// Toggle Complete Endpoint Tests
// =========================================

it('sets progress to 100 when toggling to completed via endpoint', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->todoStatus->id,
        'progress' => 0,
    ]);

    $this->actingAs($this->user)
        ->patch(route('tasks.toggle-complete', $task->uuid));

    $task->refresh();

    expect($task->status_id)->toBe($this->completedStatus->id)
        ->and($task->progress)->toBe(100);
});

it('resets progress to 0 when toggling to incomplete via endpoint', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->completedStatus->id,
        'progress' => 100,
    ]);

    $this->actingAs($this->user)
        ->patch(route('tasks.toggle-complete', $task->uuid));

    $task->refresh();

    expect($task->status_id)->toBe($this->pendingStatus->id)
        ->and($task->progress)->toBe(0);
});

// =========================================
// Bulk Toggle Tests
// =========================================

it('syncs progress for all tasks in bulk toggle to completed', function (): void {
    $task1 = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->todoStatus->id,
        'progress' => 0,
    ]);

    $task2 = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->inProgressStatus->id,
        'progress' => 40,
    ]);

    $this->actingAs($this->user)
        ->post(route('tasks.bulk-toggle-complete'), [
            'task_ids' => [$task1->id, $task2->id],
            'completed' => true,
        ]);

    $task1->refresh();
    $task2->refresh();

    expect($task1->progress)->toBe(100)
        ->and($task2->progress)->toBe(100);
});

it('syncs progress for all tasks in bulk toggle to incomplete', function (): void {
    $task1 = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->completedStatus->id,
        'progress' => 100,
    ]);

    $task2 = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->completedStatus->id,
        'progress' => 100,
    ]);

    $this->actingAs($this->user)
        ->post(route('tasks.bulk-toggle-complete'), [
            'task_ids' => [$task1->id, $task2->id],
            'completed' => false,
        ]);

    $task1->refresh();
    $task2->refresh();

    expect($task1->progress)->toBe(0)
        ->and($task2->progress)->toBe(0);
});

// =========================================
// Update Status via API Endpoint
// =========================================

it('syncs progress when status is updated via API updateStatus endpoint', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->pendingStatus->id,
        'progress' => 0,
    ]);

    $this->actingAs($this->user)
        ->patchJson(route('api.tasks.updateStatus', $task->uuid), [
            'status_id' => $this->completedStatus->id,
        ]);

    $task->refresh();

    expect($task->progress)->toBe(100);
});

// =========================================
// Project Auto-Completion Tests
// =========================================

it('auto-completes project when all tasks become completed', function (): void {
    $project = Project::factory()->create([
        'status' => ProjectStatus::ACTIVE,
    ]);

    $task1 = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->completedStatus->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $project->id,
    ]);

    $task2 = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->inProgressStatus->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $project->id,
    ]);

    // Complete the last remaining task
    $task2->update(['status_id' => $this->completedStatus->id]);

    $project->refresh();

    expect($project->status)->toBe(ProjectStatus::COMPLETED);
});

it('does not auto-complete project when some tasks remain incomplete', function (): void {
    $project = Project::factory()->create([
        'status' => ProjectStatus::ACTIVE,
    ]);

    $task1 = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->inProgressStatus->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $project->id,
    ]);

    $task2 = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->inProgressStatus->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $project->id,
    ]);

    // Complete only one task
    $task1->update(['status_id' => $this->completedStatus->id]);

    $project->refresh();

    expect($project->status)->toBe(ProjectStatus::ACTIVE);
});

it('does not auto-complete a cancelled project even when all tasks are completed', function (): void {
    $project = Project::factory()->create([
        'status' => ProjectStatus::CANCELLED,
    ]);

    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->inProgressStatus->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $project->id,
    ]);

    $task->update(['status_id' => $this->completedStatus->id]);

    $project->refresh();

    expect($project->status)->toBe(ProjectStatus::CANCELLED);
});

it('does not auto-complete an on_hold project even when all tasks are completed', function (): void {
    $project = Project::factory()->create([
        'status' => ProjectStatus::ON_HOLD,
    ]);

    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->inProgressStatus->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $project->id,
    ]);

    $task->update(['status_id' => $this->completedStatus->id]);

    $project->refresh();

    expect($project->status)->toBe(ProjectStatus::ON_HOLD);
});

// =========================================
// Edge Cases
// =========================================

it('does not change progress when status_id is not changed', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->inProgressStatus->id,
        'progress' => 50,
    ]);

    // Update only the title, not the status
    $task->update(['title' => 'Updated title']);
    $task->refresh();

    expect($task->progress)->toBe(50);
});

it('handles task creation without affecting progress', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->pendingStatus->id,
        'progress' => 0,
    ]);

    // Progress should remain 0 on creation (observer only acts on update)
    expect($task->progress)->toBe(0);
});

it('handles sequential status transitions correctly', function (): void {
    $task = Task::factory()->create([
        'program_id' => $this->program->id,
        'status_id' => $this->pendingStatus->id,
        'progress' => 0,
    ]);

    // pending → in_progress: 0 → 10
    $task->update(['status_id' => $this->inProgressStatus->id]);
    $task->refresh();
    expect($task->progress)->toBe(10);

    // in_progress → under_review: 10 → 75
    $task->update(['status_id' => $this->underReviewStatus->id]);
    $task->refresh();
    expect($task->progress)->toBe(75);

    // under_review → completed: 75 → 100
    $task->update(['status_id' => $this->completedStatus->id]);
    $task->refresh();
    expect($task->progress)->toBe(100);

    // completed → pending (reopened): 100 → 0
    $task->update(['status_id' => $this->pendingStatus->id]);
    $task->refresh();
    expect($task->progress)->toBe(0);
});
