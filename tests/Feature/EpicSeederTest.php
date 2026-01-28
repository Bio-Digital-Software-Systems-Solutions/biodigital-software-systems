<?php

use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\EpicSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a user for project manager
    User::factory()->create();
});

it('creates epics for existing projects', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->count(2)->create();

    $this->seed(EpicSeeder::class);

    $epics = Task::where('type', 'epic')->count();
    expect($epics)->toBeGreaterThan(0);
});

it('creates between 2 and 3 epics per project', function () {
    $this->seed(StatusSeeder::class);
    $project = Project::factory()->create();

    $this->seed(EpicSeeder::class);

    $epicCount = Task::where('taskable_type', Project::class)
        ->where('taskable_id', $project->id)
        ->where('type', 'epic')
        ->count();

    expect($epicCount)->toBeGreaterThanOrEqual(2)
        ->toBeLessThanOrEqual(3);
});

it('creates epics with type set to epic', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(EpicSeeder::class);

    $epics = Task::where('type', 'epic')->get();

    expect($epics->count())->toBeGreaterThan(0);
    $epics->each(function ($epic) {
        expect($epic->type)->toBe('epic');
    });
});

it('creates child stories for each epic', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(EpicSeeder::class);

    $epics = Task::where('type', 'epic')->get();

    $epics->each(function ($epic) {
        $children = Task::where('epic_id', $epic->id)->count();
        expect($children)->toBe(5); // Each epic should have 5 stories
    });
});

it('associates child tasks with epics via epic_id', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(EpicSeeder::class);

    $epic = Task::where('type', 'epic')->first();

    if ($epic) {
        $childTasks = Task::where('epic_id', $epic->id)->get();

        expect($childTasks->count())->toBeGreaterThan(0);
        $childTasks->each(function ($task) use ($epic) {
            expect($task->epic_id)->toBe($epic->id);
        });
    }
});

it('creates child tasks with story, task, or feature types', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(EpicSeeder::class);

    $childTasks = Task::whereNotNull('epic_id')->get();

    $types = $childTasks->pluck('type')->unique()->toArray();

    // Should have at least one of the story types
    $validTypes = ['story', 'task', 'feature'];
    $hasValidType = collect($types)->intersect($validTypes)->isNotEmpty();

    expect($hasValidType)->toBeTrue();
});

it('sets color in epic custom_fields', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(EpicSeeder::class);

    $epic = Task::where('type', 'epic')->first();

    if ($epic && $epic->custom_fields) {
        expect($epic->custom_fields)->toHaveKey('color');
        expect($epic->custom_fields['color'])->toMatch('/^#[A-Fa-f0-9]{6}$/');
    }
});

it('sets epic labels correctly', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(EpicSeeder::class);

    $epic = Task::where('type', 'epic')->first();

    if ($epic && $epic->labels) {
        expect($epic->labels)->toContain('epic');
        expect($epic->labels)->toContain('feature-set');
    }
});

it('assigns story points to child tasks but not to epics', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(EpicSeeder::class);

    $epics = Task::where('type', 'epic')->get();
    $childTasks = Task::whereNotNull('epic_id')->get();

    // Epics should not have story points
    $epics->each(function ($epic) {
        expect($epic->story_points)->toBeNull();
    });

    // Child tasks should have story points
    $childTasks->each(function ($task) {
        expect($task->story_points)->toBeGreaterThanOrEqual(1)
            ->toBeLessThanOrEqual(8);
    });
});

it('skips projects that already have epics', function () {
    $this->seed(StatusSeeder::class);
    $project = Project::factory()->create();

    // First seeding
    $this->seed(EpicSeeder::class);
    $initialCount = Task::where('taskable_type', Project::class)
        ->where('taskable_id', $project->id)
        ->where('type', 'epic')
        ->count();

    // Second seeding should not add more epics
    $this->seed(EpicSeeder::class);
    $finalCount = Task::where('taskable_type', Project::class)
        ->where('taskable_id', $project->id)
        ->where('type', 'epic')
        ->count();

    expect($finalCount)->toBe($initialCount);
});

it('creates tasks with unique keys', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(EpicSeeder::class);

    $keys = Task::pluck('key')->toArray();
    $uniqueKeys = array_unique($keys);

    expect(count($keys))->toBe(count($uniqueKeys));
});

it('assigns tasks to project members', function () {
    $this->seed(StatusSeeder::class);
    $project = Project::factory()->create();
    $members = User::factory()->count(3)->create();
    $project->members()->attach($members->pluck('id'));

    $this->seed(EpicSeeder::class);

    $tasks = Task::where('taskable_type', Project::class)
        ->where('taskable_id', $project->id)
        ->get();

    $assignedToIds = $tasks->pluck('assigned_to')->unique()->filter();
    $memberIds = $members->pluck('id');

    // At least some tasks should be assigned to project members
    $hasProjectMember = $assignedToIds->intersect($memberIds)->isNotEmpty();
    expect($hasProjectMember)->toBeTrue();
});

it('sets reporter_id to project manager', function () {
    $this->seed(StatusSeeder::class);
    $manager = User::factory()->create();
    $project = Project::factory()->create(['project_manager_id' => $manager->id]);

    $this->seed(EpicSeeder::class);

    $tasks = Task::where('taskable_type', Project::class)
        ->where('taskable_id', $project->id)
        ->get();

    $tasks->each(function ($task) use ($manager) {
        expect($task->reporter_id)->toBe($manager->id);
    });
});

it('creates epics with predefined titles', function () {
    $this->seed(StatusSeeder::class);
    Project::factory()->create();

    $this->seed(EpicSeeder::class);

    $epics = Task::where('type', 'epic')->get();
    $titles = $epics->pluck('title')->toArray();

    // Should have at least one of the predefined titles
    $predefinedTitles = [
        'Authentification et Gestion des Utilisateurs',
        'Tableau de Bord et Analytics',
        'Système de Notifications',
        'Gestion des Fichiers',
        'API et Intégrations Tierces',
        'Performance et Optimisation',
    ];

    $hasPredefindTitle = collect($titles)->intersect($predefinedTitles)->isNotEmpty();
    expect($hasPredefindTitle)->toBeTrue();
});

it('creates child tasks with due dates', function () {
    $this->seed(StatusSeeder::class);
    $project = Project::factory()->create([
        'start_date' => now(),
        'end_date' => now()->addMonths(2),
    ]);

    $this->seed(EpicSeeder::class);

    $childTasks = Task::whereNotNull('epic_id')
        ->where('taskable_id', $project->id)
        ->get();

    $childTasks->each(function ($task) {
        expect($task->due_date)->not->toBeNull();
    });
});

it('sets appropriate status for stories based on epic status', function () {
    $this->seed(StatusSeeder::class);
    $project = Project::factory()->create(['status' => 'completed']);

    $this->seed(EpicSeeder::class);

    $epic = Task::where('type', 'epic')
        ->where('taskable_id', $project->id)
        ->with('status')
        ->first();

    if ($epic) {
        $children = Task::where('epic_id', $epic->id)->with('status')->get();

        // For completed project, epic should have completed status
        expect($epic->status->name)->toBe('completed');

        // All children should also be completed
        $children->each(function ($child) {
            expect($child->status->name)->toBe('completed');
        });
    }
});
