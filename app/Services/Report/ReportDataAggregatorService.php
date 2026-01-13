<?php

namespace App\Services\Report;

use App\Enums\Report\ActivityCategory;
use App\Enums\Report\ObjectiveStatus;
use App\Enums\Report\ReportPeriodType;
use App\Models\Department;
use App\Models\DepartmentActivity;
use App\Models\DepartmentKpi;
use App\Models\DepartmentObjective;
use App\Models\DepartmentReport;
use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportDataAggregatorService
{
    /**
     * Aggregate all data for a department report.
     */
    public function aggregateForReport(DepartmentReport $report): array
    {
        $department = $report->department;
        $periodStart = $report->period_start;
        $periodEnd = $report->period_end;

        return [
            'summary' => $this->getSummary($department, $periodStart, $periodEnd),
            'activities' => $this->getActivitiesData($department, $periodStart, $periodEnd),
            'objectives' => $this->getObjectivesData($department, $periodStart, $periodEnd),
            'kpis' => $this->getKpisData($department, $periodStart, $periodEnd),
            'projects' => $this->getProjectsData($department, $periodStart, $periodEnd),
            'tasks' => $this->getTasksData($department, $periodStart, $periodEnd),
            'members' => $this->getMembersData($department, $periodStart, $periodEnd),
            'trends' => $this->getTrendsData($department, $periodStart, $periodEnd),
        ];
    }

    /**
     * Get summary statistics.
     */
    public function getSummary(Department $department, Carbon $periodStart, Carbon $periodEnd): array
    {
        $activities = DepartmentActivity::forDepartment($department->id)
            ->forPeriod($periodStart, $periodEnd);

        $objectives = DepartmentObjective::forDepartment($department->id)
            ->forPeriod($periodStart, $periodEnd);

        $totalHours = $activities->clone()->sum('duration_hours');
        $totalActivities = $activities->clone()->count();

        $objectivesCompleted = $objectives->clone()
            ->where('status', ObjectiveStatus::COMPLETED->value)
            ->count();
        $objectivesTotal = $objectives->clone()->count();

        $completionRate = $objectivesTotal > 0
            ? round(($objectivesCompleted / $objectivesTotal) * 100, 1)
            : 0;

        return [
            'total_activities' => $totalActivities,
            'total_hours' => round($totalHours, 1),
            'objectives_completed' => $objectivesCompleted,
            'objectives_total' => $objectivesTotal,
            'completion_rate' => $completionRate,
            'unique_participants' => $this->getUniqueParticipantsCount($department, $periodStart, $periodEnd),
            'projects_active' => $this->getActiveProjectsCount($department),
        ];
    }

    /**
     * Get activities data grouped by category.
     */
    public function getActivitiesData(Department $department, Carbon $periodStart, Carbon $periodEnd): array
    {
        $activities = DepartmentActivity::forDepartment($department->id)
            ->forPeriod($periodStart, $periodEnd)
            ->with(['user', 'relatedProject'])
            ->orderBy('date', 'desc')
            ->get();

        $byCategory = $activities->groupBy(fn($a) => $a->category->value);
        $categoryStats = [];

        foreach (ActivityCategory::cases() as $category) {
            $categoryActivities = $byCategory->get($category->value, collect());
            $categoryStats[$category->value] = [
                'label' => $category->label(),
                'icon' => $category->icon(),
                'color' => $category->color(),
                'count' => $categoryActivities->count(),
                'hours' => round($categoryActivities->sum('duration_hours'), 1),
                'participants' => $categoryActivities->pluck('participants')->flatten()->unique()->count(),
            ];
        }

        return [
            'total' => $activities->count(),
            'total_hours' => round($activities->sum('duration_hours'), 1),
            'by_category' => $categoryStats,
            'recent' => $activities->take(10)->map(fn($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'category' => $a->category->value,
                'category_label' => $a->category_label,
                'date' => $a->date->format('Y-m-d'),
                'duration' => $a->duration_hours,
                'user' => $a->user?->full_name,
            ])->toArray(),
            'timeline' => $this->getActivitiesTimeline($activities),
        ];
    }

    /**
     * Get objectives data with progress tracking.
     */
    public function getObjectivesData(Department $department, Carbon $periodStart, Carbon $periodEnd): array
    {
        $objectives = DepartmentObjective::forDepartment($department->id)
            ->forPeriod($periodStart, $periodEnd)
            ->with(['assignee', 'children'])
            ->rootLevel()
            ->get();

        $byStatus = $objectives->groupBy(fn($o) => $o->status->value);
        $statusStats = [];

        foreach (ObjectiveStatus::cases() as $status) {
            $statusObjectives = $byStatus->get($status->value, collect());
            $statusStats[$status->value] = [
                'label' => $status->label(),
                'color' => $status->color(),
                'icon' => $status->icon(),
                'count' => $statusObjectives->count(),
                'avg_progress' => round($statusObjectives->avg('progress_percentage') ?? 0, 1),
            ];
        }

        $overdue = $objectives->filter(fn($o) => $o->is_overdue);
        $delayed = $objectives->filter(fn($o) => $o->status === ObjectiveStatus::DELAYED);

        return [
            'total' => $objectives->count(),
            'average_progress' => round($objectives->avg('progress_percentage') ?? 0, 1),
            'by_status' => $statusStats,
            'overdue_count' => $overdue->count(),
            'delayed_count' => $delayed->count(),
            'list' => $objectives->map(fn($o) => [
                'id' => $o->id,
                'uuid' => $o->uuid,
                'title' => $o->title,
                'status' => $o->status->value,
                'status_label' => $o->status_label,
                'status_color' => $o->status_color,
                'progress' => $o->progress_percentage,
                'target_date' => $o->target_date?->format('Y-m-d'),
                'is_overdue' => $o->is_overdue,
                'assignee' => $o->assignee?->full_name,
                'key_results' => $o->key_results ?? [],
                'children_count' => $o->children->count(),
            ])->toArray(),
        ];
    }

    /**
     * Get KPIs data with trends.
     */
    public function getKpisData(Department $department, Carbon $periodStart, Carbon $periodEnd): array
    {
        $kpis = DepartmentKpi::forDepartment($department->id)
            ->active()
            ->ordered()
            ->with(['values' => fn($q) => $q->whereBetween('recorded_at', [$periodStart, $periodEnd])])
            ->get();

        return $kpis->map(function ($kpi) use ($periodStart, $periodEnd) {
            $values = $kpi->values;
            $currentValue = $values->sortByDesc('recorded_at')->first()?->value;
            $previousValue = $values->sortByDesc('recorded_at')->skip(1)->first()?->value;

            $trend = $kpi->calculateTrend();

            return [
                'id' => $kpi->id,
                'uuid' => $kpi->uuid,
                'name' => $kpi->name,
                'description' => $kpi->description,
                'unit' => $kpi->unit,
                'target' => $kpi->target_value,
                'current' => $currentValue,
                'previous' => $previousValue,
                'performance_status' => $kpi->performance_status,
                'status_color' => $kpi->getStatusColor(),
                'trend' => $trend,
                'values' => $values->map(fn($v) => [
                    'value' => $v->value,
                    'date' => $v->recorded_at->format('Y-m-d'),
                ])->toArray(),
            ];
        })->toArray();
    }

    /**
     * Get projects data for the period.
     * Note: Projects are not directly linked to departments in this schema.
     * This method returns an empty structure since project-department relationship doesn't exist.
     */
    public function getProjectsData(Department $department, Carbon $periodStart, Carbon $periodEnd): array
    {
        // Projects are not linked to departments in this schema
        // Return empty structure for now - can be enhanced if project-department relationship is added
        return [
            'total' => 0,
            'by_status' => [],
            'list' => [],
        ];
    }

    /**
     * Get tasks data for the period.
     * Note: Tasks are linked through projects which are not linked to departments.
     * This method returns an empty structure since task-department relationship doesn't exist.
     */
    public function getTasksData(Department $department, Carbon $periodStart, Carbon $periodEnd): array
    {
        // Tasks are not linked to departments in this schema
        // Return empty structure for now - can be enhanced if project-department relationship is added
        return [
            'created' => 0,
            'completed' => 0,
            'by_status' => [],
            'by_priority' => [],
            'completion_rate' => 0,
        ];
    }

    /**
     * Get members activity data.
     */
    public function getMembersData(Department $department, Carbon $periodStart, Carbon $periodEnd): array
    {
        $activities = DepartmentActivity::forDepartment($department->id)
            ->forPeriod($periodStart, $periodEnd)
            ->with('user')
            ->get();

        $byUser = $activities->groupBy('user_id');

        $membersStats = $byUser->map(function ($userActivities, $userId) {
            $user = $userActivities->first()->user;
            return [
                'id' => $userId,
                'name' => $user?->full_name ?? 'Unknown',
                'activities_count' => $userActivities->count(),
                'total_hours' => round($userActivities->sum('duration_hours'), 1),
                'categories' => $userActivities->groupBy(fn($a) => $a->category->value)
                    ->map(fn($group) => $group->count())
                    ->toArray(),
            ];
        })->sortByDesc('total_hours')->values();

        return [
            'total_members' => $membersStats->count(),
            'list' => $membersStats->take(10)->toArray(),
            'top_contributors' => $membersStats->take(5)->toArray(),
        ];
    }

    /**
     * Get trends comparing current period with previous.
     */
    public function getTrendsData(Department $department, Carbon $periodStart, Carbon $periodEnd): array
    {
        $periodType = $this->detectPeriodType($periodStart, $periodEnd);
        [$prevStart, $prevEnd] = $periodType->getPreviousPeriodDates($periodStart);

        $currentActivities = DepartmentActivity::forDepartment($department->id)
            ->forPeriod($periodStart, $periodEnd)->count();
        $previousActivities = DepartmentActivity::forDepartment($department->id)
            ->forPeriod($prevStart, $prevEnd)->count();

        $currentObjectivesCompleted = DepartmentObjective::forDepartment($department->id)
            ->forPeriod($periodStart, $periodEnd)
            ->where('status', ObjectiveStatus::COMPLETED->value)->count();
        $previousObjectivesCompleted = DepartmentObjective::forDepartment($department->id)
            ->forPeriod($prevStart, $prevEnd)
            ->where('status', ObjectiveStatus::COMPLETED->value)->count();

        $currentHours = DepartmentActivity::forDepartment($department->id)
            ->forPeriod($periodStart, $periodEnd)->sum('duration_hours');
        $previousHours = DepartmentActivity::forDepartment($department->id)
            ->forPeriod($prevStart, $prevEnd)->sum('duration_hours');

        return [
            'period_type' => $periodType->value,
            'previous_period' => [
                'start' => $prevStart->format('Y-m-d'),
                'end' => $prevEnd->format('Y-m-d'),
            ],
            'activities' => $this->calculateTrend($currentActivities, $previousActivities),
            'objectives_completed' => $this->calculateTrend($currentObjectivesCompleted, $previousObjectivesCompleted),
            'hours' => $this->calculateTrend($currentHours, $previousHours),
        ];
    }

    /**
     * Get unique participants count for the period.
     */
    protected function getUniqueParticipantsCount(Department $department, Carbon $periodStart, Carbon $periodEnd): int
    {
        $activities = DepartmentActivity::forDepartment($department->id)
            ->forPeriod($periodStart, $periodEnd)
            ->get();

        $participants = collect();
        foreach ($activities as $activity) {
            $participants = $participants->merge($activity->participants ?? []);
            $participants->push($activity->user_id);
        }

        return $participants->unique()->count();
    }

    /**
     * Get active projects count.
     * Note: Projects are not linked to departments in this schema.
     */
    protected function getActiveProjectsCount(Department $department): int
    {
        // Projects are not linked to departments in this schema
        return 0;
    }

    /**
     * Get activities timeline data for charts.
     */
    protected function getActivitiesTimeline(Collection $activities): array
    {
        return $activities->groupBy(fn($a) => $a->date->format('Y-m-d'))
            ->map(fn($group, $date) => [
                'date' => $date,
                'count' => $group->count(),
                'hours' => round($group->sum('duration_hours'), 1),
            ])
            ->sortKeys()
            ->values()
            ->toArray();
    }

    /**
     * Detect period type from dates.
     */
    protected function detectPeriodType(Carbon $start, Carbon $end): ReportPeriodType
    {
        $days = $start->diffInDays($end);

        if ($days <= 7) {
            return ReportPeriodType::WEEKLY;
        }
        if ($days <= 31) {
            return ReportPeriodType::MONTHLY;
        }
        if ($days <= 92) {
            return ReportPeriodType::QUARTERLY;
        }
        if ($days <= 366) {
            return ReportPeriodType::ANNUAL;
        }

        return ReportPeriodType::CUSTOM;
    }

    /**
     * Calculate trend between current and previous values.
     */
    protected function calculateTrend(float $current, float $previous): array
    {
        $change = $current - $previous;
        $percentage = $previous > 0 ? round(($change / $previous) * 100, 1) : ($current > 0 ? 100 : 0);

        return [
            'current' => $current,
            'previous' => $previous,
            'change' => $change,
            'percentage' => $percentage,
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
        ];
    }
}
