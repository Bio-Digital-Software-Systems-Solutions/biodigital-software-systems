<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('view programs');

        $this->project = Project::factory()->create();
    }

    public function test_can_view_kanban_board(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('kanban.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Kanban/Index')
            ->has('tasksByStatus')
            ->has('projects')
            ->has('users')
            ->has('sprints')
            ->has('filters')
        );
    }

    public function test_kanban_groups_tasks_by_status(): void
    {
        $todoTask = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'todo',
        ]);

        $inProgressTask = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'in_progress',
        ]);

        $doneTask = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'done',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index'));

        $response->assertOk();

        // Verify tasks are grouped correctly by status
        $tasksByStatus = $response->viewData('page')['props']['tasksByStatus'];

        $todoIds = collect($tasksByStatus['todo'])->pluck('id')->toArray();
        $inProgressIds = collect($tasksByStatus['in_progress'])->pluck('id')->toArray();
        $doneIds = collect($tasksByStatus['done'])->pluck('id')->toArray();

        $this->assertContains($todoTask->id, $todoIds);
        $this->assertContains($inProgressTask->id, $inProgressIds);
        $this->assertContains($doneTask->id, $doneIds);
    }

    public function test_can_update_task_status_using_uuid(): void
    {
        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'todo',
        ]);

        $this->assertNotNull($task->uuid);

        $response = $this->actingAs($this->user)
            ->patchJson(route('kanban.tasks.update-status', $task->uuid), [
                'status' => 'in_progress',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'task' => [
                'id',
                'uuid',
                'title',
                'status',
                'project',
                'assignee',
                'reporter',
            ],
        ]);

        $task->refresh();
        $this->assertEquals(TaskStatus::IN_PROGRESS, $task->status);
    }

    public function test_cannot_update_task_status_using_id(): void
    {
        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'todo',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson(route('kanban.tasks.update-status', $task->id), [
                'status' => 'in_progress',
            ]);

        $response->assertStatus(404);

        $task->refresh();
        $this->assertEquals(TaskStatus::TODO, $task->status);
    }

    public function test_can_update_task_to_all_valid_statuses(): void
    {
        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'todo',
        ]);

        $validStatuses = [
            'todo' => TaskStatus::TODO,
            'in_progress' => TaskStatus::IN_PROGRESS,
            'in_review' => TaskStatus::IN_REVIEW,
            'blocked' => TaskStatus::BLOCKED,
            'done' => TaskStatus::DONE,
        ];

        foreach ($validStatuses as $statusString => $statusEnum) {
            $response = $this->actingAs($this->user)
                ->patchJson(route('kanban.tasks.update-status', $task->uuid), [
                    'status' => $statusString,
                ]);

            $response->assertOk();
            $task->refresh();
            $this->assertEquals($statusEnum, $task->status);
        }
    }

    public function test_cannot_update_task_to_invalid_status(): void
    {
        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'todo',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson(route('kanban.tasks.update-status', $task->uuid), [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);

        $task->refresh();
        $this->assertEquals(TaskStatus::TODO, $task->status);
    }

    public function test_can_filter_kanban_by_project(): void
    {
        $project2 = Project::factory()->create();

        $task1 = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'todo',
        ]);

        $task2 = ProjectTask::factory()->create([
            'project_id' => $project2->id,
            'reporter_id' => $this->user->id,
            'status' => 'todo',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['project_id' => $this->project->id]));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Kanban/Index')
            ->where('filters.project_id', (string) $this->project->id)
            ->where('tasksByStatus.todo.0.id', $task1->id)
        );
    }

    public function test_can_filter_kanban_by_assignee(): void
    {
        $user2 = User::factory()->create();

        $task1 = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'assignee_id' => $this->user->id,
            'status' => 'todo',
        ]);

        $task2 = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'assignee_id' => $user2->id,
            'status' => 'todo',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['assignee_id' => $this->user->id]));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Kanban/Index')
            ->where('filters.assignee_id', (string) $this->user->id)
            ->where('tasksByStatus.todo.0.id', $task1->id)
        );
    }

    public function test_can_filter_kanban_by_priority(): void
    {
        $highTask = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'priority' => 'high',
            'status' => 'todo',
        ]);

        $lowTask = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'priority' => 'low',
            'status' => 'todo',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['priority' => 'high']));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Kanban/Index')
            ->where('filters.priority', 'high')
            ->has('tasksByStatus.todo')
        );

        // Verify the filter works by checking the high task is included and low task is excluded
        $todoTasks = $response->viewData('page')['props']['tasksByStatus']['todo'];
        $taskIds = collect($todoTasks)->pluck('id')->toArray();

        $this->assertContains($highTask->id, $taskIds, 'High priority task should be in results');
        $this->assertNotContains($lowTask->id, $taskIds, 'Low priority task should not be in results');
    }

    public function test_can_filter_kanban_by_type(): void
    {
        $bugTask = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'type' => 'bug',
            'status' => 'todo',
        ]);

        $featureTask = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'type' => 'feature',
            'status' => 'todo',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['type' => 'bug']));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Kanban/Index')
            ->where('filters.type', 'bug')
            ->has('tasksByStatus.todo')
        );

        // Verify the filter works by checking bug task is included and feature task is excluded
        $todoTasks = $response->viewData('page')['props']['tasksByStatus']['todo'];
        $taskIds = collect($todoTasks)->pluck('id')->toArray();

        $this->assertContains($bugTask->id, $taskIds, 'Bug task should be in results');
        $this->assertNotContains($featureTask->id, $taskIds, 'Feature task should not be in results');
    }

    public function test_can_filter_kanban_by_sprint(): void
    {
        $sprint = Sprint::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $taskInSprint = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'sprint_id' => $sprint->id,
            'status' => 'todo',
        ]);

        $taskWithoutSprint = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'sprint_id' => null,
            'status' => 'todo',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['sprint_id' => $sprint->id]));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Kanban/Index')
            ->where('filters.sprint_id', (string) $sprint->id)
            ->where('tasksByStatus.todo.0.id', $taskInSprint->id)
        );
    }

    public function test_can_search_tasks_in_kanban(): void
    {
        $matchingTask = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'title' => 'Fix login bug',
            'status' => 'todo',
        ]);

        $nonMatchingTask = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'title' => 'Add new feature',
            'status' => 'todo',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['search' => 'login']));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Kanban/Index')
            ->where('filters.search', 'login')
            ->where('tasksByStatus.todo.0.id', $matchingTask->id)
        );
    }

    public function test_unauthorized_user_cannot_access_kanban(): void
    {
        $unauthorizedUser = User::factory()->create();

        $response = $this->actingAs($unauthorizedUser)
            ->get(route('kanban.index'));

        $response->assertStatus(302); // Redirect due to middleware
    }

    public function test_task_status_update_requires_authentication(): void
    {
        $task = ProjectTask::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->user->id,
            'status' => 'todo',
        ]);

        $response = $this->patchJson(route('kanban.tasks.update-status', $task->uuid), [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(401); // Unauthorized
    }
}
