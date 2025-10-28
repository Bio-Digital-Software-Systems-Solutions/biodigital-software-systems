<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GanttController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view programs');
    }

    public function index(Request $request)
    {
        $query = Project::with(['manager']);

        // Apply filters
        if ($request->has('project_id') && $request->project_id) {
            $query->where('id', $request->project_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $projects = $query->latest()->get();

        // Transform projects and tasks for Gantt chart
        $ganttData = $projects->map(function ($project) {
            // Load tasks for this project using the polymorphic relation
            $tasks = Task::where('project_id', $project->id)
                ->whereNotNull('due_date')
                ->with(['assignee', 'status'])
                ->get();

            return [
                'id' => 'project-'.$project->uuid,
                'uuid' => $project->uuid,
                'name' => $project->name,
                'start' => $project->start_date,
                'end' => $project->end_date,
                'progress' => $this->calculateProgress($tasks),
                'type' => 'project',
                'color' => $project->color,
                'tasks' => $tasks->map(function ($task) {
                    return [
                        'id' => 'task-'.$task->uuid,
                        'uuid' => $task->uuid,
                        'name' => $task->title,
                        'start' => $task->created_at,
                        'end' => $task->due_date,
                        'progress' => $this->getTaskProgress($task),
                        'type' => 'task',
                        'assignee' => $task->assignee ? $task->assignee->first_name.' '.$task->assignee->last_name : null,
                        'priority' => $task->priority,
                        'status' => $task->status ? $task->status->name : null,
                    ];
                }),
            ];
        });

        // Get all projects for filter
        $allProjects = Project::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Gantt/Index', [
            'ganttData' => $ganttData,
            'projects' => $allProjects,
            'filters' => $request->only(['project_id', 'status']),
        ]);
    }

    private function calculateProgress($tasks): int
    {
        $totalTasks = $tasks->count();
        if ($totalTasks === 0) {
            return 0;
        }

        $completedTasks = $tasks->filter(function ($task) {
            return $task->status && $task->status->name === 'done';
        })->count();

        return round(($completedTasks / $totalTasks) * 100);
    }

    private function getTaskProgress(Task $task): int
    {
        if (!$task->status) {
            return 0;
        }

        return match ($task->status->name) {
            'done', 'completed' => 100,
            'in_progress' => 50,
            'in_review' => 75,
            default => 0,
        };
    }
}
