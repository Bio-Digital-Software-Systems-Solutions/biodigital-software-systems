<?php

namespace App\Http\Controllers;

use App\Enums\Employee\EmployeeStatus;
use App\Enums\Scheduling\AbsenceStatus;
use App\Enums\Scheduling\ShiftTaskStatus;
use App\Enums\Scheduling\SwapRequestStatus;
use App\Enums\Star\StarStatus;
use App\Models\Department;
use App\Models\DepartmentDocument;
use App\Models\DepartmentForm;
use App\Models\DepartmentNeed;
use App\Models\DepartmentPositionNomination;
use App\Models\DepartmentWorkflow;
use App\Models\Employee;
use App\Models\Scheduling\Absence;
use App\Models\Scheduling\DepartmentTodo;
use App\Models\Scheduling\Shift;
use App\Models\Scheduling\ShiftSwapRequest;
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
            'first_deputy_id' => 'nullable|exists:users,id',
            'second_deputy_id' => 'nullable|exists:users,id',
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
            $validated['image'] = 'departments/'.$request->image;
        }

        $department = Department::create($validated);

        return redirect()->route('departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function show(Department $department)
    {
        $department->load(['users', 'headOfDepartment', 'firstDeputy', 'secondDeputy']);

        // Check if user can view statistics
        $user = auth()->user();
        $canViewStatistics = $this->canUserViewStatistics($user, $department);

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
            ->map(fn ($employee) => [
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
            ->map(fn ($star) => [
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
                'first_deputy' => $department->firstDeputy ? [
                    'id' => $department->firstDeputy->id,
                    'name' => $department->firstDeputy->name,
                    'email' => $department->firstDeputy->email,
                ] : null,
                'second_deputy' => $department->secondDeputy ? [
                    'id' => $department->secondDeputy->id,
                    'name' => $department->secondDeputy->name,
                    'email' => $department->secondDeputy->email,
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
            'canManage' => $user->can('manage departments'),
            'canViewStatistics' => $canViewStatistics,
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
                    'creator:id,uuid,first_name,last_name,email',
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
            'positions' => $department->positions()->ordered()->get()->map(fn ($p) => [
                'id' => $p->id,
                'uuid' => $p->uuid,
                'name' => $p->name,
                'code' => $p->code,
                'description' => $p->description,
                'color' => $p->color,
                'hourly_rate' => $p->hourly_rate,
                'is_active' => $p->is_active,
            ]),
            'nominations' => DepartmentPositionNomination::with(['position', 'user', 'nominatedBy'])
                ->where('department_id', $department->id)
                ->active()
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($n) => [
                    'uuid' => $n->uuid,
                    'position' => [
                        'id' => $n->position->id,
                        'uuid' => $n->position->uuid,
                        'name' => $n->position->name,
                        'color' => $n->position->color,
                    ],
                    'user' => [
                        'id' => $n->user->id,
                        'uuid' => $n->user->uuid,
                        'name' => $n->user->name,
                        'email' => $n->user->email,
                    ],
                    'nominated_by' => $n->nominatedBy ? [
                        'id' => $n->nominatedBy->id,
                        'name' => $n->nominatedBy->name,
                    ] : null,
                    'start_date' => $n->start_date?->toDateString(),
                    'end_date' => $n->end_date?->toDateString(),
                    'notes' => $n->notes,
                    'is_active' => $n->is_active,
                    'created_at' => $n->created_at->toISOString(),
                ]),
            'documentsTree' => DepartmentDocument::getTreeForDepartment($department->id),
            'documentsCount' => DepartmentDocument::where('department_id', $department->id)->count(),
            // Only load statistics if user has permission to view them
            'statistics' => $canViewStatistics ? $this->getDepartmentStatistics($department) : null,
        ]);
    }

    public function edit(Department $department)
    {
        $department->load(['users', 'firstDeputy', 'secondDeputy']);
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
            'first_deputy_id' => 'nullable|exists:users,id',
            'second_deputy_id' => 'nullable|exists:users,id',
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
            $validated['image'] = 'departments/'.$request->image;
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

    /**
     * Get comprehensive statistics for a department
     * Note: Uses direct model queries instead of relationships to avoid issues with Inertia rendering
     */
    private function getDepartmentStatistics(Department $department): array
    {
        $departmentId = $department->id;

        // Workflows stats
        $workflows = DepartmentWorkflow::where('department_id', $departmentId)->get();
        $workflowsStats = [
            'total' => $workflows->count(),
            'active' => $workflows->where('status', 'active')->count(),
            'draft' => $workflows->where('status', 'draft')->count(),
            'deprecated' => $workflows->where('status', 'deprecated')->count(),
        ];

        // Forms stats
        $forms = DepartmentForm::where('department_id', $departmentId)->withCount('submissions')->get();
        $formsStats = [
            'total' => $forms->count(),
            'published' => $forms->where('status', 'published')->count(),
            'draft' => $forms->where('status', 'draft')->count(),
            'archived' => $forms->where('status', 'archived')->count(),
            'total_submissions' => $forms->sum('submissions_count'),
        ];

        // Needs stats
        $needs = DepartmentNeed::where('department_id', $departmentId)->get();
        $needsByStatus = $needs->groupBy(fn ($need) => $need->status?->value ?? 'unknown')->map->count()->toArray();
        $needsByPriority = $needs->groupBy(fn ($need) => $need->priority?->value ?? 'unknown')->map->count()->toArray();
        $needsStats = [
            'total' => $needs->count(),
            'by_status' => $needsByStatus,
            'by_priority' => $needsByPriority,
            'total_cost' => (int) ($needs->sum('estimated_cost') ?? 0),
        ];

        // Documents stats - use direct query, not relationship
        $totalDocSize = DepartmentDocument::where('department_id', $departmentId)->sum('file_size');
        $documentsStats = [
            'total' => DepartmentDocument::where('department_id', $departmentId)->count(),
            'total_size' => (int) $totalDocSize,
            'formatted_size' => $this->formatBytes((int) $totalDocSize),
        ];

        // Scheduling stats
        $schedulingStats = [
            'total_shifts' => Shift::where('department_id', $departmentId)->count(),
            'upcoming_shifts' => Shift::where('department_id', $departmentId)
                ->where('date', '>=', now()->toDateString())
                ->count(),
            'pending_absences' => Absence::where('department_id', $departmentId)
                ->where('status', AbsenceStatus::PENDING)
                ->count(),
            'approved_absences' => Absence::where('department_id', $departmentId)
                ->where('status', AbsenceStatus::APPROVED)
                ->where('end_date', '>=', now()->toDateString())
                ->count(),
            'pending_swap_requests' => ShiftSwapRequest::whereHas('requestedShift', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })
                ->whereIn('status', [SwapRequestStatus::PENDING_COLLEAGUE, SwapRequestStatus::PENDING_MANAGER])
                ->count(),
        ];

        // Todos stats - enhanced with detailed breakdown
        $todos = DepartmentTodo::where('department_id', $departmentId)
            ->with(['assignee:id,uuid,first_name,last_name'])
            ->get();

        $todosStats = [
            'total' => $todos->count(),
            'completed' => $todos->where('status', ShiftTaskStatus::COMPLETED)->count(),
            'in_progress' => $todos->where('status', ShiftTaskStatus::IN_PROGRESS)->count(),
            'pending' => $todos->where('status', ShiftTaskStatus::TODO)->count(),
            'overdue' => $todos->filter(fn ($todo) => $todo->is_overdue)->count(),
            'by_priority' => [
                'critical' => $todos->filter(fn ($t) => $t->priority?->value === 'critical')->count(),
                'high' => $todos->filter(fn ($t) => $t->priority?->value === 'high')->count(),
                'medium' => $todos->filter(fn ($t) => $t->priority?->value === 'medium')->count(),
                'low' => $todos->filter(fn ($t) => $t->priority?->value === 'low')->count(),
            ],
        ];

        // Task evolution over time
        $taskEvolution = $this->getTaskEvolution($departmentId);

        // Task distribution by member
        $tasksByMember = $this->getTasksByMember($todos, $department);

        // Performance metrics
        $performanceMetrics = $this->getPerformanceMetrics($todos, $department);

        return [
            'members' => [
                'total' => $department->users()->count(),
                'has_head' => $department->head_of_department !== null,
            ],
            'workflows' => $workflowsStats,
            'forms' => $formsStats,
            'needs' => $needsStats,
            'documents' => $documentsStats,
            'scheduling' => $schedulingStats,
            'todos' => $todosStats,
            'task_evolution' => $taskEvolution,
            'tasks_by_member' => $tasksByMember,
            'performance' => $performanceMetrics,
        ];
    }

    /**
     * Get task evolution data over different time periods
     */
    private function getTaskEvolution(int $departmentId): array
    {
        $now = now();

        // Weekly data (last 8 weeks)
        $weeklyData = [];
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
            $weekEnd = $now->copy()->subWeeks($i)->endOfWeek();

            $created = DepartmentTodo::where('department_id', $departmentId)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->count();

            $completed = DepartmentTodo::where('department_id', $departmentId)
                ->where('status', ShiftTaskStatus::COMPLETED)
                ->whereBetween('completed_at', [$weekStart, $weekEnd])
                ->count();

            $weeklyData[] = [
                'label' => 'S'.$weekStart->weekOfYear,
                'period' => $weekStart->format('d/m').' - '.$weekEnd->format('d/m'),
                'created' => $created,
                'completed' => $completed,
            ];
        }

        // Monthly data (last 6 months)
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $now->copy()->subMonths($i)->endOfMonth();

            $created = DepartmentTodo::where('department_id', $departmentId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $completed = DepartmentTodo::where('department_id', $departmentId)
                ->where('status', ShiftTaskStatus::COMPLETED)
                ->whereBetween('completed_at', [$monthStart, $monthEnd])
                ->count();

            $monthlyData[] = [
                'label' => $monthStart->translatedFormat('M'),
                'period' => $monthStart->translatedFormat('F Y'),
                'created' => $created,
                'completed' => $completed,
            ];
        }

        // Quarterly data (last 4 quarters)
        $quarterlyData = [];
        for ($i = 3; $i >= 0; $i--) {
            $quarterStart = $now->copy()->subQuarters($i)->startOfQuarter();
            $quarterEnd = $now->copy()->subQuarters($i)->endOfQuarter();

            $created = DepartmentTodo::where('department_id', $departmentId)
                ->whereBetween('created_at', [$quarterStart, $quarterEnd])
                ->count();

            $completed = DepartmentTodo::where('department_id', $departmentId)
                ->where('status', ShiftTaskStatus::COMPLETED)
                ->whereBetween('completed_at', [$quarterStart, $quarterEnd])
                ->count();

            $quarterlyData[] = [
                'label' => 'T'.$quarterStart->quarter.' '.$quarterStart->year,
                'period' => $quarterStart->translatedFormat('M').' - '.$quarterEnd->translatedFormat('M Y'),
                'created' => $created,
                'completed' => $completed,
            ];
        }

        // Semester data (last 2 semesters)
        $semesterData = [];
        for ($i = 1; $i >= 0; $i--) {
            $semesterStart = $now->copy()->subMonths($i * 6)->startOfMonth();
            if ($semesterStart->month <= 6) {
                $semesterStart = $semesterStart->copy()->startOfYear();
                $semesterEnd = $semesterStart->copy()->addMonths(5)->endOfMonth();
                $label = 'S1 '.$semesterStart->year;
            } else {
                $semesterStart = $semesterStart->copy()->setMonth(7)->startOfMonth();
                $semesterEnd = $semesterStart->copy()->endOfYear();
                $label = 'S2 '.$semesterStart->year;
            }

            $created = DepartmentTodo::where('department_id', $departmentId)
                ->whereBetween('created_at', [$semesterStart, $semesterEnd])
                ->count();

            $completed = DepartmentTodo::where('department_id', $departmentId)
                ->where('status', ShiftTaskStatus::COMPLETED)
                ->whereBetween('completed_at', [$semesterStart, $semesterEnd])
                ->count();

            $semesterData[] = [
                'label' => $label,
                'period' => $semesterStart->translatedFormat('M').' - '.$semesterEnd->translatedFormat('M Y'),
                'created' => $created,
                'completed' => $completed,
            ];
        }

        return [
            'weekly' => $weeklyData,
            'monthly' => $monthlyData,
            'quarterly' => $quarterlyData,
            'semester' => $semesterData,
        ];
    }

    /**
     * Get task distribution by member
     */
    private function getTasksByMember($todos, Department $department): array
    {
        $members = $department->users()->get(['users.id', 'users.uuid', 'users.first_name', 'users.last_name']);

        $tasksByMember = [];
        foreach ($members as $member) {
            $memberTodos = $todos->where('assigned_to', $member->id);
            $totalTasks = $memberTodos->count();
            $completedTasks = $memberTodos->where('status', ShiftTaskStatus::COMPLETED)->count();
            $inProgressTasks = $memberTodos->where('status', ShiftTaskStatus::IN_PROGRESS)->count();
            $overdueTasks = $memberTodos->filter(fn ($t) => $t->is_overdue)->count();

            $tasksByMember[] = [
                'uuid' => $member->uuid,
                'name' => trim($member->first_name.' '.$member->last_name),
                'total' => $totalTasks,
                'completed' => $completedTasks,
                'in_progress' => $inProgressTasks,
                'pending' => $totalTasks - $completedTasks - $inProgressTasks,
                'overdue' => $overdueTasks,
                'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0,
            ];
        }

        // Sort by total tasks descending
        usort($tasksByMember, fn ($a, $b) => $b['total'] - $a['total']);

        // Add unassigned tasks
        $unassignedTodos = $todos->whereNull('assigned_to');
        if ($unassignedTodos->count() > 0) {
            $tasksByMember[] = [
                'uuid' => null,
                'name' => 'Non assigné',
                'total' => $unassignedTodos->count(),
                'completed' => $unassignedTodos->where('status', ShiftTaskStatus::COMPLETED)->count(),
                'in_progress' => $unassignedTodos->where('status', ShiftTaskStatus::IN_PROGRESS)->count(),
                'pending' => $unassignedTodos->where('status', ShiftTaskStatus::TODO)->count(),
                'overdue' => $unassignedTodos->filter(fn ($t) => $t->is_overdue)->count(),
                'completion_rate' => 0,
            ];
        }

        return $tasksByMember;
    }

    /**
     * Get individual and collective performance metrics
     */
    private function getPerformanceMetrics($todos, Department $department): array
    {
        $members = $department->users()->get(['users.id', 'users.uuid', 'users.first_name', 'users.last_name']);
        $now = now();

        // Collective metrics
        $totalTasks = $todos->count();
        $completedTasks = $todos->where('status', ShiftTaskStatus::COMPLETED)->count();
        $overdueTasks = $todos->filter(fn ($t) => $t->is_overdue)->count();

        // This month's velocity
        $thisMonthStart = $now->copy()->startOfMonth();
        $completedThisMonth = $todos
            ->where('status', ShiftTaskStatus::COMPLETED)
            ->filter(fn ($t) => $t->completed_at && $t->completed_at->gte($thisMonthStart))
            ->count();

        // Last month's velocity for comparison
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();
        $completedLastMonth = DepartmentTodo::where('department_id', $department->id)
            ->where('status', ShiftTaskStatus::COMPLETED)
            ->whereBetween('completed_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $velocityChange = $completedLastMonth > 0
            ? round((($completedThisMonth - $completedLastMonth) / $completedLastMonth) * 100, 1)
            : ($completedThisMonth > 0 ? 100 : 0);

        // Average completion time (for completed tasks with both created_at and completed_at)
        $completedWithTimes = $todos
            ->where('status', ShiftTaskStatus::COMPLETED)
            ->filter(fn ($t) => $t->completed_at !== null);

        $avgCompletionDays = 0;
        if ($completedWithTimes->count() > 0) {
            $totalDays = $completedWithTimes->sum(function ($todo) {
                return $todo->created_at->diffInDays($todo->completed_at);
            });
            $avgCompletionDays = round($totalDays / $completedWithTimes->count(), 1);
        }

        // Individual performance
        $individualPerformance = [];
        foreach ($members as $member) {
            $memberTodos = $todos->where('assigned_to', $member->id);
            $memberTotal = $memberTodos->count();
            $memberCompleted = $memberTodos->where('status', ShiftTaskStatus::COMPLETED)->count();
            $memberOverdue = $memberTodos->filter(fn ($t) => $t->is_overdue)->count();

            // Member's completion time
            $memberCompletedWithTimes = $memberTodos
                ->where('status', ShiftTaskStatus::COMPLETED)
                ->filter(fn ($t) => $t->completed_at !== null);

            $memberAvgDays = 0;
            if ($memberCompletedWithTimes->count() > 0) {
                $memberTotalDays = $memberCompletedWithTimes->sum(function ($todo) {
                    return $todo->created_at->diffInDays($todo->completed_at);
                });
                $memberAvgDays = round($memberTotalDays / $memberCompletedWithTimes->count(), 1);
            }

            // Tasks completed this month by member
            $memberCompletedThisMonth = $memberTodos
                ->where('status', ShiftTaskStatus::COMPLETED)
                ->filter(fn ($t) => $t->completed_at && $t->completed_at->gte($thisMonthStart))
                ->count();

            $individualPerformance[] = [
                'uuid' => $member->uuid,
                'name' => trim($member->first_name.' '.$member->last_name),
                'total_tasks' => $memberTotal,
                'completed_tasks' => $memberCompleted,
                'overdue_tasks' => $memberOverdue,
                'completion_rate' => $memberTotal > 0 ? round(($memberCompleted / $memberTotal) * 100, 1) : 0,
                'overdue_rate' => $memberTotal > 0 ? round(($memberOverdue / $memberTotal) * 100, 1) : 0,
                'avg_completion_days' => $memberAvgDays,
                'completed_this_month' => $memberCompletedThisMonth,
            ];
        }

        // Sort by completion rate descending
        usort($individualPerformance, fn ($a, $b) => $b['completion_rate'] <=> $a['completion_rate']);

        return [
            'collective' => [
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0,
                'overdue_tasks' => $overdueTasks,
                'overdue_rate' => $totalTasks > 0 ? round(($overdueTasks / $totalTasks) * 100, 1) : 0,
                'velocity_this_month' => $completedThisMonth,
                'velocity_last_month' => $completedLastMonth,
                'velocity_change' => $velocityChange,
                'avg_completion_days' => $avgCompletionDays,
            ],
            'individual' => $individualPerformance,
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Check if a user can view department statistics
     * Access is granted if:
     * - User is the head of department
     * - User is a member of the department
     * - User has "manage departments" permission
     * - User has "view department statistics" permission
     */
    private function canUserViewStatistics($user, Department $department): bool
    {
        // Admin/manager with permission can always view
        if ($user->can('manage departments') || $user->can('view department statistics')) {
            return true;
        }

        // Head of department can view
        if ($department->head_of_department === $user->id) {
            return true;
        }

        // Member of the department can view
        if ($department->users->contains('id', $user->id)) {
            return true;
        }

        return false;
    }
}
