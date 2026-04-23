<?php

use App\Models\Agile\UserStory;
use App\Models\Task;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('allows project-manager to manage story tasks', function (): void {
    $pm = asRole('project-manager');
    $story = UserStory::factory()->create();
    $task = Task::factory()->asStoryTask($story)->create();

    expect($pm->can('viewAny', Task::class))->toBeTrue()
        ->and($pm->can('create', Task::class))->toBeTrue()
        ->and($pm->can('update', $task))->toBeTrue()
        ->and($pm->can('delete', $task))->toBeTrue();
});

it('allows assignee to update their own task', function (): void {
    $assignee = asRole('member');
    $story = UserStory::factory()->create();
    $task = Task::factory()->asStoryTask($story)->create(['assigned_to' => $assignee->id]);

    expect($assignee->can('view', $task))->toBeTrue()
        ->and($assignee->can('update', $task))->toBeTrue();
});

it('denies member from creating or deleting story tasks', function (): void {
    $member = asRole('member');
    $task = Task::factory()->create();

    expect($member->can('create', Task::class))->toBeFalse()
        ->and($member->can('delete', $task))->toBeFalse();
});
