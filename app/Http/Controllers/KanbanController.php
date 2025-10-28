<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Status;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KanbanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view programs');
    }

    public function index(Request $request)
    {
        $query = Task::with(['project', 'assignee', 'reporter', 'sprint', 'status']);

        // Apply filters
        if ($request->has('project_id') && $request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('assignee_id') && $request->assignee_id) {
            $query->where('assigned_to', $request->assignee_id);
        }

        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('sprint_id') && $request->sprint_id) {
            $query->where('sprint_id', $request->sprint_id);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $tasks = $query->latest()->get();

        // Get status name-to-id mapping
        $statuses = Status::all()->pluck('id', 'name');

        // Group tasks by status name (for frontend compatibility)
        $tasksByStatus = [
            'todo' => $tasks->filter(function ($task) {
                return $task->status && $task->status->name === 'todo';
            })->values()->map(function ($task) {
                return $this->formatTaskForFrontend($task);
            }),
            'in_progress' => $tasks->filter(function ($task) {
                return $task->status && $task->status->name === 'in_progress';
            })->values()->map(function ($task) {
                return $this->formatTaskForFrontend($task);
            }),
            'in_review' => $tasks->filter(function ($task) {
                return $task->status && $task->status->name === 'in_review';
            })->values()->map(function ($task) {
                return $this->formatTaskForFrontend($task);
            }),
            'blocked' => $tasks->filter(function ($task) {
                return $task->status && $task->status->name === 'blocked';
            })->values()->map(function ($task) {
                return $this->formatTaskForFrontend($task);
            }),
            'done' => $tasks->filter(function ($task) {
                return $task->status && $task->status->name === 'done';
            })->values()->map(function ($task) {
                return $this->formatTaskForFrontend($task);
            }),
        ];

        // Get filter data
        $projects = Project::select('id', 'name')->orderBy('name')->get();
        $users = User::select('id', 'first_name', 'last_name')->orderBy('first_name')->get();
        $sprints = \App\Models\Sprint::with('project:id,name')
            ->select('id', 'name', 'project_id', 'start_date', 'end_date')
            ->where('end_date', '>=', now())
            ->orderBy('start_date')
            ->get();

        return Inertia::render('Kanban/Index', [
            'tasksByStatus' => $tasksByStatus,
            'projects' => $projects,
            'users' => $users,
            'sprints' => $sprints,
            'filters' => $request->only(['project_id', 'assignee_id', 'priority', 'type', 'sprint_id', 'search']),
        ]);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status' => 'required|in:todo,in_progress,in_review,blocked,done',
        ]);

        // Find the status ID from the status name
        $status = Status::where('name', $validated['status'])->first();

        if (!$status) {
            return response()->json([
                'message' => 'Invalid status',
            ], 422);
        }

        $task->update(['status_id' => $status->id]);

        return response()->json([
            'message' => 'Task status updated successfully',
            'task' => $this->formatTaskForFrontend($task->fresh()->load(['project', 'assignee', 'reporter', 'status'])),
        ]);
    }

    /**
     * Format task data for frontend consumption
     */
    private function formatTaskForFrontend(Task $task): array
    {
        return [
            'id' => $task->id,
            'uuid' => $task->uuid,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status ? $task->status->name : null,
            'priority' => $task->priority ?? 'medium',
            'type' => $task->type ?? 'task',
            'assignee' => $task->assignee ? [
                'id' => $task->assignee->id,
                'first_name' => $task->assignee->first_name,
                'last_name' => $task->assignee->last_name,
            ] : null,
            'project' => $task->project ? [
                'id' => $task->project->id,
                'name' => $task->project->name,
                'color' => $task->project->color ?? null,
            ] : null,
            'due_date' => $task->due_date ? $task->due_date->toDateString() : null,
        ];
    }
}
