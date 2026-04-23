<?php

namespace App\Http\Controllers\Agile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agile\StoreStoryTaskRequest;
use App\Http\Requests\Agile\UpdateStoryTaskRequest;
use App\Http\Resources\Agile\StoryTaskResource;
use App\Models\Agile\UserStory;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StoryTaskController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function index(UserStory $userStory): Response
    {
        $this->authorize('viewAny', Task::class);

        $tasks = $userStory->storyTasks()->with('assignee')->latest()->get();

        return Inertia::render('Agile/StoryTasks/Index', [
            'user_story_id' => $userStory->id,
            'story_tasks' => StoryTaskResource::collection($tasks),
        ]);
    }

    public function show(Task $storyTask): Response
    {
        $this->authorize('view', $storyTask);

        return Inertia::render('Agile/StoryTasks/Show', [
            'story_task' => new StoryTaskResource($storyTask),
        ]);
    }

    public function store(StoreStoryTaskRequest $request, UserStory $userStory): RedirectResponse
    {
        $data = $request->validated();

        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'work_type' => $data['work_type'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? null,
            'status_id' => $data['status_id'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'taskable_type' => UserStory::class,
            'taskable_id' => $userStory->id,
            'type' => 'task',
            'reporter_id' => $request->user()->id,
        ]);

        return redirect()->route('agile.user-stories.story-tasks.index', $userStory);
    }

    public function update(UpdateStoryTaskRequest $request, Task $storyTask): RedirectResponse
    {
        $storyTask->update($request->validated());

        return redirect()->route('agile.story-tasks.show', $storyTask);
    }

    public function destroy(Task $storyTask): RedirectResponse
    {
        $this->authorize('delete', $storyTask);
        $storyTask->delete();

        return redirect()->back();
    }
}
