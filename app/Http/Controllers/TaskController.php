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
        $tasks = Task::with(['status', 'program', 'assignedUser'])
            ->when(request('status'), function ($query, $status) {
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
            })
            ->orderBy('due_date')
            ->paginate(10);

        $programs = Program::active()->get();
        $statuses = Status::all();
        $users = User::all();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'programs' => $programs,
            'statuses' => $statuses,
            'users' => $users,
            'filters' => request()->only(['status', 'program', 'priority', 'assigned_to']),
        ]);
    }

    public function create(Request $request)
    {
        $programs = Program::active()->get();
        $statuses = Status::all();
        $users = User::all();

        return Inertia::render('Tasks/Create', [
            'programs' => $programs,
            'statuses' => $statuses,
            'users' => $users,
            'programId' => $request->query('program'),
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
            'program_id' => 'required|exists:programs,id',
            'assigned_to' => 'nullable|exists:users,id',
            'image' => 'nullable',
        ]);

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

        // Redirect to program if task was created from a program page
        if ($request->input('program_id')) {
            return redirect()->route('programs.show', $request->input('program_id'))
                ->with('success', 'Task created successfully.');
        }

        return redirect()->route('tasks.index')
            ->with('success', 'Task created successfully.');
    }

    public function show(Task $task)
    {
        $task->load(['status', 'program', 'assignedUser']);

        return Inertia::render('Tasks/Show', [
            'task' => $task,
        ]);
    }

    public function edit(Task $task)
    {
        $task->load(['status', 'program', 'assignedUser']);
        $programs = Program::active()->get();
        $statuses = Status::all();
        $users = User::all();

        return Inertia::render('Tasks/Edit', [
            'task' => $task,
            'programs' => $programs,
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
            'program_id' => 'required|exists:programs,id',
            'assigned_to' => 'nullable|exists:users,id',
            'image' => 'nullable',
        ]);

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
