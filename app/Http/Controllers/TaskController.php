<?php

namespace App\Http\Controllers;

use App\Enums\Employee\EmployeeStatus;
use App\Enums\Star\StarStatus;
use App\Models\Employee;
use App\Models\Program;
use App\Models\Star;
use App\Models\Status;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\TaskParticipant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        // Get all users
        $users = User::all()->map(fn($user) => [
            'id' => $user->id,
            'uuid' => $user->uuid ?? null,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'type' => 'user',
        ]);

        // Get active employees
        $employees = Employee::with('user')
            ->where('status', EmployeeStatus::ACTIVE)
            ->get()
            ->map(fn($employee) => [
                'id' => $employee->user_id,
                'uuid' => $employee->uuid,
                'first_name' => $employee->user->first_name ?? '',
                'last_name' => $employee->user->last_name ?? '',
                'email' => $employee->user->email ?? '',
                'position' => $employee->position,
                'type' => 'employee',
            ]);

        // Get active stars
        $stars = Star::with('user')
            ->where('status', StarStatus::ACTIVE)
            ->get()
            ->map(fn($star) => [
                'id' => $star->user_id,
                'uuid' => $star->uuid,
                'first_name' => $star->user->first_name ?? '',
                'last_name' => $star->user->last_name ?? '',
                'email' => $star->user->email ?? '',
                'title' => $star->title,
                'type' => 'star',
            ]);

        return Inertia::render('Tasks/Create', [
            'projects' => $projects,
            'programs' => $programs,
            'statuses' => $statuses,
            'users' => $users,
            'employees' => $employees,
            'stars' => $stars,
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
            'progress' => 'nullable|integer|min:0|max:100',
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
        if (isset($validated['taskable_type']) && $validated['taskable_type'] === 'App\\Models\\Project' && $request->has('from_project')) {
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
        $task->load([
            'status',
            'program',
            'project',
            'assignedUser',
            'participants.user',
            'taskAttachments.user',
        ]);

        // Load comments separately to properly eager-load nested relations
        $comments = $task->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies' => function ($query) {
                $query->with('user')->orderBy('created_at', 'asc');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        $task->setRelation('comments', $comments);

        // Load activity logs for status changes
        $activities = $task->activities()
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($activity) {
                // Include created events or status_id changes
                if ($activity->description === 'created') {
                    return true;
                }
                $attributes = $activity->properties['attributes'] ?? [];

                return isset($attributes['status_id']);
            })
            ->map(function ($activity) {
                $oldStatusId = $activity->properties['old']['status_id'] ?? null;
                $newStatusId = $activity->properties['attributes']['status_id'] ?? null;

                $oldStatus = $oldStatusId ? Status::find($oldStatusId) : null;
                $newStatus = $newStatusId ? Status::find($newStatusId) : null;

                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'old_status' => $oldStatus ? ['id' => $oldStatus->id, 'name' => $oldStatus->name, 'color' => $oldStatus->color] : null,
                    'new_status' => $newStatus ? ['id' => $newStatus->id, 'name' => $newStatus->name, 'color' => $newStatus->color] : null,
                    'causer' => $activity->causer ? [
                        'id' => $activity->causer->id,
                        'first_name' => $activity->causer->first_name,
                        'last_name' => $activity->causer->last_name,
                    ] : null,
                    'created_at' => $activity->created_at->toISOString(),
                ];
            })
            ->values();

        $users = User::all();

        return Inertia::render('Tasks/Show', [
            'task' => $task,
            'users' => $users,
            'activities' => $activities,
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
            'progress' => 'nullable|integer|min:0|max:100',
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

        return redirect()->route('tasks.show', $task->uuid)
            ->with('success', 'Tâche mise à jour avec succès.');
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

    /**
     * Update just the progress of a task.
     */
    public function updateProgress(Request $request, Task $task)
    {
        $validated = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $task->update(['progress' => $validated['progress']]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Task progress updated successfully.',
                'progress' => $task->progress,
            ]);
        }

        return back()->with('success', 'Task progress updated successfully.');
    }

    // ==========================================
    // Task Participants
    // ==========================================

    /**
     * Add a participant to a task.
     */
    public function addParticipant(Request $request, Task $task)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|max:50',
        ]);

        // Check if user is already a participant
        $existing = TaskParticipant::where('task_id', $task->id)
            ->where('user_id', $validated['user_id'])
            ->first();

        if ($existing) {
            return back()->withErrors(['user_id' => 'User is already a participant on this task.']);
        }

        TaskParticipant::create([
            'task_id' => $task->id,
            'user_id' => $validated['user_id'],
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'Participant added successfully.');
    }

    /**
     * Update a participant's role.
     */
    public function updateParticipant(Request $request, Task $task, TaskParticipant $participant)
    {
        $validated = $request->validate([
            'role' => 'required|string|max:50',
        ]);

        $participant->update(['role' => $validated['role']]);

        return back()->with('success', 'Participant role updated successfully.');
    }

    /**
     * Remove a participant from a task.
     */
    public function removeParticipant(Task $task, TaskParticipant $participant)
    {
        $participant->delete();

        return back()->with('success', 'Participant removed successfully.');
    }

    // ==========================================
    // Task Comments
    // ==========================================

    /**
     * Add a comment to a task.
     */
    public function addComment(Request $request, Task $task)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:10000',
            'parent_id' => 'nullable|exists:task_comments,id',
        ]);

        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $message = $validated['parent_id'] ? 'Reply added successfully.' : 'Comment added successfully.';

        return back()->with('success', $message);
    }

    /**
     * Update a comment.
     */
    public function updateComment(Request $request, Task $task, TaskComment $comment)
    {
        // Only the comment author can edit their comment
        if ($comment->user_id !== auth()->id()) {
            abort(403, 'You can only edit your own comments.');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        $comment->update(['content' => $validated['content']]);

        return back()->with('success', 'Comment updated successfully.');
    }

    /**
     * Delete a comment.
     */
    public function deleteComment(Task $task, TaskComment $comment)
    {
        // Check if task is completed or closed - no deletion allowed
        $taskStatus = $task->status?->name;
        if (in_array($taskStatus, ['completed', 'closed', 'terminé', 'fermé'])) {
            abort(403, 'Cannot delete comments on completed or closed tasks.');
        }

        // Only the comment author or super admin can delete the comment
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');
        $isAuthor = $comment->user_id === $user->id;

        if (!$isAuthor && !$isSuperAdmin) {
            abort(403, 'Only the comment author or super admin can delete this comment.');
        }

        $comment->delete();

        return back()->with('success', 'Comment deleted successfully.');
    }

    // ==========================================
    // Task Attachments
    // ==========================================

    /**
     * Upload an attachment to a task.
     */
    public function addAttachment(Request $request, Task $task)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:51200', // Max 50MB
        ]);

        $file = $request->file('file');
        $path = $file->store('task-attachments/' . $task->id, 'public');

        TaskAttachment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return back()->with('success', 'Attachment uploaded successfully.');
    }

    /**
     * Delete an attachment.
     */
    public function deleteAttachment(Task $task, TaskAttachment $attachment)
    {
        // Only the uploader or admin can delete the attachment
        if ($attachment->user_id !== auth()->id() && ! auth()->user()->can('delete attachments')) {
            abort(403, 'You can only delete your own attachments.');
        }

        // Delete the file from storage
        Storage::disk('public')->delete($attachment->file_path);

        $attachment->delete();

        return back()->with('success', 'Attachment deleted successfully.');
    }

    /**
     * Download an attachment.
     */
    public function downloadAttachment(Task $task, TaskAttachment $attachment)
    {
        $path = Storage::disk('public')->path($attachment->file_path);

        if (! file_exists($path)) {
            abort(404, 'File not found.');
        }

        return response()->download($path, $attachment->file_name);
    }
}
