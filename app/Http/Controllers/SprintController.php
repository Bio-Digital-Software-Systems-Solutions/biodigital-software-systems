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
        $query = Sprint::with(['project', 'tasks.assignee', 'tasks.status', 'attachments.user']);

        // Apply filters
        if ($request->has('project_id') && $request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where(function ($q) use ($request): void {
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

        $sprints = $query->latest('start_date')->get()->map(function ($sprint): array {
            $totalTasks = $sprint->tasks->count();
            $completedTasks = $sprint->tasks->filter(fn ($task): bool => $task->status && $task->status->name === 'completed')->count();

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
                'attachments' => $sprint->attachments->map(fn ($attachment): array => [
                    'id' => $attachment->id,
                    'file_name' => $attachment->file_name,
                    'file_type' => $attachment->file_type,
                    'mime_type' => $attachment->mime_type,
                    'file_size' => $attachment->file_size,
                    'file_url' => $attachment->file_url,
                    'uploaded_by' => $attachment->user ? $attachment->user->name : 'Unknown',
                    'created_at' => $attachment->created_at->format('d/m/Y H:i'),
                ]),
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

        Sprint::create($validated);

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

    /**
     * Agile action endpoint: start the sprint.
     * Refuses if another sprint is already active on the same project.
     */
    public function start(Sprint $sprint, \App\Services\Agile\SprintLifecycleService $service): \Illuminate\Http\JsonResponse
    {
        $this->authorize('start', $sprint);

        $updated = $service->start($sprint);

        return (new \App\Http\Resources\Agile\SprintResource($updated))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Agile action endpoint: close the sprint.
     */
    public function close(Sprint $sprint, \App\Services\Agile\SprintLifecycleService $service): \Illuminate\Http\JsonResponse
    {
        $this->authorize('close', $sprint);

        $updated = $service->close($sprint);

        return (new \App\Http\Resources\Agile\SprintResource($updated))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Agile action endpoint: move a user story into this sprint (or detach it when sprint_id=null).
     * Refuses if the target sprint is completed.
     */
    public function moveStoryToSprint(
        \App\Http\Requests\Agile\MoveUserStoryToSprintRequest $request,
        \App\Models\Agile\UserStory $userStory,
        \App\Services\Agile\SprintLifecycleService $service,
    ): \Illuminate\Http\JsonResponse {
        $target = $request->filled('sprint_id')
            ? Sprint::findOrFail($request->integer('sprint_id'))
            : null;

        $story = $service->moveStoryToSprint($userStory, $target);

        return (new \App\Http\Resources\Agile\UserStoryResource($story))
            ->response()
            ->setStatusCode(200);
    }
}
