<?php

namespace App\Http\Controllers;

use App\Enums\Employee\EmployeeStatus;
use App\Enums\Star\StarStatus;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Star;
use App\Models\User;
use App\Services\ProjectStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view projects')->only(['index', 'show', 'board', 'list', 'gantt']);
        $this->middleware('can:create projects')->only(['create', 'store']);
        $this->middleware('can:edit projects')->only(['edit', 'update']);
        $this->middleware('can:delete projects')->only(['destroy']);
    }

    public function index()
    {
        $projects = Project::with(['manager', 'members'])
            ->withCount([
                'tasks',
                'members',
                'tasks as completed_tasks_count' => function ($query) {
                    $query->whereHas('status', function ($q) {
                        $q->where('name', 'completed');
                    });
                },
            ])
            ->get();

        // Calculate statistics
        $projectIds = $projects->pluck('id');

        $stats = [
            'total_projects' => $projects->count(),
            'active_projects' => $projects->where('status', 'active')->count(),
            'completed_projects' => $projects->where('status', 'completed')->count(),
            'on_hold_projects' => $projects->where('status', 'on_hold')->count(),
            'total_tasks' => $projects->sum('tasks_count'),
            'completed_tasks' => \App\Models\Task::where('taskable_type', 'App\Models\Project')
                ->whereIn('taskable_id', $projectIds)
                ->whereHas('status', function ($q) {
                    $q->where('name', 'completed');
                })
                ->count(),
            'in_progress_tasks' => \App\Models\Task::where('taskable_type', 'App\Models\Project')
                ->whereIn('taskable_id', $projectIds)
                ->whereHas('status', function ($q) {
                    $q->where('name', 'in_progress');
                })
                ->count(),
            'overdue_tasks' => \App\Models\Task::where('taskable_type', 'App\Models\Project')
                ->whereIn('taskable_id', $projectIds)
                ->where('due_date', '<', now())
                ->whereHas('status', function ($q) {
                    $q->whereNotIn('name', ['completed', 'cancelled']);
                })
                ->count(),
            'total_epics' => \App\Models\Task::where('taskable_type', 'App\Models\Project')
                ->whereIn('taskable_id', $projectIds)
                ->where('type', 'epic')
                ->count(),
            'active_sprints' => \App\Models\Sprint::whereIn('project_id', $projectIds)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->count(),
            'upcoming_sprints' => \App\Models\Sprint::whereIn('project_id', $projectIds)
                ->where('start_date', '>', now())
                ->count(),
        ];

        // Get recent projects
        $recentProjects = Project::with(['manager', 'members'])
            ->withCount([
                'tasks',
                'members',
                'tasks as completed_tasks_count' => function ($query) {
                    $query->whereHas('status', function ($q) {
                        $q->where('name', 'completed');
                    });
                },
            ])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($project) {
                $project->progress = $project->tasks_count > 0
                    ? round(($project->completed_tasks_count / $project->tasks_count) * 100)
                    : 0;

                return $project;
            });

        $analyticsStats = (new ProjectStatisticsService)->getGlobalStatistics();

        return Inertia::render('Projects/Dashboard', [
            'projects' => $projects,
            'stats' => $stats,
            'recentProjects' => $recentProjects,
            'analyticsStats' => $analyticsStats,
        ]);
    }

    public function list()
    {
        $projects = Project::with(['manager', 'members'])
            ->withCount([
                'tasks',
                'members',
                'tasks as completed_tasks_count' => function ($query) {
                    $query->whereHas('status', function ($q) {
                        $q->where('name', 'completed');
                    });
                },
            ])
            ->when(request('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when(request('sort_by'), function ($query) {
                $sortBy = request('sort_by');
                $direction = request('sort_direction', 'asc');

                if ($sortBy === 'name') {
                    $query->orderBy('projects.name', $direction);
                } elseif ($sortBy === 'status') {
                    $query->orderBy('projects.status', $direction);
                } elseif ($sortBy === 'tasks_count') {
                    $query->orderBy('tasks_count', $direction);
                } elseif ($sortBy === 'manager') {
                    $query->leftJoin('users', 'projects.project_manager_id', '=', 'users.id')
                        ->orderBy('users.first_name', $direction)
                        ->select('projects.*');
                }
            }, function ($query) {
                // Default sorting when no sort specified
                $query->latest();
            })
            ->get()
            ->map(function ($project) {
                $project->progress = $project->tasks_count > 0
                    ? round(($project->completed_tasks_count / $project->tasks_count) * 100)
                    : 0;

                return $project;
            });

        // Sort by progress if requested (needs to be done after calculation)
        if (request('sort_by') === 'progress') {
            $direction = request('sort_direction', 'asc');
            $projects = $direction === 'asc'
                ? $projects->sortBy('progress')->values()
                : $projects->sortByDesc('progress')->values();
        }

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'filters' => request()->only(['status', 'sort_by', 'sort_direction']),
        ]);
    }

    public function create()
    {
        // Get all users
        $users = User::all()->map(fn ($user) => [
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
            ->map(fn ($employee) => [
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
            ->map(fn ($star) => [
                'id' => $star->user_id,
                'uuid' => $star->uuid,
                'first_name' => $star->user->first_name ?? '',
                'last_name' => $star->user->last_name ?? '',
                'email' => $star->user->email ?? '',
                'title' => $star->title,
                'type' => 'star',
            ]);

        return Inertia::render('Projects/Create', [
            'users' => $users,
            'employees' => $employees,
            'stars' => $stars,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:planning,active,on_hold,completed,cancelled',
            'priority' => 'required|in:lowest,low,medium,high,highest',
            'color' => 'nullable|string|max:7',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'image' => 'nullable',
            'project_manager_id' => 'nullable|exists:users,id',
            'participants' => 'nullable|array',
            'participants.*' => 'exists:users,id',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('projects', 'public');
        }
        // Handle image from TUS upload (just filename)
        elseif ($request->filled('image') && is_string($request->image)) {
            // Image has already been uploaded via TUS to projects directory
            $validated['image'] = 'projects/'.$request->image;
        }

        $validated['slug'] = Str::slug($validated['name']);

        // Set project manager (default to current user if not specified)
        if (empty($validated['project_manager_id'])) {
            $validated['project_manager_id'] = $request->user()->id;
        }

        // Extract participants before creating project
        $participants = $validated['participants'] ?? [];
        unset($validated['participants']);

        $project = Project::create($validated);

        // Create participants in project_participants table
        foreach ($participants as $participantId) {
            if ($participantId != $validated['project_manager_id']) {
                \App\Models\ProjectParticipant::create([
                    'project_id' => $project->id,
                    'user_id' => $participantId,
                    'role' => 'member',
                ]);
            }
        }

        return redirect()->route('projects.show', $project->uuid)->with('success', 'Projet créé avec succès.');
    }

    public function show(Project $project)
    {
        $project->load([
            'manager',
            'reviewer',
            'members',
            'tasks.assignee',
            'tasks.reporter',
            'tasks.status',
            'participants.user',
            'attachments.user',
        ]);

        // Load comments with their replies (only top-level comments)
        $comments = $project->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies'])
            ->latest()
            ->get();

        // Get all users for participant selection
        $users = \App\Models\User::select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->get();

        // Calculate progress
        $completedTasks = $project->tasks()
            ->whereHas('status', function ($query) {
                $query->where('name', 'completed');
            })
            ->count();
        $totalTasks = $project->tasks()->count();
        $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        // Load activity logs for the project and related models
        $activities = $this->getProjectActivities($project);

        $projectStatistics = (new ProjectStatisticsService)->getProjectStatistics($project);

        return Inertia::render('Projects/Show', [
            'project' => array_merge($project->toArray(), [
                'progress' => $progress,
                'tasks_count' => $totalTasks,
                'comments' => $comments,
            ]),
            'users' => $users,
            'activities' => $activities,
            'projectStatistics' => $projectStatistics,
        ]);
    }

    /**
     * Get all activities for a project including related models.
     */
    private function getProjectActivities(Project $project): \Illuminate\Support\Collection
    {
        // Get project's own activities
        $projectActivities = $project->activities()
            ->with('causer')
            ->get()
            ->map(fn ($a) => $this->formatActivity($a, 'project'));

        // Get participant activities
        $participantIds = $project->participants()->pluck('id');
        $participantActivities = \Spatie\Activitylog\Models\Activity::query()
            ->where('subject_type', \App\Models\ProjectParticipant::class)
            ->whereIn('subject_id', $participantIds)
            ->with('causer')
            ->get()
            ->map(fn ($a) => $this->formatActivity($a, 'participant'));

        // Get attachment activities
        $attachmentIds = $project->attachments()->pluck('id');
        $attachmentActivities = \Spatie\Activitylog\Models\Activity::query()
            ->where('subject_type', \App\Models\ProjectAttachment::class)
            ->whereIn('subject_id', $attachmentIds)
            ->with('causer')
            ->get()
            ->map(fn ($a) => $this->formatActivity($a, 'attachment'));

        // Get task activities (only created/deleted for project tasks)
        $taskIds = $project->tasks()->pluck('id');
        $taskActivities = \Spatie\Activitylog\Models\Activity::query()
            ->where('subject_type', \App\Models\Task::class)
            ->whereIn('subject_id', $taskIds)
            ->whereIn('description', ['created', 'deleted'])
            ->with('causer')
            ->get()
            ->map(fn ($a) => $this->formatActivity($a, 'task'));

        // Merge and sort by date
        return $projectActivities
            ->merge($participantActivities)
            ->merge($attachmentActivities)
            ->merge($taskActivities)
            ->sortByDesc('created_at')
            ->values()
            ->take(50); // Limit to last 50 activities
    }

    /**
     * Format an activity for the frontend.
     */
    private function formatActivity($activity, string $type): array
    {
        $properties = $activity->properties->toArray();
        $description = $this->getActivityDescription($activity, $type, $properties);

        return [
            'id' => $activity->id,
            'type' => $type,
            'event' => $activity->description,
            'description' => $description,
            'properties' => $properties,
            'causer' => $activity->causer ? [
                'id' => $activity->causer->id,
                'first_name' => $activity->causer->first_name,
                'last_name' => $activity->causer->last_name,
            ] : null,
            'created_at' => $activity->created_at->toISOString(),
        ];
    }

    /**
     * Get human-readable description for an activity.
     */
    private function getActivityDescription($activity, string $type, array $properties): string
    {
        $event = $activity->description;

        return match ($type) {
            'project' => match ($event) {
                'created' => 'Projet créé',
                'updated' => $this->getProjectUpdateDescription($properties),
                'deleted' => 'Projet supprimé',
                default => 'Projet modifié',
            },
            'participant' => match ($event) {
                'created' => 'Participant ajouté',
                'deleted' => 'Participant retiré',
                default => 'Participant modifié',
            },
            'attachment' => match ($event) {
                'created' => 'Document ajouté: '.($properties['attributes']['file_name'] ?? 'fichier'),
                'deleted' => 'Document supprimé',
                default => 'Document modifié',
            },
            'task' => match ($event) {
                'created' => 'Tâche ajoutée: '.($properties['attributes']['title'] ?? 'tâche'),
                'deleted' => 'Tâche supprimée',
                default => 'Tâche modifiée',
            },
            default => 'Modification',
        };
    }

    /**
     * Get description for project update based on changed fields.
     */
    private function getProjectUpdateDescription(array $properties): string
    {
        $attributes = $properties['attributes'] ?? [];
        $old = $properties['old'] ?? [];

        if (isset($attributes['status']) && isset($old['status'])) {
            return "Statut changé de {$old['status']} à {$attributes['status']}";
        }

        if (isset($attributes['priority'])) {
            return 'Priorité modifiée';
        }

        if (isset($attributes['name'])) {
            return 'Nom du projet modifié';
        }

        if (isset($attributes['description'])) {
            return 'Description modifiée';
        }

        if (isset($attributes['start_date']) || isset($attributes['end_date'])) {
            return 'Dates du projet modifiées';
        }

        if (isset($attributes['budget'])) {
            return 'Budget modifié';
        }

        if (isset($attributes['project_manager_id'])) {
            return 'Chef de projet modifié';
        }

        if (isset($attributes['reviewer_id'])) {
            return 'Réviseur modifié';
        }

        return 'Projet mis à jour';
    }

    public function edit(Project $project)
    {
        return Inertia::render('Projects/Edit', [
            'project' => $project,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:planning,active,on_hold,completed,cancelled',
            'priority' => 'required|in:lowest,low,medium,high,highest',
            'color' => 'nullable|string|max:7',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'image' => 'nullable',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($project->image) {
                \Storage::disk('public')->delete($project->image);
            }
            $validated['image'] = $request->file('image')->store('projects', 'public');
        }
        // Handle image from TUS upload (just filename)
        elseif ($request->filled('image') && is_string($request->image)) {
            // Delete old image if it exists
            if ($project->image) {
                \Storage::disk('public')->delete($project->image);
            }
            // Image has already been uploaded via TUS to projects directory
            $validated['image'] = 'projects/'.$request->image;
        }

        if ($validated['name'] !== $project->name) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $project->update($validated);

        return redirect()->route('projects.show', $project->uuid)->with('success', 'Projet mis à jour avec succès.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Projet supprimé avec succès.');
    }

    public function board(Request $request, Project $project)
    {
        $query = $project->tasks()->with(['assignee', 'reporter', 'status', 'sprint']);

        // Apply filters
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

        // Group tasks by status name (convert to array for JavaScript)
        $tasksByStatus = [
            'pending' => $tasks->filter(fn ($task) => $task->status && in_array($task->status->name, ['pending', 'new']))
                ->values()->map(fn ($task) => $this->formatTaskForKanban($task))->toArray(),
            'todo' => $tasks->filter(fn ($task) => $task->status && $task->status->name === 'todo')
                ->values()->map(fn ($task) => $this->formatTaskForKanban($task))->toArray(),
            'in_progress' => $tasks->filter(fn ($task) => $task->status && $task->status->name === 'in_progress')
                ->values()->map(fn ($task) => $this->formatTaskForKanban($task))->toArray(),
            'under_review' => $tasks->filter(fn ($task) => $task->status && $task->status->name === 'under_review')
                ->values()->map(fn ($task) => $this->formatTaskForKanban($task))->toArray(),
            'blocked' => $tasks->filter(fn ($task) => $task->status && $task->status->name === 'blocked')
                ->values()->map(fn ($task) => $this->formatTaskForKanban($task))->toArray(),
            'completed' => $tasks->filter(fn ($task) => $task->status && $task->status->name === 'completed')
                ->values()->map(fn ($task) => $this->formatTaskForKanban($task))->toArray(),
        ];

        // Get filter data
        $users = \App\Models\User::select('id', 'first_name', 'last_name')->orderBy('first_name')->get();
        $sprints = \App\Models\Sprint::where('project_id', $project->id)
            ->select('id', 'name', 'start_date', 'end_date')
            ->orderBy('start_date')
            ->get();

        return Inertia::render('Projects/Board', [
            'project' => [
                'id' => $project->id,
                'uuid' => $project->uuid,
                'name' => $project->name,
                'color' => $project->color,
            ],
            'tasksByStatus' => $tasksByStatus,
            'users' => $users,
            'sprints' => $sprints,
            'filters' => $request->only(['assignee_id', 'priority', 'type', 'sprint_id', 'search']),
        ]);
    }

    private function formatTaskForKanban(\App\Models\Task $task): array
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
            'due_date' => $task->due_date ? $task->due_date->toDateString() : null,
        ];
    }

    public function gantt(Project $project)
    {
        $project->load(['tasks.assignee', 'tasks.reporter', 'tasks.status']);

        return Inertia::render('Projects/Gantt', [
            'project' => $project,
        ]);
    }

    public function uploadAttachment(Request $request, Project $project)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max
        ]);

        $file = $request->file('file');
        $path = $file->store('projects/attachments', 'public');

        $attachment = $project->attachments()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'file_url' => \Storage::disk('public')->url($path),
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'File uploaded successfully',
            'attachment' => $attachment->load('user'),
        ], 201);
    }

    public function deleteAttachment(Project $project, $attachmentId)
    {
        $attachment = $project->attachments()->findOrFail($attachmentId);

        // Delete file from storage
        if (\Storage::disk('public')->exists($attachment->file_path)) {
            \Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json([
            'message' => 'Attachment deleted successfully',
        ]);
    }
}
