<?php

namespace App\Http\Controllers;

use App\Enums\Employee\EmployeeStatus;
use App\Enums\Star\StarStatus;
use App\Models\Department;
use App\Models\DepartmentDocument;
use App\Models\DepartmentForm;
use App\Models\DepartmentNeed;
use App\Models\DepartmentWorkflow;
use App\Models\Employee;
use App\Models\Star;
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
        $departments = Department::with(['headOfDepartment'])
            ->withCount('users')
            ->when(request('status'), function ($query, $status) {
                if ($status === 'active') {
                    $query->active();
                }
            })
            ->ordered()
            ->paginate(10)
            ->appends(request()->query());

        // Map departments to include head_of_department_user for frontend compatibility
        $data = collect($departments->items())->map(function ($department) {
            return [
                'id' => $department->id,
                'uuid' => $department->uuid,
                'name' => $department->name,
                'code' => $department->code,
                'description' => $department->description,
                'budget' => $department->budget,
                'is_active' => $department->is_active,
                'head_of_department' => $department->head_of_department,
                'head_of_department_user' => $department->relationLoaded('headOfDepartment') && $department->headOfDepartment ? [
                    'id' => $department->headOfDepartment->id,
                    'first_name' => $department->headOfDepartment->first_name,
                    'last_name' => $department->headOfDepartment->last_name,
                ] : null,
                'users_count' => $department->users_count,
                'created_at' => $department->created_at,
                'updated_at' => $department->updated_at,
            ];
        })->all();

        return Inertia::render('Departments/Index', [
            'departments' => [
                'data' => $data,
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

        // Get IDs of users already in the department
        $existingUserIds = $department->users->pluck('id')->toArray();

        // Get all users for adding members
        $allUsers = User::select('id', 'uuid', 'first_name', 'last_name', 'email')
            ->whereNotIn('id', $existingUserIds)
            ->orderBy('first_name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'type' => 'user',
                ];
            });

        // Get active employees not already in the department
        $employees = Employee::with('user')
            ->where('status', EmployeeStatus::ACTIVE)
            ->whereNotIn('user_id', $existingUserIds)
            ->get()
            ->map(fn($employee) => [
                'id' => $employee->user_id,
                'uuid' => $employee->uuid,
                'name' => $employee->user->name ?? '',
                'email' => $employee->user->email ?? '',
                'position' => $employee->position,
                'type' => 'employee',
            ]);

        // Get active stars not already in the department
        $stars = Star::with('user')
            ->where('status', StarStatus::ACTIVE)
            ->whereNotIn('user_id', $existingUserIds)
            ->get()
            ->map(fn($star) => [
                'id' => $star->user_id,
                'uuid' => $star->uuid,
                'name' => $star->user->name ?? '',
                'email' => $star->user->email ?? '',
                'title' => $star->title,
                'type' => 'star',
            ]);

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
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                }),
                'users_count' => $department->users->count(),
            ],
            'availableUsers' => $allUsers,
            'availableEmployees' => $employees,
            'availableStars' => $stars,
            'canManage' => auth()->user()->can('manage departments'),
            'workflows' => DepartmentWorkflow::where('department_id', $department->id)
                ->withCount(['steps', 'instances'])
                ->orderBy('created_at', 'desc')
                ->get(),
            'forms' => DepartmentForm::where('department_id', $department->id)
                ->withCount(['fields', 'submissions'])
                ->orderBy('created_at', 'desc')
                ->get(),
            'needs' => DepartmentNeed::where('department_id', $department->id)
                ->with(['requester'])
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get(),
            'appointments' => $department->appointments()
                ->with(['organizer:id,uuid,first_name,last_name,email', 'participants:id,uuid,first_name,last_name,email'])
                ->withCount('participants')
                ->orderBy('start_datetime')
                ->get()
                ->map(function ($appointment) {
                    return [
                        'uuid' => $appointment->uuid,
                        'title' => $appointment->title,
                        'description' => $appointment->description,
                        'type' => $appointment->type,
                        'status' => $appointment->status,
                        'start_datetime' => $appointment->start_datetime->toISOString(),
                        'end_datetime' => $appointment->end_datetime->toISOString(),
                        'location' => $appointment->location,
                        'formatted_time_range' => $appointment->formatted_time_range,
                        'participants_count' => $appointment->participants_count,
                        'organizer' => $appointment->organizer ? [
                            'id' => $appointment->organizer->id,
                            'uuid' => $appointment->organizer->uuid,
                            'name' => $appointment->organizer->name,
                        ] : null,
                    ];
                }),
            'meetings' => $department->meetings()
                ->with([
                    'appointment' => function ($query) {
                        $query->with(['organizer:id,uuid,first_name,last_name,email', 'participants:id,uuid,first_name,last_name,email'])
                              ->withCount('participants');
                    },
                    'creator:id,uuid,first_name,last_name,email'
                ])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($meeting) {
                    $appointment = $meeting->appointment;
                    return [
                        'uuid' => $meeting->uuid,
                        'is_mandatory' => $meeting->is_mandatory,
                        'notify_all_members' => $meeting->notify_all_members,
                        'notes' => $meeting->notes,
                        'notified_at' => $meeting->notified_at?->toISOString(),
                        'created_at' => $meeting->created_at->toISOString(),
                        'creator' => $meeting->creator ? [
                            'id' => $meeting->creator->id,
                            'uuid' => $meeting->creator->uuid,
                            'name' => $meeting->creator->name,
                        ] : null,
                        'appointment' => $appointment ? [
                            'uuid' => $appointment->uuid,
                            'title' => $appointment->title,
                            'description' => $appointment->description,
                            'type' => $appointment->type,
                            'status' => $appointment->status,
                            'start_datetime' => $appointment->start_datetime->format('Y-m-d\TH:i:s'),
                            'end_datetime' => $appointment->end_datetime->format('Y-m-d\TH:i:s'),
                            'location' => $appointment->location,
                            'formatted_time_range' => $appointment->formatted_time_range,
                            'participants_count' => $appointment->participants_count,
                            'organizer' => $appointment->organizer ? [
                                'id' => $appointment->organizer->id,
                                'uuid' => $appointment->organizer->uuid,
                                'name' => $appointment->organizer->name,
                            ] : null,
                        ] : null,
                    ];
                }),
            'documentsTree' => DepartmentDocument::getTreeForDepartment($department->id),
            'documentsCount' => $department->documents()->count(),
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
