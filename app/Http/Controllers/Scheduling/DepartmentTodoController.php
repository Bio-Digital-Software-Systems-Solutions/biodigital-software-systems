<?php

namespace App\Http\Controllers\Scheduling;

use App\Enums\Scheduling\ShiftTaskStatus;
use App\Enums\Scheduling\TodoPriority;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Scheduling\DepartmentTodo;
use App\Models\Scheduling\Shift;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentTodoController extends Controller
{
    /**
     * Display todos for a department
     */
    public function index(Request $request, Department $department): Response|JsonResponse
    {
        $this->authorize('viewAny', [DepartmentTodo::class, $department]);

        $query = DepartmentTodo::forDepartment($department)
            ->with(['assignee', 'creator', 'shift.user', 'completedBy']);

        // Filter by status
        if ($request->filled('status')) {
            $status = ShiftTaskStatus::tryFrom($request->status);
            if ($status) {
                $query->byStatus($status);
            }
        } else {
            // By default, show only active todos
            $query->active();
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $priority = TodoPriority::tryFrom($request->priority);
            if ($priority) {
                $query->byPriority($priority);
            }
        }

        // Filter by assignee
        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->unassigned();
            } else {
                $user = User::where('uuid', $request->assigned_to)->first();
                if ($user) {
                    $query->assignedTo($user);
                }
            }
        }

        // Filter by shift
        if ($request->filled('shift_id')) {
            if ($request->shift_id === 'none') {
                $query->withoutShift();
            } else {
                $shift = Shift::where('uuid', $request->shift_id)->first();
                if ($shift) {
                    $query->forShift($shift);
                }
            }
        }

        // Filter by due date
        if ($request->filled('due')) {
            match ($request->due) {
                'overdue' => $query->overdue(),
                'today' => $query->dueToday(),
                'this_week' => $query->dueThisWeek(),
                default => null,
            };
        }

        $todos = $query->ordered()->get();

        // For API requests, return JSON
        if ($request->wantsJson()) {
            return response()->json([
                'todos' => $todos->map(fn ($todo): array => $todo->toArrayForApi()),
                'stats' => $this->getTodoStats($department),
            ]);
        }

        return Inertia::render('Departments/Todos/Index', [
            'department' => $department,
            'todos' => $todos->map(fn ($todo): array => $todo->toArrayForApi()),
            'stats' => $this->getTodoStats($department),
            'members' => $this->getDepartmentMembers($department),
            'statuses' => collect(ShiftTaskStatus::cases())->map(fn ($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
            'priorities' => collect(TodoPriority::cases())->map(fn ($p): array => [
                'value' => $p->value,
                'label' => $p->label(),
                'color' => $p->color(),
            ]),
            'filters' => [
                'status' => $request->status,
                'priority' => $request->priority,
                'assigned_to' => $request->assigned_to,
                'shift_id' => $request->shift_id,
                'due' => $request->due,
            ],
        ]);
    }

    /**
     * Store a new todo
     */
    public function store(Request $request, Department $department): JsonResponse|RedirectResponse
    {
        $this->authorize('create', [DepartmentTodo::class, $department]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,uuid',
            'backup_assignees' => 'nullable|array',
            'backup_assignees.*' => 'exists:users,uuid',
            'shift_id' => 'nullable|exists:shifts,uuid',
            'estimated_minutes' => 'nullable|integer|min:1',
        ]);

        $assignee = null;
        if (! empty($validated['assigned_to'])) {
            $assignee = User::where('uuid', $validated['assigned_to'])->first();
        }

        $backupAssigneeIds = null;
        if (! empty($validated['backup_assignees'])) {
            $backupAssigneeIds = User::whereIn('uuid', $validated['backup_assignees'])
                ->pluck('id')
                ->toArray();
        }

        $shift = null;
        if (! empty($validated['shift_id'])) {
            $shift = Shift::where('uuid', $validated['shift_id'])->first();
        }

        $todo = DepartmentTodo::create([
            'department_id' => $department->id,
            'shift_id' => $shift?->id,
            'assigned_to' => $assignee?->id,
            'backup_assignees' => $backupAssigneeIds,
            'created_by' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => ShiftTaskStatus::TODO,
            'priority' => $validated['priority'] ?? 'medium',
            'due_date' => $validated['due_date'] ?? null,
            'estimated_minutes' => $validated['estimated_minutes'] ?? null,
        ]);

        $todo->load(['assignee', 'creator', 'shift.user', 'completedBy']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tâche créée avec succès',
                'todo' => $todo->toArrayForApi(),
            ]);
        }

        return redirect()->back()->with('success', 'Tâche créée avec succès');
    }

    /**
     * Get a single todo
     */
    public function show(Request $request, Department $department, DepartmentTodo $todo): JsonResponse|Response
    {
        if ($todo->department_id !== $department->id) {
            abort(404);
        }

        $this->authorize('view', $todo);

        $todo->load(['assignee', 'creator', 'shift.user', 'completedBy']);

        if ($request->wantsJson()) {
            return response()->json([
                'todo' => $todo->toArrayForApi(),
            ]);
        }

        return Inertia::render('Departments/Todos/Show', [
            'department' => $department,
            'todo' => $todo->toArrayForApi(),
            'members' => $this->getDepartmentMembers($department),
        ]);
    }

    /**
     * Update a todo
     */
    public function update(Request $request, Department $department, DepartmentTodo $todo): JsonResponse|RedirectResponse
    {
        if ($todo->department_id !== $department->id) {
            abort(404);
        }

        $this->authorize('update', $todo);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:todo,in_progress,completed,blocked,cancelled',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|string',
            'backup_assignees' => 'nullable|array',
            'backup_assignees.*' => 'string',
            'shift_id' => 'nullable|string',
            'estimated_minutes' => 'nullable|integer|min:1',
        ]);

        $updateData = [];

        if (isset($validated['title'])) {
            $updateData['title'] = $validated['title'];
        }

        if (array_key_exists('description', $validated)) {
            $updateData['description'] = $validated['description'];
        }

        if (isset($validated['status'])) {
            $newStatus = ShiftTaskStatus::from($validated['status']);

            // If completing the todo, set completion metadata
            if ($newStatus === ShiftTaskStatus::COMPLETED && $todo->status !== ShiftTaskStatus::COMPLETED) {
                $updateData['status'] = $newStatus;
                $updateData['completed_at'] = now();
                $updateData['completed_by'] = auth()->id();
            } elseif ($newStatus !== ShiftTaskStatus::COMPLETED && $todo->status === ShiftTaskStatus::COMPLETED) {
                // Reopening a completed todo
                $updateData['status'] = $newStatus;
                $updateData['completed_at'] = null;
                $updateData['completed_by'] = null;
            } else {
                $updateData['status'] = $newStatus;
            }
        }

        if (isset($validated['priority'])) {
            $updateData['priority'] = $validated['priority'];
        }

        if (array_key_exists('due_date', $validated)) {
            $updateData['due_date'] = $validated['due_date'];
        }

        if (array_key_exists('assigned_to', $validated)) {
            if (empty($validated['assigned_to'])) {
                $updateData['assigned_to'] = null;
            } else {
                $assignee = User::where('uuid', $validated['assigned_to'])->first();
                $updateData['assigned_to'] = $assignee?->id;
            }
        }

        if (array_key_exists('backup_assignees', $validated)) {
            if (empty($validated['backup_assignees'])) {
                $updateData['backup_assignees'] = null;
            } else {
                $updateData['backup_assignees'] = User::whereIn('uuid', $validated['backup_assignees'])
                    ->pluck('id')
                    ->toArray();
            }
        }

        if (array_key_exists('shift_id', $validated)) {
            if (empty($validated['shift_id'])) {
                $updateData['shift_id'] = null;
            } else {
                $shift = Shift::where('uuid', $validated['shift_id'])->first();
                $updateData['shift_id'] = $shift?->id;
            }
        }

        if (array_key_exists('estimated_minutes', $validated)) {
            $updateData['estimated_minutes'] = $validated['estimated_minutes'];
        }

        $todo->update($updateData);
        $todo->load(['assignee', 'creator', 'shift.user', 'completedBy']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tâche mise à jour',
                'todo' => $todo->toArrayForApi(),
            ]);
        }

        return redirect()->back()->with('success', 'Tâche mise à jour');
    }

    /**
     * Delete a todo
     */
    public function destroy(Request $request, Department $department, DepartmentTodo $todo): JsonResponse|RedirectResponse
    {
        if ($todo->department_id !== $department->id) {
            abort(404);
        }

        $this->authorize('delete', $todo);

        $todo->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tâche supprimée',
            ]);
        }

        return redirect()->back()->with('success', 'Tâche supprimée');
    }

    /**
     * Toggle todo completion status
     */
    public function toggleComplete(Request $request, Department $department, DepartmentTodo $todo): JsonResponse
    {
        if ($todo->department_id !== $department->id) {
            abort(404);
        }

        $this->authorize('update', $todo);

        if ($todo->status === ShiftTaskStatus::COMPLETED) {
            $todo->reopen();
            $message = 'Tâche réouverte';
        } else {
            $todo->complete(auth()->user());
            $message = 'Tâche terminée';
        }

        $todo->load(['assignee', 'creator', 'shift.user', 'completedBy']);

        return response()->json([
            'success' => true,
            'message' => $message,
            'todo' => $todo->toArrayForApi(),
        ]);
    }

    /**
     * Update todo status
     */
    public function updateStatus(Request $request, Department $department, DepartmentTodo $todo): JsonResponse
    {
        if ($todo->department_id !== $department->id) {
            abort(404);
        }

        $this->authorize('update', $todo);

        $validated = $request->validate([
            'status' => 'required|string|in:todo,in_progress,completed,blocked,cancelled',
        ]);

        $newStatus = ShiftTaskStatus::from($validated['status']);

        match ($newStatus) {
            ShiftTaskStatus::COMPLETED => $todo->complete(auth()->user()),
            ShiftTaskStatus::IN_PROGRESS => $todo->start(),
            ShiftTaskStatus::BLOCKED => $todo->block(),
            ShiftTaskStatus::CANCELLED => $todo->cancel(),
            ShiftTaskStatus::TODO => $todo->status->isFinal() ? $todo->reopen() : $todo->pause(),
        };

        $todo->load(['assignee', 'creator', 'shift.user', 'completedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour',
            'todo' => $todo->toArrayForApi(),
        ]);
    }

    /**
     * Assign a todo to a user
     */
    public function assign(Request $request, Department $department, DepartmentTodo $todo): JsonResponse
    {
        if ($todo->department_id !== $department->id) {
            abort(404);
        }

        $this->authorize('update', $todo);

        $validated = $request->validate([
            'user_uuid' => 'nullable|exists:users,uuid',
        ]);

        if (empty($validated['user_uuid'])) {
            $todo->unassign();
            $message = 'Assignation retirée';
        } else {
            $user = User::where('uuid', $validated['user_uuid'])->first();
            $todo->assign($user);
            $message = 'Tâche assignée à '.($user->full_name ?? $user->name);
        }

        $todo->load(['assignee', 'creator', 'shift.user', 'completedBy']);

        return response()->json([
            'success' => true,
            'message' => $message,
            'todo' => $todo->toArrayForApi(),
        ]);
    }

    /**
     * Get todos for a specific shift
     */
    public function forShift(Request $request, Department $department, Shift $shift): JsonResponse
    {
        $this->authorize('viewAny', [DepartmentTodo::class, $department]);

        $todos = DepartmentTodo::forDepartment($department)
            ->forShift($shift)
            ->with(['assignee', 'creator', 'completedBy', 'shift.user'])
            ->ordered()
            ->get();

        return response()->json([
            'todos' => $todos->map(fn ($todo): array => $todo->toArrayForApi()),
        ]);
    }

    /**
     * Bulk update todos
     */
    public function bulkUpdate(Request $request, Department $department): JsonResponse
    {
        $this->authorize('bulkUpdate', [DepartmentTodo::class, $department]);

        $validated = $request->validate([
            'todo_uuids' => 'required|array',
            'todo_uuids.*' => 'required|string',
            'action' => 'required|string|in:complete,cancel,delete,assign,set_priority',
            'value' => 'nullable|string',
        ]);

        $todos = DepartmentTodo::forDepartment($department)
            ->whereIn('uuid', $validated['todo_uuids'])
            ->get();

        $count = 0;
        foreach ($todos as $todo) {
            switch ($validated['action']) {
                case 'complete':
                    if ($todo->complete(auth()->user())) {
                        $count++;
                    }
                    break;
                case 'cancel':
                    if ($todo->cancel()) {
                        $count++;
                    }
                    break;
                case 'delete':
                    $todo->delete();
                    $count++;
                    break;
                case 'assign':
                    if (! empty($validated['value'])) {
                        $user = User::where('uuid', $validated['value'])->first();
                        if ($user) {
                            $todo->assign($user);
                            $count++;
                        }
                    } else {
                        $todo->unassign();
                        $count++;
                    }
                    break;
                case 'set_priority':
                    if (! empty($validated['value']) && TodoPriority::tryFrom($validated['value'])) {
                        $todo->update(['priority' => $validated['value']]);
                        $count++;
                    }
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} tâche(s) mise(s) à jour",
            'count' => $count,
        ]);
    }

    /**
     * Get todo statistics for a department
     */
    protected function getTodoStats(Department $department): array
    {
        $base = DepartmentTodo::forDepartment($department);

        return [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->pending()->count(),
            'completed' => (clone $base)->completed()->count(),
            'overdue' => (clone $base)->overdue()->count(),
            'due_today' => (clone $base)->dueToday()->count(),
            'unassigned' => (clone $base)->active()->unassigned()->count(),
            'by_priority' => [
                'urgent' => (clone $base)->active()->byPriority(TodoPriority::URGENT)->count(),
                'high' => (clone $base)->active()->byPriority(TodoPriority::HIGH)->count(),
                'medium' => (clone $base)->active()->byPriority(TodoPriority::MEDIUM)->count(),
                'low' => (clone $base)->active()->byPriority(TodoPriority::LOW)->count(),
            ],
        ];
    }

    /**
     * Get department members for assignment
     */
    protected function getDepartmentMembers(Department $department): array
    {
        return $department->users()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($user): array => [
                'uuid' => $user->uuid,
                'name' => $user->full_name ?? $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url ?? null,
            ])
            ->toArray();
    }
}
