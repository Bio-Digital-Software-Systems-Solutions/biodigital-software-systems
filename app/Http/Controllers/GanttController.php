<?php

namespace App\Http\Controllers;

use App\Models\Project;
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
        $query = Project::with(['tasks' => function ($q) {
            $q->whereNotNull('start_date')
                ->orWhereNotNull('due_date')
                ->with(['assignee', 'project']);
        }, 'manager']);

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
            return [
                'id' => 'project-'.$project->uuid,
                'uuid' => $project->uuid,
                'name' => $project->name,
                'start' => $project->start_date,
                'end' => $project->end_date,
                'progress' => $this->calculateProgress($project),
                'type' => 'project',
                'color' => $project->color,
                'tasks' => $project->tasks->map(function ($task) {
                    return [
                        'id' => 'task-'.$task->uuid,
                        'uuid' => $task->uuid,
                        'name' => $task->title,
                        'start' => $task->start_date ?? $task->created_at,
                        'end' => $task->due_date,
                        'progress' => $task->status === 'done' ? 100 : ($task->status === 'in_progress' ? 50 : 0),
                        'type' => 'task',
                        'assignee' => $task->assignee ? $task->assignee->first_name.' '.$task->assignee->last_name : null,
                        'priority' => $task->priority,
                        'status' => $task->status,
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

    private function calculateProgress(Project $project): int
    {
        $totalTasks = $project->tasks->count();
        if ($totalTasks === 0) {
            return 0;
        }

        $completedTasks = $project->tasks->where('status', 'done')->count();

        return round(($completedTasks / $totalTasks) * 100);
    }
}
