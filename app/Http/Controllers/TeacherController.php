<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $teachers = Teacher::with('user')
            ->latest()
            ->paginate(10);

        return Inertia::render('Teachers/Index', [
            'teachers' => $teachers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $users = User::whereDoesntHave('teacher')
            ->select('id', 'name', 'email')
            ->get();

        return Inertia::render('Teachers/Create', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Teacher::create($validated);

        return redirect()->route('teachers.index')
            ->with('message', 'Teacher created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Teacher $teacher): Response
    {
        $teacher->load(['user', 'trainings', 'classes']);

        return Inertia::render('Teachers/Show', [
            'teacher' => $teacher,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Teacher $teacher): Response
    {
        $teacher->load('user');

        $users = User::whereDoesntHave('teacher')
            ->orWhere('id', $teacher->user_id)
            ->select('id', 'name', 'email')
            ->get();

        return Inertia::render('Teachers/Edit', [
            'teacher' => $teacher,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        $validated = $request->validated();

        $teacher->update($validated);

        return redirect()->route('teachers.index')
            ->with('message', 'Teacher updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Teacher $teacher): RedirectResponse
    {
        $teacher->delete();

        return redirect()->route('teachers.index')
            ->with('message', 'Teacher deleted successfully.');
    }
}
