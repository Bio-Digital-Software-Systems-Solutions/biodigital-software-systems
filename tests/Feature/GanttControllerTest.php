<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GanttControllerTest extends TestCase
{
    public $user;
    public $project;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('view programs');

        $this->project = Project::factory()->create([
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);
    }

    public function test_can_view_gantt_chart(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('gantt.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Gantt/Index')
            ->has('ganttData')
            ->has('projects')
            ->has('filters')
        );
    }

    public function test_gantt_uses_uuid_for_projects(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('gantt.index'));

        $response->assertOk();

        $ganttData = $response->viewData('page')['props']['ganttData'];

        if (count($ganttData) > 0) {
            $firstProject = $ganttData[0];

            // Verify project has UUID
            $this->assertArrayHasKey('uuid', $firstProject);
            $this->assertNotNull($firstProject['uuid']);

            // Verify ID is prefixed with 'project-' and uses UUID
            $this->assertStringStartsWith('project-', $firstProject['id']);
            $this->assertStringContainsString($firstProject['uuid'], $firstProject['id']);
        }
    }

    public function test_gantt_uses_uuid_for_tasks(): void
    {
        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('gantt.index'));

        $response->assertOk();

        $ganttData = $response->viewData('page')['props']['ganttData'];

        foreach ($ganttData as $project) {
            if (isset($project['tasks']) && count($project['tasks']) > 0) {
                $firstTask = $project['tasks'][0];

                // Verify task has UUID
                $this->assertArrayHasKey('uuid', $firstTask);
                $this->assertNotNull($firstTask['uuid']);

                // Verify ID is prefixed with 'task-' and uses UUID
                $this->assertStringStartsWith('task-', $firstTask['id']);
                $this->assertStringContainsString($firstTask['uuid'], $firstTask['id']);

                break;
            }
        }
    }

    public function test_can_filter_gantt_by_project(): void
    {
        $project2 = Project::factory()->create([
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'due_date' => now()->addDays(7),
        ]);

        ProjectTask::factory()->create([
            'project_id' => $project2->id,
            'reporter_id' => $this->user->id,
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('gantt.index', ['project_id' => $this->project->id]));

        $response->assertOk();

        $ganttData = $response->viewData('page')['props']['ganttData'];

        // Should only have one project
        $this->assertCount(1, $ganttData);

        // Verify it's the correct project
        $projectIds = collect($ganttData)->pluck('uuid')->toArray();
        $this->assertContains($this->project->uuid, $projectIds);
        $this->assertNotContains($project2->uuid, $projectIds);
    }

    public function test_can_filter_gantt_by_status(): void
    {
        $activeProject = Project::factory()->create([
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $completedProject = Project::factory()->create([
            'status' => 'completed',
            'start_date' => now()->subDays(30),
            'end_date' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('gantt.index', ['status' => 'active']));

        $response->assertOk();

        $ganttData = $response->viewData('page')['props']['ganttData'];
        $projectUuids = collect($ganttData)->pluck('uuid')->toArray();

        $this->assertContains($activeProject->uuid, $projectUuids);
        $this->assertNotContains($completedProject->uuid, $projectUuids);
    }

    public function test_gantt_includes_tasks_with_dates(): void
    {
        // Task with dates - should be included
        $taskWithDates = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'due_date' => now()->addDays(7),
        ]);

        // Task without dates - might not be included based on the query
        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'due_date' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('gantt.index'));

        $response->assertOk();

        $ganttData = $response->viewData('page')['props']['ganttData'];

        foreach ($ganttData as $project) {
            if ($project['uuid'] === $this->project->uuid) {
                $taskUuids = collect($project['tasks'])->pluck('uuid')->toArray();

                // Task with dates should be included
                $this->assertContains($taskWithDates->uuid, $taskUuids);

                break;
            }
        }
    }

    public function test_gantt_calculates_project_progress(): void
    {
        // Create tasks with different statuses
        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'done',
            'due_date' => now()->addDays(7),
        ]);

        ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'in_progress',
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('gantt.index'));

        $response->assertOk();

        $ganttData = $response->viewData('page')['props']['ganttData'];

        foreach ($ganttData as $project) {
            if ($project['uuid'] === $this->project->uuid) {
                // With 1 done out of 2 tasks, progress should be 50%
                $this->assertEquals(50, $project['progress']);
                break;
            }
        }
    }

    public function test_gantt_task_shows_assignee_name(): void
    {
        $assignee = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'assignee_id' => $assignee->id,
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('gantt.index'));

        $response->assertOk();

        $ganttData = $response->viewData('page')['props']['ganttData'];

        foreach ($ganttData as $project) {
            if (isset($project['tasks'])) {
                foreach ($project['tasks'] as $ganttTask) {
                    if ($ganttTask['uuid'] === $task->uuid) {
                        $this->assertEquals('John Doe', $ganttTask['assignee']);
                        return;
                    }
                }
            }
        }
    }

    public function test_unauthorized_user_cannot_access_gantt(): void
    {
        $unauthorizedUser = User::factory()->create();

        $response = $this->actingAs($unauthorizedUser)
            ->get(route('gantt.index'));

        $response->assertStatus(302); // Redirect due to middleware
    }

    public function test_guest_cannot_access_gantt(): void
    {
        $response = $this->get(route('gantt.index'));

        $response->assertStatus(302); // Redirect to login
    }

    public function test_gantt_filters_are_passed_correctly(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('gantt.index', [
                'project_id' => $this->project->id,
                'status' => 'active',
            ]));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Gantt/Index')
            ->where('filters.project_id', (string) $this->project->id)
            ->where('filters.status', 'active')
        );
    }

    public function test_gantt_displays_all_projects_for_filter_dropdown(): void
    {
        Project::factory()->create(['name' => 'Alpha Project']);
        Project::factory()->create(['name' => 'Beta Project']);

        $response = $this->actingAs($this->user)
            ->get(route('gantt.index'));

        $response->assertOk();

        $projects = $response->viewData('page')['props']['projects'];

        // Should have at least the created projects
        $this->assertGreaterThanOrEqual(3, count($projects));

        // Projects should be ordered by name
        $projectNames = collect($projects)->pluck('name')->toArray();
        $sortedNames = collect($projects)->pluck('name')->sort()->values()->toArray();

        $this->assertEquals($sortedNames, $projectNames);
    }
}
