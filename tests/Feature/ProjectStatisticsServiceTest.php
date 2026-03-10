<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectStatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectStatisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectStatisticsService;
    }

    public function test_get_global_statistics_returns_expected_keys(): void
    {
        $result = $this->service->getGlobalStatistics();

        $this->assertArrayHasKey('projects_by_status', $result);
        $this->assertArrayHasKey('tasks_by_status', $result);
        $this->assertArrayHasKey('tasks_by_priority', $result);
        $this->assertArrayHasKey('sprints_by_status', $result);
        $this->assertArrayHasKey('epics_by_status', $result);
        $this->assertArrayHasKey('task_evolution', $result);
        $this->assertArrayHasKey('completion_by_project', $result);
        $this->assertArrayHasKey('projects_by_member', $result);
        $this->assertArrayHasKey('tasks_by_member', $result);
        $this->assertArrayHasKey('global_progress', $result);
        $this->assertArrayHasKey('velocity', $result);
    }

    public function test_get_project_statistics_returns_expected_keys(): void
    {
        $project = Project::factory()->create();

        $result = $this->service->getProjectStatistics($project);

        $this->assertArrayHasKey('tasks_by_status', $result);
        $this->assertArrayHasKey('tasks_by_priority', $result);
        $this->assertArrayHasKey('sprints_by_status', $result);
        $this->assertArrayHasKey('epics_by_status', $result);
        $this->assertArrayHasKey('task_evolution', $result);
        $this->assertArrayHasKey('completion_by_assignee', $result);
        $this->assertArrayHasKey('tasks_by_member', $result);
        $this->assertArrayHasKey('global_progress', $result);
        $this->assertArrayHasKey('velocity', $result);
    }

    public function test_get_task_statistics_returns_expected_keys(): void
    {
        $result = $this->service->getTaskStatistics();

        $this->assertArrayHasKey('tasks_by_status', $result);
        $this->assertArrayHasKey('tasks_by_priority', $result);
        $this->assertArrayHasKey('task_evolution', $result);
        $this->assertArrayHasKey('completion_by_assignee', $result);
        $this->assertArrayHasKey('tasks_by_member', $result);
        $this->assertArrayHasKey('global_progress', $result);
        $this->assertArrayHasKey('velocity', $result);
    }

    public function test_projects_by_status_counts_correctly(): void
    {
        Project::factory()->count(2)->create(['status' => 'active']);
        Project::factory()->count(3)->create(['status' => 'completed']);
        Project::factory()->create(['status' => 'on_hold']);

        $result = $this->service->getGlobalStatistics();
        $byStatus = collect($result['projects_by_status']);

        $this->assertEquals(2, $byStatus->firstWhere('label', 'Actif')['value']);
        $this->assertEquals(3, $byStatus->firstWhere('label', 'Terminé')['value']);
        $this->assertEquals(1, $byStatus->firstWhere('label', 'En pause')['value']);
    }

    public function test_tasks_by_status_counts_correctly(): void
    {
        $project = Project::factory()->create();
        $completedStatus = Status::factory()->completed()->create();
        $inProgressStatus = Status::factory()->inProgress()->create();

        Task::factory()->count(3)->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $project->id,
            'status_id' => $completedStatus->id,
        ]);
        Task::factory()->count(2)->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $project->id,
            'status_id' => $inProgressStatus->id,
        ]);

        $result = $this->service->getGlobalStatistics();
        $byStatus = collect($result['tasks_by_status']);

        $this->assertEquals(3, $byStatus->firstWhere('label', 'Terminé')['value']);
        $this->assertEquals(2, $byStatus->firstWhere('label', 'En cours')['value']);
    }

    public function test_tasks_by_priority_counts_correctly(): void
    {
        $project = Project::factory()->create();
        $status = Status::factory()->create();

        Task::factory()->count(4)->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $project->id,
            'status_id' => $status->id,
            'priority' => 'high',
        ]);
        Task::factory()->count(2)->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $project->id,
            'status_id' => $status->id,
            'priority' => 'low',
        ]);

        $result = $this->service->getGlobalStatistics();
        $byPriority = collect($result['tasks_by_priority']);

        $this->assertEquals(4, $byPriority->firstWhere('label', 'Haute')['value']);
        $this->assertEquals(2, $byPriority->firstWhere('label', 'Basse')['value']);
    }

    public function test_sprints_by_status_counts_correctly(): void
    {
        $project = Project::factory()->create();

        Sprint::factory()->count(2)->create(['project_id' => $project->id, 'status' => 'active']);
        Sprint::factory()->create(['project_id' => $project->id, 'status' => 'completed']);

        $result = $this->service->getGlobalStatistics();
        $byStatus = collect($result['sprints_by_status']);

        $this->assertEquals(2, $byStatus->firstWhere('label', 'Actif')['value']);
        $this->assertEquals(1, $byStatus->firstWhere('label', 'Terminé')['value']);
    }

    public function test_task_evolution_returns_multi_period_data(): void
    {
        $result = $this->service->getGlobalStatistics();

        // Check that task_evolution contains all period types
        $this->assertArrayHasKey('weekly', $result['task_evolution']);
        $this->assertArrayHasKey('monthly', $result['task_evolution']);
        $this->assertArrayHasKey('quarterly', $result['task_evolution']);
        $this->assertArrayHasKey('semester', $result['task_evolution']);
        $this->assertArrayHasKey('yearly', $result['task_evolution']);

        // Check weekly data has 8 weeks
        $this->assertCount(8, $result['task_evolution']['weekly']);
        $this->assertArrayHasKey('label', $result['task_evolution']['weekly'][0]);
        $this->assertArrayHasKey('created', $result['task_evolution']['weekly'][0]);
        $this->assertArrayHasKey('completed', $result['task_evolution']['weekly'][0]);

        // Check monthly data has 12 months
        $this->assertCount(12, $result['task_evolution']['monthly']);

        // Check quarterly data has 4 quarters
        $this->assertCount(4, $result['task_evolution']['quarterly']);

        // Check semester data has 4 semesters
        $this->assertCount(4, $result['task_evolution']['semester']);

        // Check yearly data has 3 years
        $this->assertCount(3, $result['task_evolution']['yearly']);
    }

    public function test_completion_by_project_returns_correct_rate(): void
    {
        $project = Project::factory()->create();
        $completedStatus = Status::factory()->completed()->create();
        $pendingStatus = Status::factory()->pending()->create();

        Task::factory()->count(3)->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $project->id,
            'status_id' => $completedStatus->id,
        ]);
        Task::factory()->count(7)->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $project->id,
            'status_id' => $pendingStatus->id,
        ]);

        $result = $this->service->getGlobalStatistics();
        $completion = collect($result['completion_by_project']);

        $projectCompletion = $completion->firstWhere('name', $project->name);
        $this->assertNotNull($projectCompletion);
        $this->assertEquals(30.0, $projectCompletion['value']);
        $this->assertEquals(3, $projectCompletion['completed']);
        $this->assertEquals(10, $projectCompletion['total']);
    }

    public function test_completion_by_assignee_returns_correct_data(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $completedStatus = Status::factory()->completed()->create();
        $pendingStatus = Status::factory()->pending()->create();

        Task::factory()->count(2)->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $project->id,
            'status_id' => $completedStatus->id,
            'assigned_to' => $user->id,
        ]);
        Task::factory()->count(3)->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $project->id,
            'status_id' => $pendingStatus->id,
            'assigned_to' => $user->id,
        ]);

        $result = $this->service->getProjectStatistics($project);
        $byAssignee = collect($result['completion_by_assignee']);

        $userName = trim($user->first_name.' '.$user->last_name);
        $userCompletion = $byAssignee->firstWhere('name', $userName);
        $this->assertNotNull($userCompletion);
        $this->assertEquals(40.0, $userCompletion['value']);
        $this->assertEquals(2, $userCompletion['completed']);
        $this->assertEquals(5, $userCompletion['total']);
    }

    public function test_dashboard_passes_analytics_stats(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('view projects');

        $response = $this->actingAs($user)->get('/projects');

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Projects/Dashboard')
            ->has('analyticsStats')
        );
    }

    public function test_project_show_passes_statistics(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('view projects');
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.show', $project->uuid));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Projects/Show')
            ->has('projectStatistics')
        );
    }

    public function test_tasks_index_passes_statistics(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('view tasks');

        $response = $this->actingAs($user)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert
            ->component('Tasks/Index')
            ->has('taskStatistics')
        );
    }

    public function test_velocity_returns_correct_structure(): void
    {
        $result = $this->service->getGlobalStatistics();

        $this->assertArrayHasKey('velocity', $result);
        $velocity = $result['velocity'];

        // Check all period types exist
        $this->assertArrayHasKey('daily', $velocity);
        $this->assertArrayHasKey('weekly', $velocity);
        $this->assertArrayHasKey('monthly', $velocity);

        // Check structure of each period
        foreach (['daily', 'weekly', 'monthly'] as $period) {
            $this->assertArrayHasKey('value', $velocity[$period]);
            $this->assertArrayHasKey('total', $velocity[$period]);
            $this->assertArrayHasKey('period_count', $velocity[$period]);
            $this->assertArrayHasKey('max', $velocity[$period]);
            $this->assertArrayHasKey('label', $velocity[$period]);
        }

        // Check labels
        $this->assertEquals('jour', $velocity['daily']['label']);
        $this->assertEquals('semaine', $velocity['weekly']['label']);
        $this->assertEquals('mois', $velocity['monthly']['label']);

        // Check period counts
        $this->assertEquals(30, $velocity['daily']['period_count']);
        $this->assertEquals(8, $velocity['weekly']['period_count']);
        $this->assertEquals(12, $velocity['monthly']['period_count']);
    }

    public function test_velocity_calculates_correctly_with_completed_tasks(): void
    {
        $project = Project::factory()->create();
        $completedStatus = Status::factory()->completed()->create();

        // Create 30 completed tasks updated in the last 30 days
        for ($i = 0; $i < 30; $i++) {
            Task::factory()->create([
                'taskable_type' => \App\Models\Project::class,
                'taskable_id' => $project->id,
                'status_id' => $completedStatus->id,
                'updated_at' => now()->subDays($i),
            ]);
        }

        $result = $this->service->getGlobalStatistics();
        $velocity = $result['velocity'];

        // Daily: 30 tasks over 30 days = 1 task/day
        $this->assertEquals(1.0, $velocity['daily']['value']);
        $this->assertEquals(30, $velocity['daily']['total']);

        // Weekly: 30 tasks over 8 weeks ≈ 3.75 -> 3.8 tasks/week
        $this->assertGreaterThan(0, $velocity['weekly']['value']);
        $this->assertGreaterThan(0, $velocity['weekly']['total']);

        // Monthly: 30 tasks over 12 months ≈ 2.5 tasks/month
        $this->assertGreaterThan(0, $velocity['monthly']['value']);
        $this->assertGreaterThan(0, $velocity['monthly']['total']);
    }

    public function test_velocity_returns_zero_with_no_completed_tasks(): void
    {
        $project = Project::factory()->create();
        $pendingStatus = Status::factory()->pending()->create();

        // Create tasks that are not completed
        Task::factory()->count(10)->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $project->id,
            'status_id' => $pendingStatus->id,
        ]);

        $result = $this->service->getGlobalStatistics();
        $velocity = $result['velocity'];

        $this->assertEquals(0, $velocity['daily']['value']);
        $this->assertEquals(0, $velocity['daily']['total']);
        $this->assertEquals(0, $velocity['weekly']['value']);
        $this->assertEquals(0, $velocity['weekly']['total']);
        $this->assertEquals(0, $velocity['monthly']['value']);
        $this->assertEquals(0, $velocity['monthly']['total']);
    }

    public function test_velocity_excludes_old_tasks(): void
    {
        $project = Project::factory()->create();
        $completedStatus = Status::factory()->completed()->create();

        // Create tasks completed more than 30 days ago (outside daily period)
        Task::factory()->count(5)->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $project->id,
            'status_id' => $completedStatus->id,
            'updated_at' => now()->subDays(45),
        ]);

        $result = $this->service->getGlobalStatistics();
        $velocity = $result['velocity'];

        // Daily should be 0 (tasks are older than 30 days)
        $this->assertEquals(0, $velocity['daily']['total']);

        // Weekly should still have these tasks (within 8 weeks)
        $this->assertEquals(5, $velocity['weekly']['total']);
    }

    public function test_velocity_max_is_reasonable(): void
    {
        $project = Project::factory()->create();
        $completedStatus = Status::factory()->completed()->create();

        // Create 60 tasks (2 per day average)
        for ($i = 0; $i < 60; $i++) {
            Task::factory()->create([
                'taskable_type' => \App\Models\Project::class,
                'taskable_id' => $project->id,
                'status_id' => $completedStatus->id,
                'updated_at' => now()->subDays($i % 30),
            ]);
        }

        $result = $this->service->getGlobalStatistics();
        $velocity = $result['velocity'];

        // Max should be at least double the value, rounded to nearest 10
        $this->assertGreaterThanOrEqual($velocity['daily']['value'] * 2, $velocity['daily']['max']);
        $this->assertGreaterThanOrEqual(10, $velocity['daily']['max']);
        $this->assertGreaterThanOrEqual(50, $velocity['weekly']['max']);
        $this->assertGreaterThanOrEqual(200, $velocity['monthly']['max']);
    }

    public function test_velocity_works_for_project_statistics(): void
    {
        $project = Project::factory()->create();
        $completedStatus = Status::factory()->completed()->create();

        Task::factory()->count(15)->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $project->id,
            'status_id' => $completedStatus->id,
            'updated_at' => now()->subDays(5),
        ]);

        $result = $this->service->getProjectStatistics($project);

        $this->assertArrayHasKey('velocity', $result);
        $this->assertArrayHasKey('daily', $result['velocity']);
        $this->assertArrayHasKey('weekly', $result['velocity']);
        $this->assertArrayHasKey('monthly', $result['velocity']);

        $this->assertEquals(15, $result['velocity']['daily']['total']);
    }

    public function test_velocity_works_for_task_statistics(): void
    {
        $project = Project::factory()->create();
        $completedStatus = Status::factory()->completed()->create();

        Task::factory()->count(20)->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $project->id,
            'status_id' => $completedStatus->id,
            'updated_at' => now()->subDays(10),
        ]);

        $result = $this->service->getTaskStatistics();

        $this->assertArrayHasKey('velocity', $result);
        $this->assertEquals(20, $result['velocity']['daily']['total']);
    }
}
