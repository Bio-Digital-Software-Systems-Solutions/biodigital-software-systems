<?php

namespace App\Http\Controllers;

use App\Enums\EpicStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Epic Controller
 *
 * Epics are high-level tasks that use the EpicStatus enum for their status values.
 * Valid epic statuses: todo, pending, in_progress, under_review, completed, cancelled
 */
class EpicController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view programs');
    }

    public function index(Request $request)
    {
        $query = Task::with(['taskable', 'assignee', 'reporter', 'status', 'attachments.user'])
            ->where('type', 'epic')
            ->where('taskable_type', 'App\Models\Project');

        // Apply filters
        if ($request->has('project_id') && $request->project_id) {
            $query->where('taskable_id', $request->project_id);
        }

        if ($request->has('status') && $request->status) {
            $query->whereHas('status', function ($q) use ($request) {
                $q->where('name', $request->status);
            });
        }

        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }

        $epics = $query->latest()->get()->map(function ($epic) {
            // Get child tasks (tasks that belong to this epic)
            $childTasks = Task::where('epic_id', $epic->id)->with(['status', 'assignee'])->get();
            $totalTasks = $childTasks->count();
            $completedTasks = $childTasks->filter(function ($task) {
                return $task->status && in_array($task->status->name, [
                    TaskStatus::COMPLETED->value,
                    TaskStatus::DONE->value
                ]);
            })->count();

            return [
                'id' => $epic->id,
                'uuid' => $epic->uuid,
                'key' => $epic->key,
                'title' => $epic->title,
                'description' => $epic->description,
                'status' => $epic->status,
                'status_name' => $epic->status?->name,
                'priority' => $epic->priority,
                'project' => $epic->taskable, // taskable is the project for epics
                'assignee' => $epic->assignee,
                'reporter' => $epic->reporter,
                'due_date' => $epic->due_date,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'progress' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
                'child_tasks' => $childTasks->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'uuid' => $task->uuid,
                        'key' => $task->key,
                        'title' => $task->title,
                        'description' => $task->description,
                        'status' => $task->status?->name,
                        'priority' => $task->priority,
                        'type' => $task->type,
                        'assignee' => $task->assignee,
                    ];
                }),
                'attachments' => $epic->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'file_name' => $attachment->file_name,
                        'file_type' => $attachment->file_type,
                        'mime_type' => $attachment->mime_type,
                        'file_size' => $attachment->file_size,
                        'file_url' => $attachment->file_url,
                        'uploaded_by' => $attachment->user ? $attachment->user->name : 'Unknown',
                        'created_at' => $attachment->created_at->format('d/m/Y H:i'),
                    ];
                }),
            ];
        });

        // Group epics by status
        $epicsByStatus = [
            'todo' => $epics->where('status_name', EpicStatus::TODO->value)->values(),
            'pending' => $epics->where('status_name', EpicStatus::PENDING->value)->values(),
            'in_progress' => $epics->where('status_name', EpicStatus::IN_PROGRESS->value)->values(),
            'under_review' => $epics->where('status_name', EpicStatus::UNDER_REVIEW->value)->values(),
            'completed' => $epics->where('status_name', EpicStatus::COMPLETED->value)->values(),
        ];

        // Get all projects for filter
        $projects = Project::select('id', 'name')->orderBy('name')->get();

        // Get all users for assignee selection
        $users = User::select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name, // Uses the name accessor
                    'email' => $user->email,
                ];
            });

        return Inertia::render('Epics/Index', [
            'epicsByStatus' => $epicsByStatus,
            'projects' => $projects,
            'users' => $users,
            'filters' => $request->only(['project_id', 'status', 'priority']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:lowest,low,medium,high,highest',
            'assignee_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        // Get default status (todo or pending)
        $defaultStatus = \App\Models\Status::where('name', EpicStatus::TODO->value)
            ->orWhere('name', EpicStatus::PENDING->value)
            ->first();

        $validated['type'] = 'epic';
        $validated['status_id'] = $defaultStatus?->id;
        $validated['reporter_id'] = auth()->id();
        $validated['taskable_type'] = 'App\Models\Project';
        $validated['taskable_id'] = $validated['project_id'];
        unset($validated['project_id']);

        // Generate key
        $project = Project::find($validated['taskable_id']);
        $projectKey = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $project->name), 0, 4));

        // Find the highest number for this key prefix across all tasks (including soft deleted ones)
        $existingKeys = Task::withTrashed()
            ->where('key', 'like', $projectKey.'-%')
            ->pluck('key')
            ->map(function ($key) use ($projectKey) {
                // Extract the number after the dash
                $parts = explode('-', $key);
                return isset($parts[1]) ? (int) $parts[1] : 0;
            })
            ->filter();

        $nextNumber = $existingKeys->isEmpty() ? 1 : $existingKeys->max() + 1;
        $validated['key'] = $projectKey.'-'.$nextNumber;

        $epic = Task::create($validated);

        return redirect()->route('epics.index')->with('success', 'Epic créé avec succès.');
    }

    public function update(Request $request, string $epic)
    {
        $epic = Task::where('uuid', $epic)
            ->where('type', 'epic')
            ->firstOrFail();

        // Get all valid epic status enum values
        $validStatuses = collect(EpicStatus::cases())->map(fn($status) => $status->value)->toArray();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:' . implode(',', $validStatuses),
            'priority' => 'required|in:lowest,low,medium,high,highest',
            'assignee_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        // Convert status name to status_id
        if (isset($validated['status'])) {
            $status = \App\Models\Status::where('name', $validated['status'])->first();
            $validated['status_id'] = $status?->id;
            unset($validated['status']);
        }

        $epic->update($validated);

        return redirect()->route('epics.index')->with('success', 'Epic mis à jour avec succès.');
    }

    public function destroy(string $epic)
    {
        $epic = Task::where('uuid', $epic)
            ->where('type', 'epic')
            ->firstOrFail();

        $epic->delete();

        return redirect()->route('epics.index')->with('success', 'Epic supprimé avec succès.');
    }
}
