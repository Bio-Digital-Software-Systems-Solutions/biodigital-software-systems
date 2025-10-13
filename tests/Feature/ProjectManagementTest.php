<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_project(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/projects', [
            'name' => 'Test Project',
            'description' => 'A test project',
            'status' => 'planning',
            'priority' => 'high',
            'color' => '#3b82f6',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'status' => 'planning',
        ]);
    }

    public function test_user_can_list_projects(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user->id, ['is_lead' => true, 'started_at' => now()]);

        $response = $this->actingAs($user)->getJson('/api/projects');

        $response->assertStatus(200);
    }

    public function test_user_can_create_task(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/tasks', [
            'title' => 'Test Task',
            'description' => 'Task description',
            'project_id' => $project->id,
            'status' => 'todo',
            'priority' => 'medium',
            'type' => 'task',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('project_tasks', [
            'title' => 'Test Task',
            'project_id' => $project->id,
        ]);
    }

    public function test_user_can_update_task_status(): void
    {
        $user = User::factory()->create();
        $task = ProjectTask::factory()->create([
            'reporter_id' => $user->id,
            'status' => 'todo',
        ]);

        $response = $this->actingAs($user)->patchJson("/api/tasks/{$task->uuid}/status", [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('project_tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_project_progress_calculation(): void
    {
        $project = Project::factory()->create();

        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'status' => 'done',
        ]);

        ProjectTask::factory()->create([
            'project_id' => $project->id,
            'status' => 'todo',
        ]);

        $this->assertEquals(50.0, $project->fresh()->progress);
    }
}
