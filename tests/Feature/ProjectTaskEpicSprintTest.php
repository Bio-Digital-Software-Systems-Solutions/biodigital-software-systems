<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTaskEpicSprintTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create([
            'project_manager_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function can_assign_epic_to_task()
    {
        $epic = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'epic',
            'reporter_id' => $this->user->id,
        ]);

        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}", [
                'epic_id' => $epic->id,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('project_tasks', [
            'id' => $task->id,
            'epic_id' => $epic->id,
        ]);
    }

    /** @test */
    public function can_assign_both_epic_and_sprint_to_task()
    {
        $epic = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'epic',
            'reporter_id' => $this->user->id,
        ]);

        $sprint = Sprint::factory()->create([
            'project_id' => $this->project->id,
            'start_date' => now(),
            'end_date' => now()->addWeeks(2),
        ]);

        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}", [
                'epic_id' => $epic->id,
                'sprint_id' => $sprint->id,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('project_tasks', [
            'id' => $task->id,
            'epic_id' => $epic->id,
            'sprint_id' => $sprint->id,
        ]);
    }

    /** @test */
    public function can_remove_epic_from_task()
    {
        $epic = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'epic',
            'reporter_id' => $this->user->id,
        ]);

        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'epic_id' => $epic->id,
            'reporter_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}", [
                'epic_id' => null,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('project_tasks', [
            'id' => $task->id,
            'epic_id' => null,
        ]);
    }

    /** @test */
    public function show_page_includes_epics_and_sprints()
    {
        // Create and give user permission to view programs (tasks)
        \Spatie\Permission\Models\Permission::create(['name' => 'view programs']);
        $this->user->givePermissionTo('view programs');

        $epic1 = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'epic',
            'reporter_id' => $this->user->id,
        ]);

        $epic2 = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'epic',
            'reporter_id' => $this->user->id,
        ]);

        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/tasks/{$task->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('ProjectTasks/Show')
            ->has('epics')
        );

        // Verify both epics are in the response
        $epics = $response->viewData('page')['props']['epics'];
        $epicIds = collect($epics)->pluck('id')->toArray();
        $this->assertContains($epic1->id, $epicIds);
        $this->assertContains($epic2->id, $epicIds);
    }

    /** @test */
    public function epic_must_exist_in_project_tasks_table()
    {
        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}", [
                'epic_id' => 99999,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('epic_id');
    }

    /** @test */
    public function sprint_must_exist_in_sprints_table()
    {
        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}", [
                'sprint_id' => 99999,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('sprint_id');
    }

    /** @test */
    public function can_create_task_with_epic_and_sprint()
    {
        $epic = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'type' => 'epic',
            'reporter_id' => $this->user->id,
        ]);

        $sprint = Sprint::factory()->create([
            'project_id' => $this->project->id,
            'start_date' => now(),
            'end_date' => now()->addWeeks(2),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/tasks', [
                'title' => 'New Task with Epic and Sprint',
                'description' => 'Test description',
                'project_id' => $this->project->id,
                'status' => 'todo',
                'priority' => 'medium',
                'type' => 'task',
                'epic_id' => $epic->id,
                'sprint_id' => $sprint->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('project_tasks', [
            'title' => 'New Task with Epic and Sprint',
            'epic_id' => $epic->id,
            'sprint_id' => $sprint->id,
        ]);
    }
}
