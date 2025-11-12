<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view departments')->only(['index', 'show']);
        $this->middleware('can:manage departments')->only(['create', 'store', 'edit', 'update', 'destroy', 'assignUser', 'removeUser']);
    }

    public function index()
    {
        $departments = Department::with(['users', 'headOfDepartment'])
            ->withCount('users')
            ->when(request('status'), function ($query, $status) {
                if ($status === 'active') {
                    $query->active();
                }
            })
            ->ordered()
            ->paginate(10)
            ->appends(request()->query());

        return Inertia::render('Departments/Index', [
            'departments' => [
                'data' => $departments->items(),
                'links' => $departments->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $departments->currentPage(),
                    'last_page' => $departments->lastPage(),
                    'per_page' => $departments->perPage(),
                    'total' => $departments->total(),
                    'from' => $departments->firstItem(),
                    'to' => $departments->lastItem(),
                ],
            ],
            'filters' => request()->only(['status']),
        ]);
    }

    public function create()
    {
        $users = User::all();

        return Inertia::render('Departments/Create', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments',
            'description' => 'nullable|string',
            'head_of_department' => 'nullable|exists:users,id',
            'budget' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'image' => 'nullable',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('departments', 'public');
        }
        // Handle image from TUS upload (just filename)
        elseif ($request->filled('image') && is_string($request->image)) {
            // Image has already been uploaded via TUS to departments directory
            $validated['image'] = 'departments/' . $request->image;
        }

        $department = Department::create($validated);

        return redirect()->route('departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function show(Department $department)
    {
        $department->load(['users', 'headOfDepartment']);

        // Get all users for adding members
        $allUsers = User::select('id', 'first_name', 'last_name', 'email')
            ->whereNotIn('id', $department->users->pluck('id'))
            ->orderBy('first_name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name, // Uses the accessor
                    'email' => $user->email,
                ];
            });

        return Inertia::render('Departments/Show', [
            'department' => [
                'id' => $department->id,
                'uuid' => $department->uuid,
                'name' => $department->name,
                'code' => $department->code,
                'description' => $department->description,
                'budget' => $department->budget,
                'is_active' => $department->is_active,
                'head_of_department' => $department->headOfDepartment ? [
                    'id' => $department->headOfDepartment->id,
                    'name' => $department->headOfDepartment->name,
                    'email' => $department->headOfDepartment->email,
                ] : null,
                'users' => $department->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                }),
                'users_count' => $department->users->count(),
            ],
            'availableUsers' => $allUsers,
            'canManage' => auth()->user()->can('manage departments'),
        ]);
    }

    public function edit(Department $department)
    {
        $department->load(['users']);
        $users = User::all();

        return Inertia::render('Departments/Edit', [
            'department' => $department,
            'users' => $users,
        ]);
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code,'.$department->id,
            'description' => 'nullable|string',
            'head_of_department' => 'nullable|exists:users,id',
            'budget' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'image' => 'nullable',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($department->image) {
                \Storage::disk('public')->delete($department->image);
            }
            $validated['image'] = $request->file('image')->store('departments', 'public');
        }
        // Handle image from TUS upload (just filename)
        elseif ($request->filled('image') && is_string($request->image)) {
            // Delete old image if it exists
            if ($department->image) {
                \Storage::disk('public')->delete($department->image);
            }
            // Image has already been uploaded via TUS to departments directory
            $validated['image'] = 'departments/' . $request->image;
        }

        $department->update($validated);

        return redirect()->route('departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    public function assignUser(Request $request, Department $department)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $department->users()->syncWithoutDetaching([$validated['user_id']]);

        return back()->with('success', 'User assigned to department successfully.');
    }

    public function removeUser(Request $request, Department $department, User $user)
    {
        $department->users()->detach($user->id);

        return back()->with('success', 'User removed from department successfully.');
    }
}
