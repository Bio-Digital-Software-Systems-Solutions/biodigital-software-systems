<?php

namespace App\Http\Controllers\Scheduling;

use App\Enums\Scheduling\ShiftStatus;
use App\Enums\Scheduling\ShiftType;
use App\Enums\Employee\EmployeeStatus;
use App\Enums\Star\StarStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Scheduling\Shift;
use App\Models\Scheduling\WeeklySchedule;
use App\Models\Star;
use App\Models\User;
use App\Services\Scheduling\ConflictDetectionService;
use App\Services\Scheduling\SchedulingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftController extends Controller
{
    public function __construct(
        protected SchedulingService $schedulingService,
        protected ConflictDetectionService $conflictService
    ) {}

    /**
     * Display shifts for a schedule
     */
    public function index(Request $request, Department $department, WeeklySchedule $schedule): Response
    {
        $this->authorize('view', $department);

        $shifts = $schedule->shifts()
            ->with(['user', 'users', 'position', 'tasks', 'assignedBy'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return Inertia::render('Departments/Schedule/Shifts/Index', [
            'department' => $department,
            'schedule' => $schedule,
            'shifts' => $shifts,
            'shiftTypes' => collect(ShiftType::cases())->map(fn($t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'color' => $t->color(),
            ]),
            'shiftStatuses' => collect(ShiftStatus::cases())->map(fn($s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
        ]);
    }

    /**
     * Show form to create a new shift
     */
    public function create(Department $department, WeeklySchedule $schedule): Response
    {
        $this->authorize('update', $department);

        // Get department members (users)
        $users = $department->members()->get()->map(fn($user) => [
            'id' => $user->id,
            'uuid' => $user->uuid ?? null,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'type' => 'user',
        ]);

        // Get active employees
        $employees = Employee::with('user')
            ->where('status', EmployeeStatus::ACTIVE)
            ->get()
            ->map(fn($employee) => [
                'id' => $employee->user_id,
                'uuid' => $employee->uuid,
                'first_name' => $employee->user->first_name ?? '',
                'last_name' => $employee->user->last_name ?? '',
                'email' => $employee->user->email ?? '',
                'position' => $employee->position,
                'type' => 'employee',
            ]);

        // Get active stars (volunteers)
        $stars = Star::with('user')
            ->where('status', StarStatus::ACTIVE)
            ->get()
            ->map(fn($star) => [
                'id' => $star->user_id,
                'uuid' => $star->uuid,
                'first_name' => $star->user->first_name ?? '',
                'last_name' => $star->user->last_name ?? '',
                'email' => $star->user->email ?? '',
                'title' => $star->title,
                'type' => 'star',
            ]);

        $positions = $department->activePositions()->get()->map(fn($p) => [
            'id' => $p->id,
            'uuid' => $p->uuid,
            'name' => $p->name,
            'code' => $p->code,
            'color' => $p->color,
            'hourly_rate' => $p->hourly_rate,
        ]);

        return Inertia::render('Departments/Schedule/Shifts/Create', [
            'department' => $department,
            'schedule' => $schedule,
            'users' => $users,
            'employees' => $employees,
            'stars' => $stars,
            'positions' => $positions,
            'shiftTypes' => collect(ShiftType::cases())->map(fn($t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'color' => $t->color(),
                'defaultDuration' => $t->defaultDuration(),
            ]),
        ]);
    }

    /**
     * Store a new shift
     */
    public function store(Request $request, Department $department, WeeklySchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($schedule->is_locked) {
            return back()->with('error', 'Ce planning est verrouillé.');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'type' => 'required|string',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'position_id' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'break_duration' => 'nullable|integer|min:0',
            'min_employees' => 'nullable|integer|min:1',
            'max_employees' => 'nullable|integer|min:1',
            'required_skills' => 'nullable|array',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_overtime' => 'boolean',
            'requires_approval' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $userIds = $validated['user_ids'] ?? [];
        unset($validated['user_ids']);

        $validated['weekly_schedule_id'] = $schedule->id;
        $validated['department_id'] = $department->id;
        $validated['status'] = ShiftStatus::DRAFT;

        $shift = $this->schedulingService->createShift($validated);

        // Sync users to pivot table
        if (!empty($userIds)) {
            $shift->users()->sync($userIds);

            // Check for conflicts for first user (primary assignee)
            $firstUser = User::find($userIds[0]);
            if ($firstUser) {
                $conflicts = $this->conflictService->detectConflicts($shift, $firstUser);
                if ($conflicts['has_warnings']) {
                    session()->flash('warning', 'Shift créé avec des avertissements.');
                }
            }
        }

        return redirect()
            ->route('departments.schedule.index', [
                'department' => $department,
                'week' => $schedule->week_start->format('Y-m-d'),
            ])
            ->with('success', 'Shift créé avec succès.');
    }

    /**
     * Show a specific shift
     */
    public function show(Department $department, WeeklySchedule $schedule, Shift $shift): Response
    {
        $this->authorize('view', $department);

        $shift->load(['user', 'users', 'position', 'tasks', 'assignedBy', 'weeklySchedule']);

        $conflicts = null;
        if ($shift->user) {
            $conflicts = $this->conflictService->detectConflicts($shift, $shift->user);
        }

        return Inertia::render('Departments/Schedule/Shifts/Show', [
            'department' => $department,
            'schedule' => $schedule,
            'shift' => $shift,
            'conflicts' => $conflicts,
        ]);
    }

    /**
     * Show form to edit a shift
     */
    public function edit(Department $department, WeeklySchedule $schedule, Shift $shift): Response
    {
        $this->authorize('update', $department);

        $shift->load(['user', 'users', 'position', 'tasks']);

        // Get department members (users)
        $users = $department->members()->get()->map(fn($user) => [
            'id' => $user->id,
            'uuid' => $user->uuid ?? null,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'type' => 'user',
        ]);

        // Get active employees
        $employees = Employee::with('user')
            ->where('status', EmployeeStatus::ACTIVE)
            ->get()
            ->map(fn($employee) => [
                'id' => $employee->user_id,
                'uuid' => $employee->uuid,
                'first_name' => $employee->user->first_name ?? '',
                'last_name' => $employee->user->last_name ?? '',
                'email' => $employee->user->email ?? '',
                'position' => $employee->position,
                'type' => 'employee',
            ]);

        // Get active stars (volunteers)
        $stars = Star::with('user')
            ->where('status', StarStatus::ACTIVE)
            ->get()
            ->map(fn($star) => [
                'id' => $star->user_id,
                'uuid' => $star->uuid,
                'first_name' => $star->user->first_name ?? '',
                'last_name' => $star->user->last_name ?? '',
                'email' => $star->user->email ?? '',
                'title' => $star->title,
                'type' => 'star',
            ]);

        $positions = $department->activePositions()->get()->map(fn($p) => [
            'id' => $p->id,
            'uuid' => $p->uuid,
            'name' => $p->name,
            'code' => $p->code,
            'color' => $p->color,
            'hourly_rate' => $p->hourly_rate,
        ]);

        return Inertia::render('Departments/Schedule/Shifts/Edit', [
            'department' => $department,
            'schedule' => $schedule,
            'shift' => $shift,
            'users' => $users,
            'employees' => $employees,
            'stars' => $stars,
            'positions' => $positions,
            'shiftTypes' => collect(ShiftType::cases())->map(fn($t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'color' => $t->color(),
            ]),
            'shiftStatuses' => collect(ShiftStatus::cases())->map(fn($s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
        ]);
    }

    /**
     * Update a shift
     */
    public function update(Request $request, Department $department, WeeklySchedule $schedule, Shift $shift): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($schedule->is_locked) {
            return back()->with('error', 'Ce planning est verrouillé.');
        }

        $validated = $request->validate([
            'date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'type' => 'sometimes|string',
            'status' => 'sometimes|string',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'position_id' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'break_duration' => 'nullable|integer|min:0',
            'min_employees' => 'nullable|integer|min:1',
            'max_employees' => 'nullable|integer|min:1',
            'required_skills' => 'nullable|array',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_overtime' => 'boolean',
            'requires_approval' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $userIds = $validated['user_ids'] ?? [];
        unset($validated['user_ids']);

        $shift->update($validated);

        // Sync users to pivot table
        $shift->users()->sync($userIds);

        return redirect()->route('departments.schedule.shifts.show', [
            'department' => $department->uuid,
            'schedule' => $schedule->uuid,
            'shift' => $shift->uuid,
        ])->with('success', 'Shift mis à jour avec succès.');
    }

    /**
     * Assign an employee to a shift
     */
    public function assign(Request $request, Department $department, WeeklySchedule $schedule, Shift $shift): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($schedule->is_locked) {
            return back()->with('error', 'Ce planning est verrouillé.');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $employee = User::findOrFail($validated['user_id']);

        $result = $this->schedulingService->assignShift($shift, $employee, $request->user());

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        $message = 'Employé assigné avec succès.';
        if (!empty($result['warnings'])) {
            session()->flash('warning', 'Assignation effectuée avec des avertissements.');
        }

        return back()->with('success', $message);
    }

    /**
     * Unassign an employee from a shift
     */
    public function unassign(Department $department, WeeklySchedule $schedule, Shift $shift): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($schedule->is_locked) {
            return back()->with('error', 'Ce planning est verrouillé.');
        }

        $this->schedulingService->unassignShift($shift);

        return back()->with('success', 'Employé retiré du shift.');
    }

    /**
     * Check in for a shift
     */
    public function checkIn(Request $request, Department $department, WeeklySchedule $schedule, Shift $shift): RedirectResponse
    {
        if ($shift->user_id !== $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas pointer pour ce shift.');
        }

        if (!$shift->can_check_in) {
            return back()->with('error', 'Impossible de pointer maintenant.');
        }

        $shift->checkIn();

        return back()->with('success', 'Pointage d\'arrivée enregistré.');
    }

    /**
     * Check out from a shift
     */
    public function checkOut(Request $request, Department $department, WeeklySchedule $schedule, Shift $shift): RedirectResponse
    {
        if ($shift->user_id !== $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas pointer pour ce shift.');
        }

        if (!$shift->can_check_out) {
            return back()->with('error', 'Impossible de pointer maintenant.');
        }

        $shift->checkOut();

        return back()->with('success', 'Pointage de départ enregistré.');
    }

    /**
     * Get available employees for a shift (API)
     */
    public function availableEmployees(Department $department, WeeklySchedule $schedule, Shift $shift)
    {
        $this->authorize('view', $department);

        $available = $this->schedulingService->getAvailableEmployees($shift);

        return response()->json([
            'employees' => $available,
        ]);
    }

    /**
     * Cancel a shift
     */
    public function cancel(Request $request, Department $department, WeeklySchedule $schedule, Shift $shift): RedirectResponse
    {
        $this->authorize('update', $department);

        // Check if shift can be cancelled
        if ($shift->status->isFinal()) {
            return back()->with('error', 'Ce shift ne peut pas être annulé car il est déjà terminé ou annulé.');
        }

        if (!$shift->status->canTransitionTo(ShiftStatus::CANCELLED)) {
            return back()->with('error', 'Ce shift ne peut pas être annulé depuis son statut actuel.');
        }

        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        $cancellationReason = $validated['cancellation_reason'] ?? null;

        $shift->update([
            'status' => ShiftStatus::CANCELLED,
            'notes' => $cancellationReason
                ? ($shift->notes ? $shift->notes . "\n\n" : '') . "Raison d'annulation: " . $cancellationReason
                : $shift->notes,
        ]);

        return redirect()->route('departments.schedule.shifts.show', [
            'department' => $department->uuid,
            'schedule' => $schedule->uuid,
            'shift' => $shift->uuid,
        ])->with('success', 'Shift annulé avec succès.');
    }

    /**
     * Delete a shift
     */
    public function destroy(Department $department, WeeklySchedule $schedule, Shift $shift): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($schedule->is_locked) {
            return back()->with('error', 'Ce planning est verrouillé.');
        }

        $shift->tasks()->delete();
        $shift->delete();

        return back()->with('success', 'Shift supprimé avec succès.');
    }

    /**
     * Bulk create shifts
     */
    public function bulkStore(Request $request, Department $department, WeeklySchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($schedule->is_locked) {
            return back()->with('error', 'Ce planning est verrouillé.');
        }

        $validated = $request->validate([
            'shifts' => 'required|array|min:1',
            'shifts.*.date' => 'required|date',
            'shifts.*.start_time' => 'required|date_format:H:i',
            'shifts.*.end_time' => 'required|date_format:H:i',
            'shifts.*.type' => 'required|string',
            'shifts.*.user_id' => 'nullable|exists:users,id',
        ]);

        $created = 0;
        foreach ($validated['shifts'] as $shiftData) {
            $shiftData['weekly_schedule_id'] = $schedule->id;
            $shiftData['department_id'] = $department->id;
            $shiftData['status'] = ShiftStatus::DRAFT;

            $this->schedulingService->createShift($shiftData);
            $created++;
        }

        return back()->with('success', "{$created} shift(s) créé(s) avec succès.");
    }

    /**
     * Bulk delete shifts
     */
    public function bulkDestroy(Request $request, Department $department, WeeklySchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($schedule->is_locked) {
            return back()->with('error', 'Ce planning est verrouillé.');
        }

        $validated = $request->validate([
            'shift_ids' => 'required|array|min:1',
            'shift_ids.*' => 'exists:shifts,id',
        ]);

        $deleted = Shift::whereIn('id', $validated['shift_ids'])
            ->where('weekly_schedule_id', $schedule->id)
            ->delete();

        return back()->with('success', "{$deleted} shift(s) supprimé(s).");
    }
}
