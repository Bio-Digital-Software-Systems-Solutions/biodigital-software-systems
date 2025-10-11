<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KanbanTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $admin;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view programs']);

        // Create roles
        $memberRole = Role::create(['name' => 'Member']);
        $adminRole = Role::create(['name' => 'Admin']);

        // Assign permissions
        $memberRole->givePermissionTo('view programs');
        $adminRole->givePermissionTo('view programs');

        // Create users
        $this->user = User::factory()->create();
        $this->user->assignRole('Member');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');

        // Create a project
        $this->project = Project::factory()->create([
            'name' => 'Test Project',
            'project_manager_id' => $this->admin->id,
        ]);
    }

    /** @test */
    public function user_can_view_kanban_board()
    {
        $this->actingAs($this->user)
            ->get(route('kanban.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kanban/Index')
                ->has('tasksByStatus')
                ->has('projects')
                ->has('users')
                ->has('sprints')
            );
    }

    /** @test */
    public function kanban_displays_tasks_grouped_by_status()
    {
        // Create tasks with different statuses
        $todoTask = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'todo',
            'assignee_id' => $this->user->id,
            'reporter_id' => $this->admin->id,
        ]);

        $inProgressTask = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'in_progress',
            'assignee_id' => $this->user->id,
            'reporter_id' => $this->admin->id,
        ]);

        $doneTask = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'done',
            'assignee_id' => $this->user->id,
            'reporter_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index'))
            ->assertOk();

        $tasks = $response->viewData('page')['props']['tasksByStatus'];

        $this->assertCount(1, $tasks['todo']);
        $this->assertCount(1, $tasks['in_progress']);
        $this->assertCount(1, $tasks['done']);
        $this->assertEquals($todoTask->id, $tasks['todo'][0]['id']);
    }

    /** @test */
    public function kanban_can_filter_by_project()
    {
        $project2 = Project::factory()->create([
            'name' => 'Another Project',
            'project_manager_id' => $this->admin->id,
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'todo',
            'assignee_id' => $this->user->id,
            'reporter_id' => $this->admin->id,
        ]);

        ProjectTask::factory()->create([
            'project_id' => $project2->id,
            'status' => 'todo',
            'assignee_id' => $this->user->id,
            'reporter_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['project_id' => $this->project->id]))
            ->assertOk();

        $tasks = $response->viewData('page')['props']['tasksByStatus'];
        $allTasks = collect($tasks)->flatten(1);

        $this->assertCount(1, $allTasks);
        $this->assertEquals($this->project->id, $allTasks[0]['project_id']);
    }

    /** @test */
    public function kanban_can_filter_by_assignee()
    {
        $user2 = User::factory()->create();

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'todo',
            'assignee_id' => $this->user->id,
            'reporter_id' => $this->admin->id,
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'todo',
            'assignee_id' => $user2->id,
            'reporter_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['assignee_id' => $this->user->id]))
            ->assertOk();

        $tasks = $response->viewData('page')['props']['tasksByStatus'];
        $allTasks = collect($tasks)->flatten(1);

        $this->assertCount(1, $allTasks);
        $this->assertEquals($this->user->id, $allTasks[0]['assignee_id']);
    }

    /** @test */
    public function kanban_can_filter_by_priority()
    {
        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'todo',
            'priority' => 'high',
            'assignee_id' => $this->user->id,
            'reporter_id' => $this->admin->id,
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'todo',
            'priority' => 'low',
            'assignee_id' => $this->user->id,
            'reporter_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['priority' => 'high']))
            ->assertOk();

        $tasks = $response->viewData('page')['props']['tasksByStatus'];
        $allTasks = collect($tasks)->flatten(1);

        $this->assertCount(1, $allTasks);
        $this->assertEquals('high', $allTasks[0]['priority']);
    }

    /** @test */
    public function kanban_can_filter_by_type()
    {
        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'todo',
            'type' => 'bug',
            'assignee_id' => $this->user->id,
            'reporter_id' => $this->admin->id,
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'todo',
            'type' => 'feature',
            'assignee_id' => $this->user->id,
            'reporter_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['type' => 'bug']))
            ->assertOk();

        $tasks = $response->viewData('page')['props']['tasksByStatus'];
        $allTasks = collect($tasks)->flatten(1);

        $this->assertCount(1, $allTasks);
        $this->assertEquals('bug', $allTasks[0]['type']);
    }

    /** @test */
    public function kanban_can_search_tasks()
    {
        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Fix authentication bug',
            'status' => 'todo',
            'assignee_id' => $this->user->id,
            'reporter_id' => $this->admin->id,
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Add new feature',
            'status' => 'todo',
            'assignee_id' => $this->user->id,
            'reporter_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['search' => 'authentication']))
            ->assertOk();

        $tasks = $response->viewData('page')['props']['tasksByStatus'];
        $allTasks = collect($tasks)->flatten(1);

        $this->assertCount(1, $allTasks);
        $this->assertStringContainsString('authentication', $allTasks[0]['title']);
    }

    /** @test */
    public function kanban_can_filter_by_sprint()
    {
        $sprint = Sprint::factory()->create([
            'project_id' => $this->project->id,
            'start_date' => now(),
            'end_date' => now()->addWeeks(2),
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'sprint_id' => $sprint->id,
            'status' => 'todo',
            'assignee_id' => $this->user->id,
            'reporter_id' => $this->admin->id,
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'sprint_id' => null,
            'status' => 'todo',
            'assignee_id' => $this->user->id,
            'reporter_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['sprint_id' => $sprint->id]))
            ->assertOk();

        $tasks = $response->viewData('page')['props']['tasksByStatus'];
        $allTasks = collect($tasks)->flatten(1);

        $this->assertCount(1, $allTasks);
        $this->assertEquals($sprint->id, $allTasks[0]['sprint_id']);
    }

    /** @test */
    public function user_without_permission_cannot_view_kanban()
    {
        $userWithoutPermission = User::factory()->create();

        $this->actingAs($userWithoutPermission)
            ->get(route('kanban.index'))
            ->assertForbidden();
    }

    /** @test */
    public function guest_cannot_view_kanban()
    {
        $this->get(route('kanban.index'))
            ->assertRedirect(route('login'));
    }
}
