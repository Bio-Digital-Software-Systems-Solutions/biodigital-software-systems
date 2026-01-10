<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SprintBurndownTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Sprint $sprint;

    private Project $project;

    private Status $pendingStatus;

    private Status $inProgressStatus;

    private Status $completedStatus;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::firstOrCreate(['name' => 'view programs']);

        // Create user with permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('view programs');

        // Create project and sprint
        $this->project = Project::factory()->create();

        // Sprint runs for 2 weeks, starting from 1 week ago
        $this->sprint = Sprint::factory()->create([
            'project_id' => $this->project->id,
            'start_date' => Carbon::now()->subDays(7)->startOfDay(),
            'end_date' => Carbon::now()->addDays(7)->endOfDay(),
            'name' => 'Test Sprint',
        ]);

        // Create statuses
        $this->pendingStatus = Status::factory()->pending()->create();
        $this->inProgressStatus = Status::factory()->inProgress()->create();
        $this->completedStatus = Status::factory()->completed()->create();
    }

    /** @test */
    public function it_requires_authentication_to_access_burndown_chart(): void
    {
        $response = $this->getJson("/api/sprints/{$this->sprint->uuid}/burndown");

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_returns_burndown_data_for_sprint(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/sprints/{$this->sprint->uuid}/burndown");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'chartData' => [
                        '*' => [
                            'date',
                            'dayNumber',
                            'formattedDate',
                            'ideal',
                            'actual',
                            'completed',
                            'totalScope',
                        ],
                    ],
                    'summary' => [
                        'totalStoryPoints',
                        'completedPoints',
                        'remainingPoints',
                        'progressPercentage',
                        'velocity',
                        'daysElapsed',
                        'totalDays',
                        'estimatedCompletionDate',
                        'isOnTrack',
                    ],
                    'sprint' => [
                        'id',
                        'uuid',
                        'name',
                        'startDate',
                        'endDate',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_calculates_correct_story_points(): void
    {
        Sanctum::actingAs($this->user);

        // Tasks must be created before or at sprint start date to be counted
        $taskCreatedAt = Carbon::now()->subDays(8); // Before sprint start

        // Create tasks with story points
        Task::factory()->create([
            'sprint_id' => $this->sprint->id,
            'project_id' => $this->project->id,
            'status_id' => $this->pendingStatus->id,
            'story_points' => 5,
            'created_at' => $taskCreatedAt,
        ]);

        Task::factory()->create([
            'sprint_id' => $this->sprint->id,
            'project_id' => $this->project->id,
            'status_id' => $this->inProgressStatus->id,
            'story_points' => 3,
            'created_at' => $taskCreatedAt,
        ]);

        Task::factory()->create([
            'sprint_id' => $this->sprint->id,
            'project_id' => $this->project->id,
            'status_id' => $this->completedStatus->id,
            'story_points' => 8,
            'created_at' => $taskCreatedAt,
        ]);

        $response = $this->getJson("/api/sprints/{$this->sprint->uuid}/burndown");

        $response->assertOk();

        $data = $response->json('data');

        // Total should be 5 + 3 + 8 = 16
        $this->assertEquals(16, $data['summary']['totalStoryPoints']);
        // Completed should be 8
        $this->assertEquals(8, $data['summary']['completedPoints']);
        // Remaining should be 16 - 8 = 8
        $this->assertEquals(8, $data['summary']['remainingPoints']);
    }

    /** @test */
    public function it_calculates_progress_percentage_correctly(): void
    {
        Sanctum::actingAs($this->user);

        // Create 4 tasks, 1 completed (25% progress)
        Task::factory()->count(3)->create([
            'sprint_id' => $this->sprint->id,
            'project_id' => $this->project->id,
            'status_id' => $this->pendingStatus->id,
            'story_points' => 1,
        ]);

        Task::factory()->create([
            'sprint_id' => $this->sprint->id,
            'project_id' => $this->project->id,
            'status_id' => $this->completedStatus->id,
            'story_points' => 1,
        ]);

        $response = $this->getJson("/api/sprints/{$this->sprint->uuid}/burndown");

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals(25.0, $data['summary']['progressPercentage']);
    }

    /** @test */
    public function it_returns_correct_chart_data_length(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/sprints/{$this->sprint->uuid}/burndown");

        $response->assertOk();

        $chartData = $response->json('data.chartData');

        // Sprint is 15 days (7 days ago to 7 days from now + today = 15 days)
        $startDate = Carbon::parse($this->sprint->start_date);
        $endDate = Carbon::parse($this->sprint->end_date);
        $expectedDays = $startDate->diffInDays($endDate) + 1;

        $this->assertCount($expectedDays, $chartData);
    }

    /** @test */
    public function it_shows_ideal_burndown_line(): void
    {
        Sanctum::actingAs($this->user);

        // Task must be created before or at sprint start date to be counted
        $taskCreatedAt = Carbon::now()->subDays(8); // Before sprint start

        Task::factory()->create([
            'sprint_id' => $this->sprint->id,
            'project_id' => $this->project->id,
            'status_id' => $this->pendingStatus->id,
            'story_points' => 10,
            'created_at' => $taskCreatedAt,
        ]);

        $response = $this->getJson("/api/sprints/{$this->sprint->uuid}/burndown");

        $response->assertOk();

        $chartData = $response->json('data.chartData');

        // First day should have ideal = total story points
        $this->assertEquals(10, $chartData[0]['ideal']);

        // Last day should have ideal = 0
        $lastDay = end($chartData);
        $this->assertEquals(0, $lastDay['ideal']);
    }

    /** @test */
    public function it_handles_sprint_with_no_tasks(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/sprints/{$this->sprint->uuid}/burndown");

        $response->assertOk();

        $data = $response->json('data');

        $this->assertEquals(0, $data['summary']['totalStoryPoints']);
        $this->assertEquals(0, $data['summary']['completedPoints']);
        $this->assertEquals(0, $data['summary']['remainingPoints']);
        $this->assertEquals(0, $data['summary']['progressPercentage']);
    }

    /** @test */
    public function it_returns_sprint_info_in_response(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/sprints/{$this->sprint->uuid}/burndown");

        $response->assertOk()
            ->assertJsonPath('data.sprint.id', $this->sprint->id)
            ->assertJsonPath('data.sprint.name', 'Test Sprint');
    }

    /** @test */
    public function it_calculates_velocity_correctly(): void
    {
        Sanctum::actingAs($this->user);

        // Create completed tasks with 8 story points total
        Task::factory()->create([
            'sprint_id' => $this->sprint->id,
            'project_id' => $this->project->id,
            'status_id' => $this->completedStatus->id,
            'story_points' => 8,
        ]);

        $response = $this->getJson("/api/sprints/{$this->sprint->uuid}/burndown");

        $response->assertOk();

        $data = $response->json('data');

        // Velocity = completed points / days elapsed
        // Days elapsed is 8 (from 7 days ago to today = 8 days including today)
        $daysElapsed = $data['summary']['daysElapsed'];
        $expectedVelocity = round(8 / $daysElapsed, 2);

        $this->assertEquals($expectedVelocity, $data['summary']['velocity']);
    }

    /** @test */
    public function it_determines_if_sprint_is_on_track(): void
    {
        Sanctum::actingAs($this->user);

        // Create a sprint that just started today
        $newSprint = Sprint::factory()->create([
            'project_id' => $this->project->id,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(14),
        ]);

        // Add task and complete it immediately - should be on track
        Task::factory()->create([
            'sprint_id' => $newSprint->id,
            'project_id' => $this->project->id,
            'status_id' => $this->completedStatus->id,
            'story_points' => 10,
        ]);

        $response = $this->getJson("/api/sprints/{$newSprint->uuid}/burndown");

        $response->assertOk();

        // Should be on track since all points are completed
        $this->assertTrue($response->json('data.summary.isOnTrack'));
    }

    /** @test */
    public function it_handles_tasks_without_story_points(): void
    {
        Sanctum::actingAs($this->user);

        // Create tasks without story points
        Task::factory()->count(3)->create([
            'sprint_id' => $this->sprint->id,
            'project_id' => $this->project->id,
            'status_id' => $this->pendingStatus->id,
            'story_points' => null,
        ]);

        Task::factory()->create([
            'sprint_id' => $this->sprint->id,
            'project_id' => $this->project->id,
            'status_id' => $this->completedStatus->id,
            'story_points' => null,
        ]);

        $response = $this->getJson("/api/sprints/{$this->sprint->uuid}/burndown");

        $response->assertOk();

        $data = $response->json('data');

        // Should fallback to counting tasks (4 total, 1 completed = 1 point)
        $this->assertEquals(4, $data['summary']['totalStoryPoints']);
        $this->assertEquals(1, $data['summary']['completedPoints']);
    }

    /** @test */
    public function it_returns_null_for_future_dates_actual_values(): void
    {
        Sanctum::actingAs($this->user);

        Task::factory()->create([
            'sprint_id' => $this->sprint->id,
            'project_id' => $this->project->id,
            'status_id' => $this->pendingStatus->id,
            'story_points' => 10,
        ]);

        $response = $this->getJson("/api/sprints/{$this->sprint->uuid}/burndown");

        $response->assertOk();

        $chartData = $response->json('data.chartData');

        // Find a future date data point
        $futureDates = array_filter($chartData, function ($point) {
            return Carbon::parse($point['date'])->isAfter(Carbon::today());
        });

        foreach ($futureDates as $futurePoint) {
            $this->assertNull($futurePoint['actual']);
            $this->assertNull($futurePoint['completed']);
        }
    }

    /** @test */
    public function it_returns_404_for_non_existent_sprint(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/sprints/99999/burndown');

        $response->assertNotFound();
    }
}
