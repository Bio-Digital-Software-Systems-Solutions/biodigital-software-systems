<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProjectStatisticsService
{
    /**
     * Cache TTL in seconds (10 minutes).
     */
    private const CACHE_TTL = 600;

    /**
     * Get global statistics across all projects (for Dashboard analytics tab).
     *
     * @return array<string, mixed>
     */
    public function getGlobalStatistics(): array
    {
        return Cache::remember('project_stats.global', self::CACHE_TTL, function () {
            return $this->computeGlobalStatistics();
        });
    }

    /**
     * Compute global statistics (uncached).
     *
     * @return array<string, mixed>
     */
    private function computeGlobalStatistics(): array
    {
        $projectIds = Project::pluck('id');

        $tasks = Task::where('taskable_type', 'App\Models\Project')
            ->whereIn('taskable_id', $projectIds)
            ->with(['status', 'assignedUser'])
            ->get();

        $projects = Project::with('manager')->withCount([
            'tasks',
            'tasks as completed_tasks_count' => function ($query) {
                $query->whereHas('status', fn ($q) => $q->where('name', 'completed'));
            },
        ])->get();

        $sprints = Sprint::whereIn('project_id', $projectIds)->get();

        $epics = $tasks->where('type', 'epic');

        return [
            'projects_by_status' => $this->getProjectsByStatus($projects),
            'tasks_by_status' => $this->getTasksByStatus($tasks),
            'tasks_by_priority' => $this->getTasksByPriority($tasks),
            'sprints_by_status' => $this->getSprintsByStatus($sprints),
            'epics_by_status' => $this->getEpicsByStatus($epics),
            'task_evolution' => $this->getMultiPeriodTaskEvolution($tasks),
            'completion_by_project' => $this->getCompletionByProject($projects),
            'projects_by_member' => $this->getProjectsByMember($projects),
            'tasks_by_member' => $this->getTasksByMember($tasks),
            'global_progress' => $this->getGlobalProgress($tasks),
            'velocity' => $this->getVelocity($tasks),
        ];
    }

    /**
     * Get statistics for a single project (for Project Show page).
     *
     * @return array<string, mixed>
     */
    public function getProjectStatistics(Project $project): array
    {
        return Cache::remember("project_stats.{$project->id}", self::CACHE_TTL, function () use ($project) {
            $tasks = $project->tasks()->with(['status', 'assignedUser'])->get();
            $sprints = $project->sprints()->get();
            $epics = $tasks->where('type', 'epic');

            return [
                'tasks_by_status' => $this->getTasksByStatus($tasks),
                'tasks_by_priority' => $this->getTasksByPriority($tasks),
                'sprints_by_status' => $this->getSprintsByStatus($sprints),
                'epics_by_status' => $this->getEpicsByStatus($epics),
                'task_evolution' => $this->getMultiPeriodTaskEvolution($tasks),
                'completion_by_assignee' => $this->getCompletionByAssignee($tasks),
                'tasks_by_member' => $this->getTasksByMember($tasks),
                'global_progress' => $this->getGlobalProgress($tasks),
                'velocity' => $this->getVelocity($tasks),
            ];
        });
    }

    /**
     * Get task statistics across all tasks (for Tasks Index page).
     *
     * @return array<string, mixed>
     */
    public function getTaskStatistics(): array
    {
        return Cache::remember('project_stats.tasks', self::CACHE_TTL, function () {
            $tasks = Task::with(['status', 'assignedUser'])->get();

            return [
                'tasks_by_status' => $this->getTasksByStatus($tasks),
                'tasks_by_priority' => $this->getTasksByPriority($tasks),
                'task_evolution' => $this->getMultiPeriodTaskEvolution($tasks),
                'completion_by_assignee' => $this->getCompletionByAssignee($tasks),
                'tasks_by_member' => $this->getTasksByMember($tasks),
                'global_progress' => $this->getGlobalProgress($tasks),
                'velocity' => $this->getVelocity($tasks),
            ];
        });
    }

    /**
     * Clear all project statistics caches.
     */
    public static function clearCache(?int $projectId = null): void
    {
        Cache::forget('project_stats.global');
        Cache::forget('project_stats.tasks');

        if ($projectId) {
            Cache::forget("project_stats.{$projectId}");
        }
    }

    /**
     * @return array<int, array{label: string, value: int, color: string}>
     */
    private function getProjectsByStatus(Collection $projects): array
    {
        $statusConfig = [
            'active' => ['label' => 'Actif', 'color' => '#10B981'],
            'planning' => ['label' => 'Planification', 'color' => '#3B82F6'],
            'on_hold' => ['label' => 'En pause', 'color' => '#F59E0B'],
            'completed' => ['label' => 'Terminé', 'color' => '#8B5CF6'],
            'cancelled' => ['label' => 'Annulé', 'color' => '#EF4444'],
        ];

        return collect($statusConfig)->map(function ($config, $status) use ($projects) {
            return [
                'label' => $config['label'],
                'value' => $projects->where('status', $status)->count(),
                'color' => $config['color'],
            ];
        })->values()->toArray();
    }

    /**
     * @return array<int, array{label: string, value: int, color: string}>
     */
    private function getTasksByStatus(Collection $tasks): array
    {
        $statusConfig = [
            'completed' => ['label' => 'Terminé', 'color' => '#10B981'],
            'in_progress' => ['label' => 'En cours', 'color' => '#3B82F6'],
            'todo' => ['label' => 'À faire', 'color' => '#F59E0B'],
            'pending' => ['label' => 'En attente', 'color' => '#F97316'],
            'under_review' => ['label' => 'En revue', 'color' => '#8B5CF6'],
            'blocked' => ['label' => 'Bloqué', 'color' => '#EF4444'],
            'cancelled' => ['label' => 'Annulé', 'color' => '#6B7280'],
        ];

        return collect($statusConfig)->map(function ($config, $statusName) use ($tasks) {
            return [
                'label' => $config['label'],
                'value' => $tasks->filter(fn ($t) => $t->status?->name === $statusName)->count(),
                'color' => $config['color'],
            ];
        })->values()->toArray();
    }

    /**
     * @return array<int, array{label: string, value: int, color: string}>
     */
    private function getTasksByPriority(Collection $tasks): array
    {
        $priorityConfig = [
            'highest' => ['label' => 'Critique', 'color' => '#EF4444'],
            'high' => ['label' => 'Haute', 'color' => '#F97316'],
            'medium' => ['label' => 'Moyenne', 'color' => '#F59E0B'],
            'low' => ['label' => 'Basse', 'color' => '#3B82F6'],
            'lowest' => ['label' => 'Très basse', 'color' => '#6B7280'],
        ];

        return collect($priorityConfig)->map(function ($config, $priority) use ($tasks) {
            return [
                'label' => $config['label'],
                'value' => $tasks->where('priority', $priority)->count(),
                'color' => $config['color'],
            ];
        })->values()->toArray();
    }

    /**
     * @return array<int, array{label: string, value: int, color: string}>
     */
    private function getSprintsByStatus(Collection $sprints): array
    {
        $statusConfig = [
            'active' => ['label' => 'Actif', 'color' => '#10B981'],
            'planned' => ['label' => 'Planifié', 'color' => '#3B82F6'],
            'completed' => ['label' => 'Terminé', 'color' => '#8B5CF6'],
            'cancelled' => ['label' => 'Annulé', 'color' => '#EF4444'],
        ];

        return collect($statusConfig)->map(function ($config, $status) use ($sprints) {
            return [
                'label' => $config['label'],
                'value' => $sprints->where('status', $status)->count(),
                'color' => $config['color'],
            ];
        })->values()->toArray();
    }

    /**
     * @return array<int, array{label: string, value: int, color: string}>
     */
    private function getEpicsByStatus(Collection $epics): array
    {
        $statusConfig = [
            'completed' => ['label' => 'Terminé', 'color' => '#10B981'],
            'in_progress' => ['label' => 'En cours', 'color' => '#3B82F6'],
            'todo' => ['label' => 'À faire', 'color' => '#F59E0B'],
            'pending' => ['label' => 'En attente', 'color' => '#F97316'],
            'under_review' => ['label' => 'En revue', 'color' => '#8B5CF6'],
            'blocked' => ['label' => 'Bloqué', 'color' => '#EF4444'],
            'cancelled' => ['label' => 'Annulé', 'color' => '#6B7280'],
        ];

        return collect($statusConfig)->map(function ($config, $statusName) use ($epics) {
            return [
                'label' => $config['label'],
                'value' => $epics->filter(fn ($e) => $e->status?->name === $statusName)->count(),
                'color' => $config['color'],
            ];
        })->values()->toArray();
    }

    /**
     * Get multi-period task evolution data.
     *
     * @return array<string, array<int, array{label: string, created: int, completed: int}>>
     */
    private function getMultiPeriodTaskEvolution(Collection $tasks): array
    {
        return [
            'weekly' => $this->getWeeklyEvolution($tasks),
            'monthly' => $this->getMonthlyEvolution($tasks),
            'quarterly' => $this->getQuarterlyEvolution($tasks),
            'semester' => $this->getSemesterEvolution($tasks),
            'yearly' => $this->getYearlyEvolution($tasks),
        ];
    }

    /**
     * Get weekly task evolution for the last 8 weeks.
     */
    private function getWeeklyEvolution(Collection $tasks): array
    {
        $weeks = [];
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();
            $weekLabel = 'S'.$weekStart->weekOfYear;

            $created = $tasks->filter(function ($task) use ($weekStart, $weekEnd) {
                $createdAt = Carbon::parse($task->created_at);

                return $createdAt->between($weekStart, $weekEnd);
            })->count();

            $completed = $tasks->filter(function ($task) use ($weekStart, $weekEnd) {
                if ($task->status?->name !== 'completed') {
                    return false;
                }
                $updatedAt = Carbon::parse($task->updated_at);

                return $updatedAt->between($weekStart, $weekEnd);
            })->count();

            $weeks[] = [
                'label' => $weekLabel,
                'created' => $created,
                'completed' => $completed,
            ];
        }

        return $weeks;
    }

    /**
     * Get monthly task evolution for the last 12 months.
     */
    private function getMonthlyEvolution(Collection $tasks): array
    {
        $months = [];
        $monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];

        for ($i = 11; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $monthLabel = $monthNames[$monthStart->month - 1];

            $created = $tasks->filter(function ($task) use ($monthStart, $monthEnd) {
                $createdAt = Carbon::parse($task->created_at);

                return $createdAt->between($monthStart, $monthEnd);
            })->count();

            $completed = $tasks->filter(function ($task) use ($monthStart, $monthEnd) {
                if ($task->status?->name !== 'completed') {
                    return false;
                }
                $updatedAt = Carbon::parse($task->updated_at);

                return $updatedAt->between($monthStart, $monthEnd);
            })->count();

            $months[] = [
                'label' => $monthLabel,
                'created' => $created,
                'completed' => $completed,
            ];
        }

        return $months;
    }

    /**
     * Get quarterly task evolution for the last 4 quarters.
     */
    private function getQuarterlyEvolution(Collection $tasks): array
    {
        $quarters = [];

        for ($i = 3; $i >= 0; $i--) {
            $quarterStart = Carbon::now()->subQuarters($i)->startOfQuarter();
            $quarterEnd = Carbon::now()->subQuarters($i)->endOfQuarter();
            $quarterLabel = 'T'.$quarterStart->quarter.' '.$quarterStart->year;

            $created = $tasks->filter(function ($task) use ($quarterStart, $quarterEnd) {
                $createdAt = Carbon::parse($task->created_at);

                return $createdAt->between($quarterStart, $quarterEnd);
            })->count();

            $completed = $tasks->filter(function ($task) use ($quarterStart, $quarterEnd) {
                if ($task->status?->name !== 'completed') {
                    return false;
                }
                $updatedAt = Carbon::parse($task->updated_at);

                return $updatedAt->between($quarterStart, $quarterEnd);
            })->count();

            $quarters[] = [
                'label' => $quarterLabel,
                'created' => $created,
                'completed' => $completed,
            ];
        }

        return $quarters;
    }

    /**
     * Get semester task evolution for the last 4 semesters.
     */
    private function getSemesterEvolution(Collection $tasks): array
    {
        $semesters = [];

        for ($i = 3; $i >= 0; $i--) {
            $now = Carbon::now();
            $currentMonth = $now->month;
            $currentYear = $now->year;

            // Calculate semester: S1 = Jan-Jun, S2 = Jul-Dec
            $currentSemester = $currentMonth <= 6 ? 1 : 2;
            $targetSemester = $currentSemester;
            $targetYear = $currentYear;

            // Go back $i semesters
            for ($j = 0; $j < $i; $j++) {
                $targetSemester--;
                if ($targetSemester < 1) {
                    $targetSemester = 2;
                    $targetYear--;
                }
            }

            $semesterStart = Carbon::create($targetYear, $targetSemester === 1 ? 1 : 7, 1)->startOfDay();
            $semesterEnd = Carbon::create($targetYear, $targetSemester === 1 ? 6 : 12, 1)->endOfMonth();
            $semesterLabel = 'S'.$targetSemester.' '.$targetYear;

            $created = $tasks->filter(function ($task) use ($semesterStart, $semesterEnd) {
                $createdAt = Carbon::parse($task->created_at);

                return $createdAt->between($semesterStart, $semesterEnd);
            })->count();

            $completed = $tasks->filter(function ($task) use ($semesterStart, $semesterEnd) {
                if ($task->status?->name !== 'completed') {
                    return false;
                }
                $updatedAt = Carbon::parse($task->updated_at);

                return $updatedAt->between($semesterStart, $semesterEnd);
            })->count();

            $semesters[] = [
                'label' => $semesterLabel,
                'created' => $created,
                'completed' => $completed,
            ];
        }

        return $semesters;
    }

    /**
     * Get yearly task evolution for the last 3 years.
     */
    private function getYearlyEvolution(Collection $tasks): array
    {
        $years = [];

        for ($i = 2; $i >= 0; $i--) {
            $yearStart = Carbon::now()->subYears($i)->startOfYear();
            $yearEnd = Carbon::now()->subYears($i)->endOfYear();
            $yearLabel = (string) $yearStart->year;

            $created = $tasks->filter(function ($task) use ($yearStart, $yearEnd) {
                $createdAt = Carbon::parse($task->created_at);

                return $createdAt->between($yearStart, $yearEnd);
            })->count();

            $completed = $tasks->filter(function ($task) use ($yearStart, $yearEnd) {
                if ($task->status?->name !== 'completed') {
                    return false;
                }
                $updatedAt = Carbon::parse($task->updated_at);

                return $updatedAt->between($yearStart, $yearEnd);
            })->count();

            $years[] = [
                'label' => $yearLabel,
                'created' => $created,
                'completed' => $completed,
            ];
        }

        return $years;
    }

    /**
     * Get completion rate by project (for global dashboard).
     *
     * @return array<int, array{name: string, value: float, color: string, completed: int, total: int}>
     */
    private function getCompletionByProject(Collection $projects): array
    {
        $colors = ['#3B82F6', '#10B981', '#F97316', '#8B5CF6', '#EF4444', '#F59E0B', '#06B6D4', '#EC4899'];

        return $projects
            ->filter(fn ($p) => $p->tasks_count > 0)
            ->sortByDesc('tasks_count')
            ->take(10)
            ->values()
            ->map(function ($project, $i) use ($colors) {
                $rate = $project->tasks_count > 0
                    ? round(($project->completed_tasks_count / $project->tasks_count) * 100, 1)
                    : 0;

                return [
                    'name' => $project->name,
                    'value' => $rate,
                    'color' => $colors[$i % count($colors)],
                    'completed' => $project->completed_tasks_count,
                    'total' => $project->tasks_count,
                ];
            })->toArray();
    }

    /**
     * Get completion rate by assignee.
     *
     * @return array<int, array{name: string, value: float, color: string, completed: int, total: int}>
     */
    private function getCompletionByAssignee(Collection $tasks): array
    {
        $colors = ['#3B82F6', '#10B981', '#F97316', '#8B5CF6', '#EF4444', '#F59E0B', '#06B6D4', '#EC4899'];

        $grouped = $tasks->filter(fn ($t) => $t->assignedUser !== null)->groupBy('assigned_to');

        return $grouped
            ->map(function ($userTasks, $userId) {
                $user = $userTasks->first()->assignedUser;
                $total = $userTasks->count();
                $completed = $userTasks->filter(fn ($t) => $t->status?->name === 'completed')->count();
                $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

                return [
                    'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')),
                    'value' => $rate,
                    'completed' => $completed,
                    'total' => $total,
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->values()
            ->map(function ($item, $i) use ($colors) {
                $item['color'] = $colors[$i % count($colors)];

                return $item;
            })
            ->toArray();
    }

    /**
     * Get projects distribution by member (project manager).
     *
     * @return array<int, array{label: string, value: int, color: string}>
     */
    private function getProjectsByMember(Collection $projects): array
    {
        $colors = ['#3B82F6', '#10B981', '#F97316', '#8B5CF6', '#EF4444', '#F59E0B', '#06B6D4', '#EC4899'];

        $grouped = $projects->filter(fn ($p) => $p->manager !== null)->groupBy('project_manager_id');

        return $grouped
            ->map(function ($userProjects) {
                $user = $userProjects->first()->manager;

                return [
                    'label' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')),
                    'value' => $userProjects->count(),
                ];
            })
            ->sortByDesc('value')
            ->take(10)
            ->values()
            ->map(function ($item, $i) use ($colors) {
                $item['color'] = $colors[$i % count($colors)];

                return $item;
            })
            ->toArray();
    }

    /**
     * Get tasks distribution by member (assigned user).
     *
     * @return array<int, array{label: string, value: int, color: string}>
     */
    private function getTasksByMember(Collection $tasks): array
    {
        $colors = [
            '#3B82F6', '#10B981', '#F97316', '#8B5CF6', '#EF4444',
            '#F59E0B', '#06B6D4', '#EC4899', '#84CC16', '#6366F1',
            '#14B8A6', '#F43F5E', '#A855F7', '#22C55E', '#0EA5E9',
            '#D946EF', '#EAB308', '#64748B', '#FB923C', '#4ADE80',
        ];

        $grouped = $tasks->filter(fn ($t) => $t->assignedUser !== null)->groupBy('assigned_to');

        return $grouped
            ->map(function ($userTasks) {
                $user = $userTasks->first()->assignedUser;

                return [
                    'label' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')),
                    'value' => $userTasks->count(),
                ];
            })
            ->sortByDesc('value')
            ->take(100)
            ->values()
            ->map(function ($item, $i) use ($colors) {
                $item['color'] = $colors[$i % count($colors)];

                return $item;
            })
            ->toArray();
    }

    /**
     * Get global progress (overall completion rate).
     *
     * @return array{percentage: float, completed: int, total: int}
     */
    private function getGlobalProgress(Collection $tasks): array
    {
        $total = $tasks->count();
        $completed = $tasks->filter(function ($task) {
            return $task->status && $task->status->name === 'completed';
        })->count();

        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'percentage' => $percentage,
            'completed' => $completed,
            'total' => $total,
        ];
    }

    /**
     * Get velocity statistics (average completed tasks per period).
     *
     * @return array{daily: array, weekly: array, monthly: array}
     */
    private function getVelocity(Collection $tasks): array
    {
        $completedTasks = $tasks->filter(function ($task) {
            return $task->status && $task->status->name === 'completed';
        });

        // Daily velocity (last 30 days)
        $dailyData = $this->calculateDailyVelocity($completedTasks);

        // Weekly velocity (last 8 weeks)
        $weeklyData = $this->calculateWeeklyVelocity($completedTasks);

        // Monthly velocity (last 12 months)
        $monthlyData = $this->calculateMonthlyVelocity($completedTasks);

        return [
            'daily' => $dailyData,
            'weekly' => $weeklyData,
            'monthly' => $monthlyData,
        ];
    }

    /**
     * Calculate daily velocity (average tasks completed per day over last 30 days).
     */
    private function calculateDailyVelocity(Collection $completedTasks): array
    {
        $days = 30;
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $tasksInPeriod = $completedTasks->filter(function ($task) use ($startDate, $endDate) {
            $updatedAt = Carbon::parse($task->updated_at);

            return $updatedAt->between($startDate, $endDate);
        })->count();

        $average = round($tasksInPeriod / $days, 1);

        // Calculate max for gauge (round up to nearest 10 or use reasonable default)
        $max = max(ceil($average * 2 / 10) * 10, 10);

        return [
            'value' => $average,
            'total' => $tasksInPeriod,
            'period_count' => $days,
            'max' => $max,
            'label' => 'jour',
        ];
    }

    /**
     * Calculate weekly velocity (average tasks completed per week over last 8 weeks).
     */
    private function calculateWeeklyVelocity(Collection $completedTasks): array
    {
        $weeks = 8;
        $startDate = Carbon::now()->subWeeks($weeks)->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();

        $tasksInPeriod = $completedTasks->filter(function ($task) use ($startDate, $endDate) {
            $updatedAt = Carbon::parse($task->updated_at);

            return $updatedAt->between($startDate, $endDate);
        })->count();

        $average = round($tasksInPeriod / $weeks, 1);

        // Calculate max for gauge
        $max = max(ceil($average * 2 / 10) * 10, 50);

        return [
            'value' => $average,
            'total' => $tasksInPeriod,
            'period_count' => $weeks,
            'max' => $max,
            'label' => 'semaine',
        ];
    }

    /**
     * Calculate monthly velocity (average tasks completed per month over last 12 months).
     */
    private function calculateMonthlyVelocity(Collection $completedTasks): array
    {
        $months = 12;
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $tasksInPeriod = $completedTasks->filter(function ($task) use ($startDate, $endDate) {
            $updatedAt = Carbon::parse($task->updated_at);

            return $updatedAt->between($startDate, $endDate);
        })->count();

        $average = round($tasksInPeriod / $months, 1);

        // Calculate max for gauge
        $max = max(ceil($average * 2 / 50) * 50, 200);

        return [
            'value' => $average,
            'total' => $tasksInPeriod,
            'period_count' => $months,
            'max' => $max,
            'label' => 'mois',
        ];
    }
}
