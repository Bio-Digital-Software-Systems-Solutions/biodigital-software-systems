<?php

use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create permissions
    Permission::firstOrCreate(['name' => 'view projects']);
    Permission::firstOrCreate(['name' => 'create projects']);
    Permission::firstOrCreate(['name' => 'edit projects']);
    Permission::firstOrCreate(['name' => 'delete projects']);
    Permission::firstOrCreate(['name' => 'view tasks']);
    Permission::firstOrCreate(['name' => 'create tasks']);
    Permission::firstOrCreate(['name' => 'edit tasks']);
    Permission::firstOrCreate(['name' => 'delete tasks']);

    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $adminRole->syncPermissions([
        'view projects', 'create projects', 'edit projects', 'delete projects',
        'view tasks', 'create tasks', 'edit tasks', 'delete tasks',
    ]);

    $memberRole = Role::firstOrCreate(['name' => 'member']);
    $memberRole->syncPermissions(['view projects', 'view tasks']);

    // Create statuses using factory
    Status::factory()->pending()->create();
    Status::factory()->inProgress()->create();
    Status::factory()->completed()->create();
});

it('allows authenticated user with permission to create a task for a project', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $status = Status::where('name', 'pending')->first();

    $taskData = [
        'title' => 'New Task',
        'description' => 'This is a detailed task description for the project.',
        'priority' => 'high',
        'status_id' => $status->id,
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Tâche créée avec succès',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'task' => [
                'id',
                'uuid',
                'title',
                'description',
                'priority',
                'status',
            ],
        ]);

    $this->assertDatabaseHas('tasks', [
        'title' => 'New Task',
        'description' => 'This is a detailed task description for the project.',
        'priority' => 'high',
        'project_id' => $project->id,
        'taskable_type' => 'App\\Models\\Project',
        'taskable_id' => $project->id,
        'reporter_id' => $user->id,
    ]);
});

it('creates task with all optional fields', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $assignee = User::factory()->create();

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $status = Status::where('name', 'in_progress')->first();

    $taskData = [
        'title' => 'Complete Feature Implementation',
        'description' => 'Implement the full feature with all edge cases handled properly.',
        'priority' => 'medium',
        'status_id' => $status->id,
        'due_date' => now()->addDays(7)->format('Y-m-d'),
        'estimated_hours' => 8.5,
        'assigned_to' => $assignee->id,
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(201);

    $this->assertDatabaseHas('tasks', [
        'title' => 'Complete Feature Implementation',
        'priority' => 'medium',
        'assigned_to' => $assignee->id,
        'estimated_hours' => 8.5,
    ]);

    // Verify the due date is set correctly
    $task = Task::where('title', 'Complete Feature Implementation')->first();
    expect($task->due_date)->not->toBeNull();
    expect($task->assigned_to)->toBe($assignee->id);
});

it('validates required title field', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $status = Status::where('name', 'pending')->first();

    $taskData = [
        'description' => 'This is a detailed task description.',
        'priority' => 'high',
        'status_id' => $status->id,
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

it('validates required description field', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $status = Status::where('name', 'pending')->first();

    $taskData = [
        'title' => 'New Task',
        'priority' => 'high',
        'status_id' => $status->id,
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['description']);
});

it('validates description minimum length', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $status = Status::where('name', 'pending')->first();

    $taskData = [
        'title' => 'New Task',
        'description' => 'Too short', // Less than 10 characters
        'priority' => 'high',
        'status_id' => $status->id,
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['description']);
});

it('validates required priority field', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $status = Status::where('name', 'pending')->first();

    $taskData = [
        'title' => 'New Task',
        'description' => 'This is a detailed task description.',
        'status_id' => $status->id,
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['priority']);
});

it('validates priority values', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $status = Status::where('name', 'pending')->first();

    $taskData = [
        'title' => 'New Task',
        'description' => 'This is a detailed task description.',
        'priority' => 'invalid_priority',
        'status_id' => $status->id,
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['priority']);
});

it('validates required status_id field', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $taskData = [
        'title' => 'New Task',
        'description' => 'This is a detailed task description.',
        'priority' => 'high',
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status_id']);
});

it('validates status exists', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $taskData = [
        'title' => 'New Task',
        'description' => 'This is a detailed task description.',
        'priority' => 'high',
        'status_id' => 99999, // Non-existent status
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status_id']);
});

it('validates due_date must be after today', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $status = Status::where('name', 'pending')->first();

    $taskData = [
        'title' => 'New Task',
        'description' => 'This is a detailed task description.',
        'priority' => 'high',
        'status_id' => $status->id,
        'due_date' => now()->subDay()->format('Y-m-d'), // Yesterday
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['due_date']);
});

it('validates assigned_to user exists', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $status = Status::where('name', 'pending')->first();

    $taskData = [
        'title' => 'New Task',
        'description' => 'This is a detailed task description.',
        'priority' => 'high',
        'status_id' => $status->id,
        'assigned_to' => 99999, // Non-existent user
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['assigned_to']);
});

it('validates estimated_hours is not negative', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $status = Status::where('name', 'pending')->first();

    $taskData = [
        'title' => 'New Task',
        'description' => 'This is a detailed task description.',
        'priority' => 'high',
        'status_id' => $status->id,
        'estimated_hours' => -5,
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['estimated_hours']);
});

it('denies access to user without create tasks permission', function () {
    $user = User::factory()->create();
    $user->assignRole('member'); // Member only has view permission

    $project = Project::factory()->create();

    $status = Status::where('name', 'pending')->first();

    $taskData = [
        'title' => 'New Task',
        'description' => 'This is a detailed task description.',
        'priority' => 'high',
        'status_id' => $status->id,
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(403);
});

it('denies access to unauthenticated user', function () {
    $project = Project::factory()->create();

    $taskData = [
        'title' => 'New Task',
        'description' => 'This is a detailed task description.',
        'priority' => 'high',
        'status_id' => 1,
    ];

    $response = $this->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(401);
});

it('returns 404 for non-existent project', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $status = Status::where('name', 'pending')->first();

    $taskData = [
        'title' => 'New Task',
        'description' => 'This is a detailed task description.',
        'priority' => 'high',
        'status_id' => $status->id,
    ];

    $response = $this->actingAs($user)
        ->postJson('/api/projects/non-existent-uuid/tasks', $taskData);

    $response->assertStatus(404);
});

it('sets the current user as reporter_id', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $status = Status::where('name', 'pending')->first();

    $taskData = [
        'title' => 'Reporter Test Task',
        'description' => 'This task should have the current user as reporter.',
        'priority' => 'low',
        'status_id' => $status->id,
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(201);

    $this->assertDatabaseHas('tasks', [
        'title' => 'Reporter Test Task',
        'reporter_id' => $user->id,
    ]);
});

it('creates task with correct polymorphic relation', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $status = Status::where('name', 'pending')->first();

    $taskData = [
        'title' => 'Polymorphic Test Task',
        'description' => 'This task tests the polymorphic relation setup.',
        'priority' => 'medium',
        'status_id' => $status->id,
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(201);

    $task = Task::where('title', 'Polymorphic Test Task')->first();

    expect($task->taskable_type)->toBe('App\\Models\\Project');
    expect($task->taskable_id)->toBe($project->id);
    expect($task->project_id)->toBe($project->id);
    expect($task->taskable)->toBeInstanceOf(Project::class);
    expect($task->taskable->id)->toBe($project->id);
});

it('accepts valid priority values', function (string $priority) {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $status = Status::where('name', 'pending')->first();

    $taskData = [
        'title' => "Task with {$priority} priority",
        'description' => 'This is a detailed task description for testing.',
        'priority' => $priority,
        'status_id' => $status->id,
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(201);

    $this->assertDatabaseHas('tasks', [
        'priority' => $priority,
    ]);
})->with(['low', 'medium', 'high']);

it('loads task relations in response', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $assignee = User::factory()->create();

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $status = Status::where('name', 'pending')->first();

    $taskData = [
        'title' => 'Task with Relations',
        'description' => 'This is a detailed task description for testing.',
        'priority' => 'high',
        'status_id' => $status->id,
        'assigned_to' => $assignee->id,
    ];

    $response = $this->actingAs($user)
        ->postJson("/api/projects/{$project->uuid}/tasks", $taskData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'task' => [
                'status' => ['id', 'name'],
                'assignee' => ['id', 'first_name', 'last_name'],
                'reporter' => ['id', 'first_name', 'last_name'],
            ],
        ]);
});

it('project show page includes statuses for task creation', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $project = Project::factory()->create([
        'project_manager_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->get("/projects/{$project->uuid}");

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->has('statuses')
        );
});
