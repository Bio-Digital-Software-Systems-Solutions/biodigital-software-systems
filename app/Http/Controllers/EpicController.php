<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EpicController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view programs');
    }

    public function index(Request $request)
    {
        $query = ProjectTask::with(['project', 'assignee', 'reporter', 'attachments.uploader'])
            ->where('type', 'epic');

        // Apply filters
        if ($request->has('project_id') && $request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }

        $epics = $query->latest()->get()->map(function ($epic) {
            // Get child tasks (tasks that belong to this epic)
            $childTasks = ProjectTask::where('epic_id', $epic->id)->get();
            $totalTasks = $childTasks->count();
            $completedTasks = $childTasks->where('status', 'done')->count();

            return [
                'id' => $epic->id,
                'key' => $epic->key,
                'title' => $epic->title,
                'description' => $epic->description,
                'status' => $epic->status,
                'priority' => $epic->priority,
                'project' => $epic->project,
                'assignee' => $epic->assignee,
                'reporter' => $epic->reporter,
                'due_date' => $epic->due_date,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'progress' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
                'child_tasks' => $childTasks,
                'attachments' => $epic->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'name' => $attachment->name,
                        'file_type' => $attachment->file_type,
                        'mime_type' => $attachment->mime_type,
                        'file_size' => $attachment->file_size,
                        'human_file_size' => $attachment->human_file_size,
                        'file_path' => asset('storage/'.$attachment->file_path),
                        'download_url' => route('attachments.download', $attachment),
                        'uploaded_by' => $attachment->uploader->name,
                        'created_at' => $attachment->created_at->format('d/m/Y H:i'),
                    ];
                }),
            ];
        });

        // Group epics by status
        $epicsByStatus = [
            'todo' => $epics->where('status', 'todo')->values(),
            'in_progress' => $epics->where('status', 'in_progress')->values(),
            'in_review' => $epics->where('status', 'in_review')->values(),
            'done' => $epics->where('status', 'done')->values(),
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

        $validated['type'] = 'epic';
        $validated['status'] = 'todo';
        $validated['reporter_id'] = auth()->id();

        // Generate key
        $project = Project::find($validated['project_id']);
        $projectKey = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $project->name), 0, 4));

        // Find the highest number for this key prefix across all tasks (not just epics)
        $lastTask = ProjectTask::where('key', 'like', $projectKey.'-%')
            ->orderByRaw('CAST(SUBSTR(key, '.(strlen($projectKey) + 2).') AS INTEGER) DESC')
            ->first();

        $nextNumber = $lastTask ? ((int) substr($lastTask->key, strpos($lastTask->key, '-') + 1)) + 1 : 1;
        $validated['key'] = $projectKey.'-'.$nextNumber;

        $epic = ProjectTask::create($validated);

        return redirect()->route('epics.index')->with('success', 'Epic créé avec succès.');
    }

    public function update(Request $request, ProjectTask $epic)
    {
        if ($epic->type !== 'epic') {
            abort(404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:todo,in_progress,in_review,done',
            'priority' => 'required|in:lowest,low,medium,high,highest',
            'assignee_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        $epic->update($validated);

        return redirect()->route('epics.index')->with('success', 'Epic mis à jour avec succès.');
    }

    public function destroy(ProjectTask $epic)
    {
        if ($epic->type !== 'epic') {
            abort(404);
        }

        $epic->delete();

        return redirect()->route('epics.index')->with('success', 'Epic supprimé avec succès.');
    }
}
