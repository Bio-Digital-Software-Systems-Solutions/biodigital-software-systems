<?php

use App\Enums\Agile\StoryTaskType;
use App\Models\Agile\UserStory;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores a story task in the legacy tasks table via polymorphic taskable', function (): void {
    $story = UserStory::factory()->create();
    $task = Task::factory()->asStoryTask($story)->withWorkType(StoryTaskType::DEV)->create();

    expect($task->taskable_type)->toBe(UserStory::class)
        ->and($task->taskable_id)->toBe($story->id)
        ->and($task->type)->toBe('task')
        ->and($task->work_type)->toBe(StoryTaskType::DEV);
});

it('exposes story tasks from a user story via the morphMany relation', function (): void {
    $story = UserStory::factory()->create();
    Task::factory()->asStoryTask($story)->withWorkType(StoryTaskType::TEST)->count(2)->create();
    Task::factory()->asStoryTask($story)->withWorkType(StoryTaskType::DEVOPS)->create();

    expect($story->storyTasks)->toHaveCount(3);
});

it('leaves work_type null on legacy tasks not bound to a user story', function (): void {
    $task = Task::factory()->create();

    expect($task->work_type)->toBeNull();
});
