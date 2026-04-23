<?php

namespace App\Http\Controllers\Agile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agile\StoreEpicRequest;
use App\Http\Requests\Agile\UpdateEpicRequest;
use App\Http\Resources\Agile\EpicResource;
use App\Models\Agile\Epic;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EpicController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Epic::class);

        $epics = Epic::query()
            ->with(['owner'])
            ->withCount('userStories')
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->integer('project_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('owner_id'), fn ($q) => $q->where('owner_id', $request->integer('owner_id')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Agile/Epics/Index', [
            'epics' => EpicResource::collection($epics),
            'projects' => Project::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'users' => User::query()
                ->where('is_active', true)
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'email']),
            'filters' => $request->only(['project_id', 'status', 'owner_id']),
        ]);
    }

    public function show(Epic $epic): Response
    {
        $this->authorize('view', $epic);

        $epic->load(['owner', 'userStories.acceptanceCriteria']);

        return Inertia::render('Agile/Epics/Show', [
            'epic' => new EpicResource($epic),
        ]);
    }

    public function store(StoreEpicRequest $request): RedirectResponse
    {
        $epic = Epic::create($request->validated());

        return redirect()->route('agile.epics.show', $epic);
    }

    public function update(UpdateEpicRequest $request, Epic $epic): RedirectResponse
    {
        $epic->update($request->validated());

        return redirect()->route('agile.epics.show', $epic);
    }

    public function destroy(Epic $epic): RedirectResponse
    {
        $this->authorize('delete', $epic);
        $epic->delete();

        return redirect()->route('agile.epics.index');
    }
}
