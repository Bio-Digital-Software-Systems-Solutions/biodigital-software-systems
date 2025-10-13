<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SprintController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view programs');
    }

    public function index(Request $request)
    {
        $query = Sprint::with(['project', 'tasks.assignee', 'attachments.uploader']);

        // Apply filters
        if ($request->has('project_id') && $request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where(function ($q) use ($request) {
                $now = now();
                switch ($request->status) {
                    case 'active':
                        $q->where('start_date', '<=', $now)
                            ->where('end_date', '>=', $now);
                        break;
                    case 'upcoming':
                        $q->where('start_date', '>', $now);
                        break;
                    case 'completed':
                        $q->where('end_date', '<', $now);
                        break;
                }
            });
        }

        $sprints = $query->latest('start_date')->get()->map(function ($sprint) {
            $totalTasks = $sprint->tasks->count();
            $completedTasks = $sprint->tasks->where('status', 'done')->count();

            $now = now();
            $status = 'upcoming';
            if ($sprint->start_date <= $now && $sprint->end_date >= $now) {
                $status = 'active';
            } elseif ($sprint->end_date < $now) {
                $status = 'completed';
            }

            return [
                'id' => $sprint->id,
                'uuid' => $sprint->uuid,
                'name' => $sprint->name,
                'goal' => $sprint->goal,
                'start_date' => $sprint->start_date,
                'end_date' => $sprint->end_date,
                'project' => $sprint->project,
                'status' => $status,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'progress' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
                'tasks' => $sprint->tasks,
                'attachments' => $sprint->attachments->map(function ($attachment) {
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

        // Group sprints by status
        $sprintsByStatus = [
            'active' => $sprints->where('status', 'active')->values(),
            'upcoming' => $sprints->where('status', 'upcoming')->values(),
            'completed' => $sprints->where('status', 'completed')->values(),
        ];

        // Get all projects for filter
        $projects = Project::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Sprints/Index', [
            'sprintsByStatus' => $sprintsByStatus,
            'projects' => $projects,
            'filters' => $request->only(['project_id', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'goal' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $sprint = Sprint::create($validated);

        return redirect()->route('sprints.index')->with('success', 'Sprint créé avec succès.');
    }

    public function update(Request $request, Sprint $sprint)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'goal' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $sprint->update($validated);

        return redirect()->route('sprints.index')->with('success', 'Sprint mis à jour avec succès.');
    }

    public function destroy(Sprint $sprint)
    {
        $sprint->delete();

        return redirect()->route('sprints.index')->with('success', 'Sprint supprimé avec succès.');
    }
}
