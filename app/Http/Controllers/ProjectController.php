<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view programs')->only(['index', 'show', 'board', 'list', 'gantt']);
        $this->middleware('can:create programs')->only(['create', 'store']);
        $this->middleware('can:edit programs')->only(['edit', 'update']);
        $this->middleware('can:delete programs')->only(['destroy']);
    }

    public function index()
    {
        $projects = Project::with(['manager', 'members'])
            ->withCount([
                'tasks',
                'members',
                'tasks as completed_tasks_count' => function ($query) {
                    $query->where('status', 'done');
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
            'completed_tasks' => ProjectTask::whereIn('project_id', $projectIds)
                ->where('status', 'done')
                ->count(),
            'in_progress_tasks' => ProjectTask::whereIn('project_id', $projectIds)
                ->where('status', 'in_progress')
                ->count(),
            'overdue_tasks' => ProjectTask::whereIn('project_id', $projectIds)
                ->where('due_date', '<', now())
                ->whereNotIn('status', ['done', 'cancelled'])
                ->count(),
            'total_epics' => ProjectTask::whereIn('project_id', $projectIds)
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
                    $query->where('status', 'done');
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

        return Inertia::render('Projects/Dashboard', [
            'projects' => $projects,
            'stats' => $stats,
            'recentProjects' => $recentProjects,
        ]);
    }

    public function list()
    {
        $projects = Project::with(['manager', 'members'])
            ->withCount([
                'tasks',
                'members',
                'tasks as completed_tasks_count' => function ($query) {
                    $query->where('status', 'done');
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
        return Inertia::render('Projects/Create');
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
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'image' => 'nullable',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('projects', 'public');
        }
        // Handle image from TUS upload (just filename)
        elseif ($request->filled('image') && is_string($request->image)) {
            // Image has already been uploaded via TUS to projects directory
            $validated['image'] = 'projects/' . $request->image;
        }

        $validated['slug'] = Str::slug($validated['name']);
        $validated['project_manager_id'] = $request->user()->id;

        $project = Project::create($validated);

        $project->members()->attach($request->user()->id, [
            'is_lead' => true,
            'started_at' => now(),
        ]);

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
        $completedTasks = $project->tasks()->where('status', 'done')->count();
        $totalTasks = $project->tasks()->count();
        $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        return Inertia::render('Projects/Show', [
            'project' => array_merge($project->toArray(), [
                'progress' => $progress,
                'tasks_count' => $totalTasks,
                'comments' => $comments,
            ]),
            'users' => $users,
        ]);
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
            $validated['image'] = 'projects/' . $request->image;
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

    public function board(Project $project)
    {
        $project->load(['tasks.assignee', 'tasks.reporter']);

        return Inertia::render('Projects/Board', [
            'project' => $project,
        ]);
    }

    public function gantt(Project $project)
    {
        $project->load(['tasks.assignee', 'tasks.reporter']);

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
