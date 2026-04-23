<?php

namespace App\Http\Controllers\Agile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agile\StoreUserStoryRequest;
use App\Http\Requests\Agile\UpdateUserStoryRequest;
use App\Http\Resources\Agile\UserStoryResource;
use App\Models\Agile\Epic;
use App\Models\Agile\UserStory;
use App\Models\Sprint;
use App\Models\Status;
use App\Models\User;
use App\Services\Agile\UserStoryCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserStoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', UserStory::class);

        $stories = UserStory::query()
            ->with(['epic', 'sprint', 'assignee'])
            ->withCount(['acceptanceCriteria', 'storyTasks'])
            ->when($request->filled('epic_id'), fn ($q) => $q->where('epic_id', $request->integer('epic_id')))
            ->when($request->filled('sprint_id'), fn ($q) => $q->where('sprint_id', $request->integer('sprint_id')))
            ->when($request->filled('assignee_id'), fn ($q) => $q->where('assignee_id', $request->integer('assignee_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Agile/UserStories/Index', [
            'stories' => UserStoryResource::collection($stories),
            'epics' => Epic::query()->orderBy('title')->get(['id', 'title']),
            'sprints' => Sprint::query()->orderBy('start_date', 'desc')->get(['id', 'name', 'status']),
            'users' => User::query()->where('is_active', true)->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']),
            'filters' => $request->only(['epic_id', 'sprint_id', 'assignee_id', 'status']),
        ]);
    }

    public function show(UserStory $userStory): Response
    {
        $this->authorize('view', $userStory);

        $userStory->load([
            'epic',
            'sprint',
            'assignee',
            'reporter',
            'acceptanceCriteria.validatedBy',
            'acceptanceCriteria.testScenarios',
            'storyTasks',
        ]);

        return Inertia::render('Agile/UserStories/Show', [
            'story' => new UserStoryResource($userStory),
            'sprints' => Sprint::query()
                ->where('project_id', $userStory->epic?->project_id)
                ->orderBy('start_date', 'desc')
                ->get(['id', 'name', 'status', 'start_date', 'end_date']),
            'users' => User::query()->where('is_active', true)->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']),
            'statuses' => Status::query()->orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function store(StoreUserStoryRequest $request): RedirectResponse
    {
        $story = UserStory::create($request->validated());

        return redirect()->route('agile.user-stories.show', $story);
    }

    public function update(UpdateUserStoryRequest $request, UserStory $userStory): RedirectResponse
    {
        $userStory->update($request->validated());

        return redirect()->route('agile.user-stories.show', $userStory);
    }

    public function destroy(UserStory $userStory): RedirectResponse
    {
        $this->authorize('delete', $userStory);
        $userStory->delete();

        return redirect()->route('agile.user-stories.index');
    }

    public function complete(Request $request, UserStory $userStory, UserStoryCompletionService $service): JsonResponse
    {
        $this->authorize('complete', $userStory);

        /** @var \App\Models\User $actor */
        $actor = $request->user();
        $story = $service->complete($userStory, $actor);

        return (new UserStoryResource($story->load('acceptanceCriteria')))
            ->response()
            ->setStatusCode(200);
    }
}
