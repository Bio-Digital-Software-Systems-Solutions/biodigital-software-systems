<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view tasks')->only(['index', 'show']);
        $this->middleware('can:create tasks')->only(['create', 'store']);
        $this->middleware('can:edit tasks')->only(['edit', 'update', 'updateStatus', 'toggleComplete', 'bulkToggleComplete']);
        $this->middleware('can:delete tasks')->only(['destroy']);
    }

    public function index()
    {
        // Build the base query
        $query = Task::query();

        // Apply filters
        $query->when(request('status'), function ($query, $status) {
            $query->whereHas('status', function ($q) use ($status) {
                $q->where('name', $status);
            });
        })
        ->when(request('program'), function ($query, $program) {
            $query->where('program_id', $program);
        })
        ->when(request('priority'), function ($query, $priority) {
            $query->where('priority', $priority);
        })
        ->when(request('assigned_to'), function ($query, $userId) {
            $query->where('assigned_to', $userId);
        });

        // Apply sorting
        $sortBy = request('sort_by');
        $direction = request('sort_direction', 'asc');

        if ($sortBy === 'title') {
            $query->orderBy('tasks.title', $direction);
        } elseif ($sortBy === 'priority') {
            $query->orderBy('tasks.priority', $direction);
        } elseif ($sortBy === 'due_date') {
            $query->orderBy('tasks.due_date', $direction);
        } elseif ($sortBy === 'status') {
            $query->leftJoin('statuses', 'tasks.status_id', '=', 'statuses.id')
                  ->orderBy('statuses.name', $direction)
                  ->select('tasks.*');
        } elseif ($sortBy === 'assigned_to') {
            $query->leftJoin('users', 'tasks.assigned_to', '=', 'users.id')
                  ->orderBy('users.first_name', $direction)
                  ->select('tasks.*');
        } elseif ($sortBy === 'program') {
            $query->leftJoin('programs', 'tasks.program_id', '=', 'programs.id')
                  ->orderBy('programs.name', $direction)
                  ->select('tasks.*');
        } elseif ($sortBy === 'project') {
            $query->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
                  ->orderBy('projects.name', $direction)
                  ->select('tasks.*');
        } else {
            // Default sorting when no sort specified
            $query->orderBy('tasks.created_at', 'desc');
        }

        // Execute query with pagination
        $tasks = $query->paginate(10);

        // Eager load relationships after pagination to avoid N+1
        $tasks->load(['status', 'program', 'project', 'assignedUser']);

        $programs = Program::active()->get();
        $statuses = Status::all();
        $users = User::all();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'programs' => $programs,
            'statuses' => $statuses,
            'users' => $users,
            'filters' => request()->only(['status', 'program', 'priority', 'assigned_to', 'sort_by', 'sort_direction']),
        ]);
    }

    public function create(Request $request)
    {
        $projects = \App\Models\Project::where('status', '!=', 'cancelled')->get();
        $programs = Program::active()->get();
        $statuses = Status::all();
        $users = User::all();

        return Inertia::render('Tasks/Create', [
            'projects' => $projects,
            'programs' => $programs,
            'statuses' => $statuses,
            'users' => $users,
            'projectId' => $request->query('project'),
            'taskableType' => $request->query('taskable_type'),
            'taskableId' => $request->query('taskable_id'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date|after:today',
            'priority' => 'required|in:low,medium,high',
            'estimated_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status_id' => 'required|exists:statuses,id',
            'taskable_type' => 'nullable|string|in:App\\Models\\Project,App\\Models\\Program',
            'taskable_id' => 'nullable|integer',
            'assigned_to' => 'nullable|exists:users,id',
            'image' => 'nullable',
        ]);

        // Backward compatibility: if project_id or program_id is provided, set taskable
        if ($request->filled('project_id')) {
            $validated['taskable_type'] = 'App\\Models\\Project';
            $validated['taskable_id'] = $request->input('project_id');
            $validated['project_id'] = $request->input('project_id');
        } elseif ($request->filled('program_id')) {
            $validated['taskable_type'] = 'App\\Models\\Program';
            $validated['taskable_id'] = $request->input('program_id');
            $validated['program_id'] = $request->input('program_id');
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('tasks', 'public');
        }
        // Handle image from TUS upload (just filename)
        elseif ($request->filled('image') && is_string($request->image)) {
            // Image has already been uploaded via TUS to tasks directory
            $validated['image'] = 'tasks/' . $request->image;
        }

        $task = Task::create($validated);

        // Redirect to project if task was created from a project page
        if ($validated['taskable_type'] === 'App\\Models\\Project' && $request->has('from_project')) {
            $project = \App\Models\Project::find($validated['taskable_id']);
            if ($project) {
                return redirect()->route('projects.show', $project->uuid)
                    ->with('success', 'Task created successfully.');
            }
        }

        return redirect()->route('tasks.index')
            ->with('success', 'Task created successfully.');
    }

    public function show(Task $task)
    {
        $task->load(['status', 'program', 'project', 'assignedUser']);

        return Inertia::render('Tasks/Show', [
            'task' => $task,
        ]);
    }

    public function edit(Task $task)
    {
        $task->load(['status', 'program', 'project', 'assignedUser', 'taskable']);
        $programs = Program::active()->get();
        $projects = \App\Models\Project::where('status', '!=', 'cancelled')->get();
        $statuses = Status::all();
        $users = User::all();

        return Inertia::render('Tasks/Edit', [
            'task' => $task,
            'programs' => $programs,
            'projects' => $projects,
            'statuses' => $statuses,
            'users' => $users,
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status_id' => 'required|exists:statuses,id',
            'taskable_type' => 'nullable|string|in:App\\Models\\Project,App\\Models\\Program',
            'taskable_id' => 'nullable|integer',
            'assigned_to' => 'nullable|exists:users,id',
            'image' => 'nullable',
        ]);

        // Backward compatibility: if project_id or program_id is provided, set taskable
        if ($request->filled('project_id')) {
            $validated['taskable_type'] = 'App\\Models\\Project';
            $validated['taskable_id'] = $request->input('project_id');
            $validated['project_id'] = $request->input('project_id');
        } elseif ($request->filled('program_id')) {
            $validated['taskable_type'] = 'App\\Models\\Program';
            $validated['taskable_id'] = $request->input('program_id');
            $validated['program_id'] = $request->input('program_id');
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($task->image) {
                \Storage::disk('public')->delete($task->image);
            }
            $validated['image'] = $request->file('image')->store('tasks', 'public');
        }
        // Handle image from TUS upload (just filename)
        elseif ($request->filled('image') && is_string($request->image)) {
            // Delete old image if it exists
            if ($task->image) {
                \Storage::disk('public')->delete($task->image);
            }
            // Image has already been uploaded via TUS to tasks directory
            $validated['image'] = 'tasks/' . $request->image;
        }

        $task->update($validated);

        return redirect()->route('tasks.index')
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status_id' => 'required|exists:statuses,id',
        ]);

        $task->update(['status_id' => $validated['status_id']]);

        return back()->with('success', 'Task status updated successfully.');
    }

    public function toggleComplete(Request $request, Task $task)
    {
        $completedStatus = Status::where('name', 'completed')->first();
        $pendingStatus = Status::where('name', 'pending')->first();

        $isCompleted = $task->status_id === $completedStatus?->id;
        $newStatusId = $isCompleted ? $pendingStatus?->id : $completedStatus?->id;

        if ($newStatusId) {
            $task->update(['status_id' => $newStatusId]);
        }

        return back();
    }

    public function bulkToggleComplete(Request $request)
    {
        $validated = $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
            'completed' => 'required|boolean',
        ]);

        $statusName = $validated['completed'] ? 'completed' : 'pending';
        $status = Status::where('name', $statusName)->first();

        if ($status) {
            Task::whereIn('id', $validated['task_ids'])
                ->update(['status_id' => $status->id]);
        }

        return back()->with('success', 'Tasks updated successfully.');
    }
}
