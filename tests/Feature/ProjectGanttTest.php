<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectGanttTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('view projects');

        $this->project = Project::factory()->create([
            'name' => 'Test Gantt Project',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);
    }

    public function test_can_view_project_gantt(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('projects.gantt', $this->project->uuid));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Projects/Gantt')
            ->has('project')
            ->where('project.uuid', $this->project->uuid)
        );
    }

    public function test_gantt_includes_tasks_with_due_date_only(): void
    {
        // Get or create status
        $status = Status::firstOrCreate(
            ['name' => 'in_progress'],
            ['description' => 'In Progress', 'color' => '#3B82F6']
        );

        // Task with only due_date (no start_date)
        $taskWithDueDate = Task::factory()->create([
            'title' => 'Task with due date',
            'taskable_type' => Project::class,
            'taskable_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'due_date' => now()->addDays(7),
            'status_id' => $status->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.gantt', $this->project->uuid));

        $response->assertOk();

        $project = $response->viewData('page')['props']['project'];
        $taskUuids = collect($project['tasks'])->pluck('uuid')->toArray();

        // Task with due_date should be included
        $this->assertContains($taskWithDueDate->uuid, $taskUuids);
    }

    public function test_gantt_includes_tasks_with_created_at_and_due_date(): void
    {
        $status = Status::firstOrCreate(
            ['name' => 'todo'],
            ['description' => 'To Do', 'color' => '#6B7280']
        );

        // Task with created_at (automatic) and due_date
        $task = Task::factory()->create([
            'title' => 'Task with dates',
            'taskable_type' => Project::class,
            'taskable_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'due_date' => now()->addDays(14),
            'status_id' => $status->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.gantt', $this->project->uuid));

        $response->assertOk();

        $project = $response->viewData('page')['props']['project'];

        // Find the task in the response
        $foundTask = collect($project['tasks'])->firstWhere('uuid', $task->uuid);

        $this->assertNotNull($foundTask);
        $this->assertNotNull($foundTask['created_at']);
        $this->assertNotNull($foundTask['due_date']);
    }

    public function test_gantt_includes_task_status(): void
    {
        $status = Status::firstOrCreate(
            ['name' => 'in_progress'],
            ['description' => 'In Progress', 'color' => '#3B82F6']
        );

        $task = Task::factory()->create([
            'title' => 'In Progress Task',
            'taskable_type' => Project::class,
            'taskable_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'due_date' => now()->addDays(7),
            'status_id' => $status->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.gantt', $this->project->uuid));

        $response->assertOk();

        $project = $response->viewData('page')['props']['project'];
        $foundTask = collect($project['tasks'])->firstWhere('uuid', $task->uuid);

        $this->assertNotNull($foundTask);
        $this->assertNotNull($foundTask['status']);
        $this->assertEquals('in_progress', $foundTask['status']['name']);
    }

    public function test_gantt_includes_task_assignee(): void
    {
        $assignee = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $status = Status::firstOrCreate(
            ['name' => 'todo'],
            ['description' => 'To Do', 'color' => '#6B7280']
        );

        $task = Task::factory()->create([
            'title' => 'Assigned Task',
            'taskable_type' => Project::class,
            'taskable_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'assigned_to' => $assignee->id,
            'due_date' => now()->addDays(7),
            'status_id' => $status->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.gantt', $this->project->uuid));

        $response->assertOk();

        $project = $response->viewData('page')['props']['project'];
        $foundTask = collect($project['tasks'])->firstWhere('uuid', $task->uuid);

        $this->assertNotNull($foundTask);
        $this->assertNotNull($foundTask['assignee']);
        $this->assertEquals('Jane', $foundTask['assignee']['first_name']);
        $this->assertEquals('Smith', $foundTask['assignee']['last_name']);
    }

    public function test_gantt_displays_tasks_without_dates_as_undefined(): void
    {
        $status = Status::firstOrCreate(
            ['name' => 'todo'],
            ['description' => 'To Do', 'color' => '#6B7280']
        );

        // Task without any dates
        $taskWithoutDates = Task::factory()->create([
            'title' => 'Task without dates',
            'taskable_type' => Project::class,
            'taskable_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'due_date' => null,
            'status_id' => $status->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.gantt', $this->project->uuid));

        $response->assertOk();

        $project = $response->viewData('page')['props']['project'];
        $foundTask = collect($project['tasks'])->firstWhere('uuid', $taskWithoutDates->uuid);

        // Task should be included but without due_date
        $this->assertNotNull($foundTask);
        $this->assertNull($foundTask['due_date']);
    }

    public function test_gantt_loads_multiple_tasks(): void
    {
        $status = Status::firstOrCreate(
            ['name' => 'todo'],
            ['description' => 'To Do', 'color' => '#6B7280']
        );

        // Create multiple tasks
        Task::factory()->count(5)->create([
            'taskable_type' => Project::class,
            'taskable_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'due_date' => now()->addDays(7),
            'status_id' => $status->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.gantt', $this->project->uuid));

        $response->assertOk();

        $project = $response->viewData('page')['props']['project'];

        $this->assertCount(5, $project['tasks']);
    }

    public function test_unauthorized_user_cannot_access_project_gantt(): void
    {
        $unauthorizedUser = User::factory()->create();

        $response = $this->actingAs($unauthorizedUser)
            ->get(route('projects.gantt', $this->project->uuid));

        // Middleware redirects to home when unauthorized
        $response->assertStatus(302);
    }

    public function test_guest_cannot_access_project_gantt(): void
    {
        $response = $this->get(route('projects.gantt', $this->project->uuid));

        $response->assertRedirect(route('login'));
    }

    public function test_gantt_shows_task_priority(): void
    {
        $status = Status::firstOrCreate(
            ['name' => 'todo'],
            ['description' => 'To Do', 'color' => '#6B7280']
        );

        $highPriorityTask = Task::factory()->create([
            'title' => 'High Priority Task',
            'taskable_type' => Project::class,
            'taskable_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'priority' => 'high',
            'due_date' => now()->addDays(7),
            'status_id' => $status->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.gantt', $this->project->uuid));

        $response->assertOk();

        $project = $response->viewData('page')['props']['project'];
        $foundTask = collect($project['tasks'])->firstWhere('uuid', $highPriorityTask->uuid);

        $this->assertNotNull($foundTask);
        $this->assertEquals('high', $foundTask['priority']);
    }
}
