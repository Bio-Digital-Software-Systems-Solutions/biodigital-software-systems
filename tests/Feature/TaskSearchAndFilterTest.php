<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaskSearchAndFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view programs']);
        Permission::create(['name' => 'manage programs']);

        // Create role with permissions
        $role = Role::create(['name' => 'project-manager']);
        $role->givePermissionTo('view programs');

        $this->user = User::factory()->create();
        $this->user->assignRole('project-manager');

        $this->project = Project::factory()->create(['name' => 'Test Project']);
    }

    /** @test */
    public function it_can_search_tasks_by_title()
    {
        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'title' => 'Fix login bug',
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'title' => 'Add new feature',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/tasks?search=login');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('ProjectTasks/Index')
            ->where('tasks.total', 1)
        );
    }

    /** @test */
    public function it_can_search_tasks_by_description()
    {
        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'title' => 'Task 1',
            'description' => 'Need to fix the authentication system',
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'title' => 'Task 2',
            'description' => 'Update documentation',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/tasks?search=authentication');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('ProjectTasks/Index')->where('tasks.total', 1));
    }

    /** @test */
    public function it_can_search_tasks_by_key()
    {
        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'key' => 'PROJ-123',
            'title' => 'Task 1',
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'key' => 'PROJ-456',
            'title' => 'Task 2',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/tasks?search=123');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('ProjectTasks/Index')->where('tasks.total', 1));
    }

    /** @test */
    public function it_can_filter_tasks_by_status()
    {
        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'todo',
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'in_progress',
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'done',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/tasks?status=in_progress');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('ProjectTasks/Index')->where('tasks.total', 1));
    }

    /** @test */
    public function it_can_filter_tasks_by_priority()
    {
        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'priority' => 'low',
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'priority' => 'high',
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'priority' => 'high',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/tasks?priority=high');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('ProjectTasks/Index')->where('tasks.total', 2));
    }

    /** @test */
    public function it_can_filter_tasks_by_type()
    {
        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'type' => 'task',
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'type' => 'bug',
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'type' => 'feature',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/tasks?type=bug');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('ProjectTasks/Index')->where('tasks.total', 1));
    }

    /** @test */
    public function it_can_filter_tasks_by_project()
    {
        $project2 = Project::factory()->create(['name' => 'Another Project']);

        ProjectTask::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        ProjectTask::factory()->count(2)->create([
            'project_id' => $project2->id,
            'reporter_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/tasks?project_id='.$this->project->id);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('ProjectTasks/Index')->where('tasks.total', 3));
    }

    /** @test */
    public function it_can_filter_tasks_by_assignee()
    {
        $assignee1 = User::factory()->create();
        $assignee2 = User::factory()->create();

        ProjectTask::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'assignee_id' => $assignee1->id,
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'assignee_id' => $assignee2->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/tasks?assignee_id='.$assignee1->id);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('ProjectTasks/Index')->where('tasks.total', 2));
    }

    /** @test */
    public function it_can_combine_multiple_filters()
    {
        $assignee = User::factory()->create();

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'in_progress',
            'priority' => 'high',
            'assignee_id' => $assignee->id,
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'in_progress',
            'priority' => 'low',
            'assignee_id' => $assignee->id,
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'done',
            'priority' => 'high',
            'assignee_id' => $assignee->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/tasks?status=in_progress&priority=high&assignee_id='.$assignee->id);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('ProjectTasks/Index')->where('tasks.total', 1));
    }

    /** @test */
    public function it_can_combine_search_with_filters()
    {
        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'title' => 'Fix authentication bug',
            'status' => 'in_progress',
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'title' => 'Fix payment bug',
            'status' => 'done',
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'title' => 'Authentication improvements',
            'status' => 'todo',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/tasks?search=authentication&status=in_progress');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('ProjectTasks/Index')->where('tasks.total', 1));
    }

    /** @test */
    public function search_is_case_insensitive()
    {
        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'title' => 'Fix Authentication Bug',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/tasks?search=authentication');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('ProjectTasks/Index')->where('tasks.total', 1));
    }

    /** @test */
    public function it_returns_all_tasks_when_no_filters_applied()
    {
        ProjectTask::factory()->count(15)->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/tasks');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('ProjectTasks/Index')->where('tasks.total', 15));
    }

    /** @test */
    public function it_paginates_results_correctly()
    {
        ProjectTask::factory()->count(25)->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/tasks');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('ProjectTasks/Index')
            ->where('tasks.total', 25)
            ->where('tasks.per_page', 20)
            ->where('tasks.last_page', 2)
        );
    }

    /** @test */
    public function it_preserves_filters_in_pagination()
    {
        ProjectTask::factory()->count(25)->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/tasks?status=in_progress&page=2');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('ProjectTasks/Index')->where('tasks.current_page', 2));
    }

    /** @test */
    public function it_returns_empty_results_when_no_matches_found()
    {
        ProjectTask::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/tasks?search=nonexistentterm');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('ProjectTasks/Index')->where('tasks.total', 0));
    }

    /** @test */
    public function unauthorized_users_cannot_access_tasks_index()
    {
        $response = $this->get('/tasks');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function users_without_permission_cannot_view_tasks()
    {
        $userWithoutPermission = User::factory()->create();
        $roleWithoutPermission = Role::create(['name' => 'Guest']);
        $userWithoutPermission->assignRole('Guest');

        $response = $this->actingAs($userWithoutPermission)
            ->get('/tasks');

        $response->assertForbidden();
    }
}
