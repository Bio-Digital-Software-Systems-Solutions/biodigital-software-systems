<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTask;
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
        $query = ProjectTask::with(['project', 'assignee', 'reporter', 'sprint']);

        // Apply filters
        if ($request->has('project_id') && $request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('assignee_id') && $request->assignee_id) {
            $query->where('assignee_id', $request->assignee_id);
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

        // Group tasks by status
        $tasksByStatus = [
            'todo' => $tasks->where('status', 'todo')->values(),
            'in_progress' => $tasks->where('status', 'in_progress')->values(),
            'in_review' => $tasks->where('status', 'in_review')->values(),
            'blocked' => $tasks->where('status', 'blocked')->values(),
            'done' => $tasks->where('status', 'done')->values(),
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

    public function updateStatus(Request $request, ProjectTask $task)
    {
        $validated = $request->validate([
            'status' => 'required|in:todo,in_progress,in_review,blocked,done',
        ]);

        $task->update($validated);

        return response()->json([
            'message' => 'Task status updated successfully',
            'task' => $task->load(['project', 'assignee', 'reporter']),
        ]);
    }
}
