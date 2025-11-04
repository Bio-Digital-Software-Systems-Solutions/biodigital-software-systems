<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
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
        $students = Student::with('user')
            ->latest()
            ->paginate(10);

        return Inertia::render('Students/Index', [
            'students' => $students,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $users = User::whereDoesntHave('student')
            ->select('id', 'first_name', 'last_name', 'email')
            ->get();

        return Inertia::render('Students/Create', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Student::create($validated);

        return redirect()->route('students.index')
            ->with('message', 'Student created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student): Response
    {
        $student->load(['user', 'trainings']);

        return Inertia::render('Students/Show', [
            'student' => $student,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student): Response
    {
        $student->load('user');

        $users = User::whereDoesntHave('student')
            ->orWhere('id', $student->user_id)
            ->select('id', 'first_name', 'last_name', 'email')
            ->get();

        return Inertia::render('Students/Edit', [
            'student' => $student,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $validated = $request->validated();

        $student->update($validated);

        return redirect()->route('students.index')
            ->with('message', 'Student updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return redirect()->route('students.index')
            ->with('message', 'Student deleted successfully.');
    }
}
