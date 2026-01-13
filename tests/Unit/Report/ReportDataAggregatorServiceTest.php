<?php

namespace Tests\Unit\Report;

use App\Enums\Report\ActivityCategory;
use App\Enums\Report\ObjectiveStatus;
use App\Models\Department;
use App\Models\DepartmentActivity;
use App\Models\DepartmentKpi;
use App\Models\DepartmentKpiValue;
use App\Models\DepartmentObjective;
use App\Models\DepartmentReport;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Report\ReportDataAggregatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportDataAggregatorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReportDataAggregatorService $service;
    protected Department $department;
    protected User $user;
    protected Carbon $periodStart;
    protected Carbon $periodEnd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReportDataAggregatorService();
        $this->user = User::factory()->create();
        $this->department = Department::factory()->active()->create();
        $this->periodStart = Carbon::create(2024, 1, 1);
        $this->periodEnd = Carbon::create(2024, 1, 31);
    }

    // ========================================
    // SUMMARY TESTS
    // ========================================

    public function test_get_summary_returns_correct_structure(): void
    {
        $summary = $this->service->getSummary($this->department, $this->periodStart, $this->periodEnd);

        $this->assertArrayHasKey('total_activities', $summary);
        $this->assertArrayHasKey('total_hours', $summary);
        $this->assertArrayHasKey('objectives_completed', $summary);
        $this->assertArrayHasKey('objectives_total', $summary);
        $this->assertArrayHasKey('completion_rate', $summary);
        $this->assertArrayHasKey('unique_participants', $summary);
        $this->assertArrayHasKey('projects_active', $summary);
    }

    public function test_get_summary_counts_activities_correctly(): void
    {
        // Create activities for this period
        DepartmentActivity::factory()
            ->forDepartment($this->department)
            ->count(5)
            ->create([
                'date' => $this->periodStart->copy()->addDays(5),
                'duration_hours' => 2.0,
            ]);

        // Create activities outside the period (should not be counted)
        DepartmentActivity::factory()
            ->forDepartment($this->department)
            ->count(3)
            ->create([
                'date' => $this->periodStart->copy()->subMonth(),
            ]);

        $summary = $this->service->getSummary($this->department, $this->periodStart, $this->periodEnd);

        $this->assertEquals(5, $summary['total_activities']);
        $this->assertEquals(10.0, $summary['total_hours']); // 5 activities * 2 hours
    }

    public function test_get_summary_calculates_completion_rate(): void
    {
        // Create 10 objectives, 7 completed
        DepartmentObjective::factory()
            ->forDepartment($this->department)
            ->count(7)
            ->create([
                'status' => ObjectiveStatus::COMPLETED->value,
                'period_start' => $this->periodStart,
                'period_end' => $this->periodEnd,
            ]);

        DepartmentObjective::factory()
            ->forDepartment($this->department)
            ->count(3)
            ->create([
                'status' => ObjectiveStatus::IN_PROGRESS->value,
                'period_start' => $this->periodStart,
                'period_end' => $this->periodEnd,
            ]);

        $summary = $this->service->getSummary($this->department, $this->periodStart, $this->periodEnd);

        $this->assertEquals(7, $summary['objectives_completed']);
        $this->assertEquals(10, $summary['objectives_total']);
        $this->assertEquals(70.0, $summary['completion_rate']);
    }

    public function test_get_summary_handles_zero_objectives(): void
    {
        $summary = $this->service->getSummary($this->department, $this->periodStart, $this->periodEnd);

        $this->assertEquals(0, $summary['objectives_completed']);
        $this->assertEquals(0, $summary['objectives_total']);
        $this->assertEquals(0, $summary['completion_rate']);
    }

    // ========================================
    // ACTIVITIES DATA TESTS
    // ========================================

    public function test_get_activities_data_returns_correct_structure(): void
    {
        $data = $this->service->getActivitiesData($this->department, $this->periodStart, $this->periodEnd);

        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('total_hours', $data);
        $this->assertArrayHasKey('by_category', $data);
        $this->assertArrayHasKey('recent', $data);
        $this->assertArrayHasKey('timeline', $data);
    }

    public function test_get_activities_data_groups_by_category(): void
    {
        // Create activities with different categories
        foreach (ActivityCategory::cases() as $category) {
            DepartmentActivity::factory()
                ->forDepartment($this->department)
                ->create([
                    'category' => $category->value,
                    'date' => $this->periodStart->copy()->addDays(5),
                    'duration_hours' => 1.5,
                ]);
        }

        $data = $this->service->getActivitiesData($this->department, $this->periodStart, $this->periodEnd);

        foreach (ActivityCategory::cases() as $category) {
            $this->assertArrayHasKey($category->value, $data['by_category']);
            $this->assertEquals(1, $data['by_category'][$category->value]['count']);
            $this->assertEquals(1.5, $data['by_category'][$category->value]['hours']);
        }
    }

    public function test_get_activities_data_returns_recent_activities(): void
    {
        DepartmentActivity::factory()
            ->forDepartment($this->department)
            ->count(15)
            ->create([
                'date' => $this->periodStart->copy()->addDays(5),
            ]);

        $data = $this->service->getActivitiesData($this->department, $this->periodStart, $this->periodEnd);

        // Should return only 10 recent activities
        $this->assertCount(10, $data['recent']);
    }

    // ========================================
    // OBJECTIVES DATA TESTS
    // ========================================

    public function test_get_objectives_data_returns_correct_structure(): void
    {
        $data = $this->service->getObjectivesData($this->department, $this->periodStart, $this->periodEnd);

        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('average_progress', $data);
        $this->assertArrayHasKey('by_status', $data);
        $this->assertArrayHasKey('overdue_count', $data);
        $this->assertArrayHasKey('delayed_count', $data);
        $this->assertArrayHasKey('list', $data);
    }

    public function test_get_objectives_data_counts_by_status(): void
    {
        DepartmentObjective::factory()
            ->forDepartment($this->department)
            ->count(3)
            ->create([
                'status' => ObjectiveStatus::COMPLETED->value,
                'period_start' => $this->periodStart,
                'period_end' => $this->periodEnd,
            ]);

        DepartmentObjective::factory()
            ->forDepartment($this->department)
            ->count(2)
            ->create([
                'status' => ObjectiveStatus::IN_PROGRESS->value,
                'period_start' => $this->periodStart,
                'period_end' => $this->periodEnd,
            ]);

        $data = $this->service->getObjectivesData($this->department, $this->periodStart, $this->periodEnd);

        $this->assertEquals(3, $data['by_status'][ObjectiveStatus::COMPLETED->value]['count']);
        $this->assertEquals(2, $data['by_status'][ObjectiveStatus::IN_PROGRESS->value]['count']);
    }

    public function test_get_objectives_data_calculates_average_progress(): void
    {
        DepartmentObjective::factory()
            ->forDepartment($this->department)
            ->create([
                'progress_percentage' => 100,
                'period_start' => $this->periodStart,
                'period_end' => $this->periodEnd,
            ]);

        DepartmentObjective::factory()
            ->forDepartment($this->department)
            ->create([
                'progress_percentage' => 50,
                'period_start' => $this->periodStart,
                'period_end' => $this->periodEnd,
            ]);

        $data = $this->service->getObjectivesData($this->department, $this->periodStart, $this->periodEnd);

        $this->assertEquals(75.0, $data['average_progress']);
    }

    // ========================================
    // KPIs DATA TESTS
    // ========================================

    public function test_get_kpis_data_returns_correct_structure(): void
    {
        $kpi = DepartmentKpi::factory()->forDepartment($this->department)->create();

        DepartmentKpiValue::factory()->create([
            'kpi_id' => $kpi->id,
            'value' => 100,
            'recorded_at' => $this->periodStart->copy()->addDays(15),
        ]);

        $data = $this->service->getKpisData($this->department, $this->periodStart, $this->periodEnd);

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayHasKey('current', $data[0]);
        $this->assertArrayHasKey('target', $data[0]);
        $this->assertArrayHasKey('trend', $data[0]);
        $this->assertArrayHasKey('values', $data[0]);
    }

    // ========================================
    // PROJECTS DATA TESTS
    // ========================================

    public function test_get_projects_data_returns_correct_structure(): void
    {
        $data = $this->service->getProjectsData($this->department, $this->periodStart, $this->periodEnd);

        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('by_status', $data);
        $this->assertArrayHasKey('list', $data);
    }

    public function test_get_projects_data_returns_empty_when_no_department_link(): void
    {
        // Projects are not linked to departments in this schema
        // This test verifies that the service handles this gracefully
        $data = $this->service->getProjectsData($this->department, $this->periodStart, $this->periodEnd);

        $this->assertEquals(0, $data['total']);
        $this->assertEmpty($data['by_status']);
        $this->assertEmpty($data['list']);
    }

    // ========================================
    // TASKS DATA TESTS
    // ========================================

    public function test_get_tasks_data_returns_correct_structure(): void
    {
        $data = $this->service->getTasksData($this->department, $this->periodStart, $this->periodEnd);

        $this->assertArrayHasKey('created', $data);
        $this->assertArrayHasKey('completed', $data);
        $this->assertArrayHasKey('by_status', $data);
        $this->assertArrayHasKey('by_priority', $data);
        $this->assertArrayHasKey('completion_rate', $data);
    }

    public function test_get_tasks_data_returns_empty_when_no_department_link(): void
    {
        // Tasks are linked through projects which are not linked to departments
        // This test verifies that the service handles this gracefully
        $data = $this->service->getTasksData($this->department, $this->periodStart, $this->periodEnd);

        $this->assertEquals(0, $data['created']);
        $this->assertEquals(0, $data['completed']);
        $this->assertEmpty($data['by_status']);
        $this->assertEmpty($data['by_priority']);
        $this->assertEquals(0, $data['completion_rate']);
    }

    // ========================================
    // MEMBERS DATA TESTS
    // ========================================

    public function test_get_members_data_returns_correct_structure(): void
    {
        $data = $this->service->getMembersData($this->department, $this->periodStart, $this->periodEnd);

        $this->assertArrayHasKey('total_members', $data);
        $this->assertArrayHasKey('list', $data);
        $this->assertArrayHasKey('top_contributors', $data);
    }

    public function test_get_members_data_groups_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        DepartmentActivity::factory()
            ->forDepartment($this->department)
            ->count(5)
            ->create([
                'user_id' => $user1->id,
                'date' => $this->periodStart->copy()->addDays(5),
                'duration_hours' => 2.0,
            ]);

        DepartmentActivity::factory()
            ->forDepartment($this->department)
            ->count(3)
            ->create([
                'user_id' => $user2->id,
                'date' => $this->periodStart->copy()->addDays(5),
                'duration_hours' => 3.0,
            ]);

        $data = $this->service->getMembersData($this->department, $this->periodStart, $this->periodEnd);

        $this->assertEquals(2, $data['total_members']);
    }

    // ========================================
    // TRENDS DATA TESTS
    // ========================================

    public function test_get_trends_data_returns_correct_structure(): void
    {
        $data = $this->service->getTrendsData($this->department, $this->periodStart, $this->periodEnd);

        $this->assertArrayHasKey('period_type', $data);
        $this->assertArrayHasKey('previous_period', $data);
        $this->assertArrayHasKey('activities', $data);
        $this->assertArrayHasKey('objectives_completed', $data);
        $this->assertArrayHasKey('hours', $data);
    }

    public function test_get_trends_data_compares_with_previous_period(): void
    {
        // Current period activities
        DepartmentActivity::factory()
            ->forDepartment($this->department)
            ->count(10)
            ->create([
                'date' => $this->periodStart->copy()->addDays(5),
            ]);

        // Previous period activities
        DepartmentActivity::factory()
            ->forDepartment($this->department)
            ->count(5)
            ->create([
                'date' => $this->periodStart->copy()->subMonth()->addDays(5),
            ]);

        $data = $this->service->getTrendsData($this->department, $this->periodStart, $this->periodEnd);

        $this->assertEquals(10, $data['activities']['current']);
        $this->assertEquals(5, $data['activities']['previous']);
        $this->assertEquals(5, $data['activities']['change']);
        $this->assertEquals('up', $data['activities']['direction']);
    }

    public function test_get_trends_data_handles_percentage_calculation(): void
    {
        // Current period: 15 activities
        DepartmentActivity::factory()
            ->forDepartment($this->department)
            ->count(15)
            ->create([
                'date' => $this->periodStart->copy()->addDays(5),
            ]);

        // Previous period: 10 activities
        DepartmentActivity::factory()
            ->forDepartment($this->department)
            ->count(10)
            ->create([
                'date' => $this->periodStart->copy()->subMonth()->addDays(5),
            ]);

        $data = $this->service->getTrendsData($this->department, $this->periodStart, $this->periodEnd);

        // 50% increase (5/10 * 100)
        $this->assertEquals(50.0, $data['activities']['percentage']);
    }

    // ========================================
    // AGGREGATE FOR REPORT TESTS
    // ========================================

    public function test_aggregate_for_report_returns_all_data(): void
    {
        $report = DepartmentReport::factory()
            ->forDepartment($this->department)
            ->byAuthor($this->user)
            ->monthly($this->periodStart)
            ->create();

        $data = $this->service->aggregateForReport($report);

        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('activities', $data);
        $this->assertArrayHasKey('objectives', $data);
        $this->assertArrayHasKey('kpis', $data);
        $this->assertArrayHasKey('projects', $data);
        $this->assertArrayHasKey('tasks', $data);
        $this->assertArrayHasKey('members', $data);
        $this->assertArrayHasKey('trends', $data);
    }
}
