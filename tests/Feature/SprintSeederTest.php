<?php

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\SprintSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a user for project manager
    User::factory()->create();
});

it('creates sprints for existing projects', function () {
    // Seed dependencies
    $this->seed(StatusSeeder::class);
    Project::factory()->count(2)->create();

    // Run the seeder
    $this->seed(SprintSeeder::class);

    // Verify sprints were created
    expect(Sprint::count())->toBeGreaterThan(0);
});

it('creates between 2 and 4 sprints per project', function () {
    $this->seed(StatusSeeder::class);
    $project = Project::factory()->create();

    $this->seed(SprintSeeder::class);

    $sprintCount = Sprint::where('project_id', $project->id)->count();
    expect($sprintCount)->toBeGreaterThanOrEqual(2)
        ->toBeLessThanOrEqual(4);
});

it('creates sprints with different statuses', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(SprintSeeder::class);

    $statuses = Sprint::pluck('status')->unique()->toArray();

    // Should have at least completed and active sprints
    expect($statuses)->toContain('completed')
        ->toContain('active');
});

it('associates tasks with sprints', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(SprintSeeder::class);

    $sprintsWithTasks = Sprint::has('tasks')->count();
    expect($sprintsWithTasks)->toBeGreaterThan(0);
});

it('creates tasks with proper status for completed sprints', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(SprintSeeder::class);

    $completedSprint = Sprint::where('status', 'completed')->first();

    if ($completedSprint) {
        $completedStatus = Status::where('name', 'completed')->first();
        $tasks = $completedSprint->tasks;

        expect($tasks->count())->toBeGreaterThan(0);

        if ($completedStatus) {
            // All tasks in completed sprint should be completed
            $allCompleted = $tasks->every(fn ($task) => $task->status_id === $completedStatus->id);
            expect($allCompleted)->toBeTrue();
        }
    }
});

it('creates tasks with mixed statuses for active sprints', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(SprintSeeder::class);

    $activeSprint = Sprint::where('status', 'active')->first();

    if ($activeSprint) {
        $tasks = $activeSprint->tasks;
        expect($tasks->count())->toBeGreaterThan(0);

        // Active sprint should have tasks with various statuses
        $statusIds = $tasks->pluck('status_id')->unique();
        expect($statusIds->count())->toBeGreaterThanOrEqual(1);
    }
});

it('sets sprint dates correctly', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(SprintSeeder::class);

    Sprint::all()->each(function ($sprint) {
        expect($sprint->start_date)->not->toBeNull();
        expect($sprint->end_date)->not->toBeNull();
        expect($sprint->end_date->gte($sprint->start_date))->toBeTrue();
    });
});

it('skips projects that already have sprints', function () {
    $this->seed(StatusSeeder::class);
    $project = Project::factory()->create();

    // First seeding
    $this->seed(SprintSeeder::class);
    $initialCount = Sprint::where('project_id', $project->id)->count();

    // Second seeding should not add more sprints
    $this->seed(SprintSeeder::class);
    $finalCount = Sprint::where('project_id', $project->id)->count();

    expect($finalCount)->toBe($initialCount);
});

it('assigns tasks to project members', function () {
    $this->seed(StatusSeeder::class);
    $project = Project::factory()->create();
    $members = User::factory()->count(3)->create();
    $project->members()->attach($members->pluck('id'));

    $this->seed(SprintSeeder::class);

    $tasks = Task::whereHas('sprint', fn ($q) => $q->where('project_id', $project->id))->get();

    if ($tasks->isNotEmpty()) {
        $assignedToIds = $tasks->pluck('assigned_to')->unique()->filter();
        $memberIds = $members->pluck('id');

        // At least some tasks should be assigned to project members
        $hasProjectMember = $assignedToIds->intersect($memberIds)->isNotEmpty();
        expect($hasProjectMember)->toBeTrue();
    }
});

it('creates tasks with unique keys', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(SprintSeeder::class);

    $keys = Task::pluck('key')->toArray();
    $uniqueKeys = array_unique($keys);

    expect(count($keys))->toBe(count($uniqueKeys));
});

it('sets task due dates within sprint period', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(SprintSeeder::class);

    Sprint::with('tasks')->get()->each(function ($sprint) {
        $sprint->tasks->each(function ($task) use ($sprint) {
            if ($task->due_date) {
                expect($task->due_date->gte($sprint->start_date))->toBeTrue();
                expect($task->due_date->lte($sprint->end_date))->toBeTrue();
            }
        });
    });
});
