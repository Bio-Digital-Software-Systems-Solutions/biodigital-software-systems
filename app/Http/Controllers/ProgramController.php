<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProgramController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view programs')->only(['index', 'show']);
        $this->middleware('can:create programs')->only(['create', 'store']);
        $this->middleware('can:edit programs')->only(['edit', 'update']);
        $this->middleware('can:delete programs')->only(['destroy']);
    }

    public function index()
    {
        $programs = Program::with(['user', 'tasks'])
            ->when(request('status'), function ($query, $status): void {
                $query->where('status', $status);
            })
            ->when(request('priority'), function ($query, $priority): void {
                $query->where('priority', $priority);
            })
            ->orderBy('start_date', 'desc')
            ->paginate(10)
            ->appends(request()->query());

        return Inertia::render('Programs/Index', [
            'programs' => [
                'data' => $programs->items(),
                'links' => $programs->linkCollection()->toArray(),
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
                'per_page' => $programs->perPage(),
                'total' => $programs->total(),
                'from' => $programs->firstItem(),
                'to' => $programs->lastItem(),
            ],
            'filters' => request()->only(['status', 'priority']),
        ]);
    }

    public function create()
    {
        $users = User::all();

        return Inertia::render('Programs/Create', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,active,paused,completed,cancelled',
            'priority' => 'required|in:low,medium,high',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        $validated['user_id'] = Auth::id();

        Program::create($validated);

        return redirect()->route('programs.index')
            ->with('success', 'Program created successfully.');
    }

    public function show(Program $program)
    {
        $program->load([
            'user',
            'tasks.status',
            'tasks.assignedUser',
            'steps.tasks.status',
            'steps.tasks.assignedUser',
            'steps.users',
        ]);

        $users = User::all();
        $statuses = \App\Models\Status::all();

        return Inertia::render('Programs/Show', [
            'program' => $program,
            'users' => $users,
            'statuses' => $statuses,
        ]);
    }

    public function edit(Program $program)
    {
        $program->load(['user']);
        $users = User::all();

        return Inertia::render('Programs/Edit', [
            'program' => $program,
            'users' => $users,
        ]);
    }

    public function update(Request $request, Program $program)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,active,paused,completed,cancelled',
            'priority' => 'required|in:low,medium,high',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        $program->update($validated);

        return redirect()->route('programs.index')
            ->with('success', 'Program updated successfully.');
    }

    public function destroy(Program $program)
    {
        $program->delete();

        return redirect()->route('programs.index')
            ->with('success', 'Program deleted successfully.');
    }
}
