<?php

namespace App\Http\Controllers\Scheduling;

use App\Enums\Employee\EmployeeStatus;
use App\Enums\Scheduling\ScheduleStatus;
use App\Enums\Scheduling\ShiftStatus;
use App\Enums\Scheduling\ShiftType;
use App\Enums\Scheduling\TodoPriority;
use App\Enums\Star\StarStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Scheduling\DepartmentTodo;
use App\Models\Scheduling\Shift;
use App\Models\Scheduling\ShiftSeries;
use App\Models\Scheduling\WeeklySchedule;
use App\Models\Star;
use App\Models\User;
use App\Services\Scheduling\ConflictDetectionService;
use App\Services\Scheduling\SchedulingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            'shiftTypes' => collect(ShiftType::cases())->map(fn ($t): array => [
                'value' => $t->value,
                'label' => $t->label(),
                'color' => $t->color(),
            ]),
            'shiftStatuses' => collect(ShiftStatus::cases())->map(fn ($s): array => [
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

        // Get all active users from users table
        $users = User::whereNotNull('email_verified_at')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($user): array => [
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
            ->whereHas('user')
            ->get()
            ->map(fn ($employee): array => [
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
            ->map(fn ($star): array => [
                'id' => $star->user_id ?? $star->id,
                'uuid' => $star->uuid,
                'first_name' => $star->user->first_name ?? $star->title ?? '',
                'last_name' => $star->user->last_name ?? '',
                'email' => $star->user->email ?? '',
                'title' => $star->title,
                'type' => 'star',
            ]);

        $positions = $department->activePositions()->get()->map(fn ($p): array => [
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
            'shiftTypes' => collect(ShiftType::cases())->map(fn ($t): array => [
                'value' => $t->value,
                'label' => $t->label(),
                'color' => $t->color(),
                'defaultDuration' => $t->defaultDuration(),
            ]),
        ]);
    }

    /**
     * Store a new shift (or multiple shifts for series/recurring)
     */
    public function store(Request $request, Department $department, WeeklySchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($schedule->is_locked) {
            return back()->with('error', 'Ce planning est verrouillé.');
        }

        $validated = $request->validate([
            'creation_mode' => 'required|in:single,multiple_dates,recurring',
            'date' => 'required_if:creation_mode,single,recurring|nullable|date',
            'dates' => 'required_if:creation_mode,multiple_dates|nullable|array',
            'dates.*' => 'date',
            'recurrence_type' => 'required_if:creation_mode,recurring|nullable|in:daily,weekly,monthly',
            'recurrence_end_date' => 'required_if:creation_mode,recurring|nullable|date|after:date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
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

        $creationMode = $validated['creation_mode'];
        $userIds = $validated['user_ids'] ?? [];

        // Build base shift data (without date/schedule)
        $baseData = collect($validated)->except([
            'creation_mode', 'date', 'dates', 'recurrence_type', 'recurrence_end_date', 'user_ids',
        ])->toArray();
        $baseData['department_id'] = $department->id;
        $baseData['status'] = ShiftStatus::DRAFT;

        if ($creationMode === 'single') {
            $baseData['date'] = $validated['date'];
            $baseData['weekly_schedule_id'] = $schedule->id;

            $shift = $this->schedulingService->createShift($baseData);
            $this->syncUsersAndCheckConflicts($shift, $userIds);
            $count = 1;
        } else {
            // Determine dates list
            if ($creationMode === 'multiple_dates') {
                $dates = $validated['dates'];
                $recurrenceType = null;
            } else {
                $dates = $this->generateRecurringDates(
                    $validated['date'],
                    $validated['recurrence_end_date'],
                    $validated['recurrence_type']
                );
                $recurrenceType = $validated['recurrence_type'];
            }

            $series = ShiftSeries::create(['recurrence_type' => $recurrenceType]);
            $count = 0;

            foreach ($dates as $date) {
                $weeklySchedule = $this->findOrCreateWeeklySchedule($department, $date);
                $shiftData = array_merge($baseData, [
                    'date' => $date,
                    'series_id' => $series->id,
                    'weekly_schedule_id' => $weeklySchedule->id,
                ]);

                $shift = $this->schedulingService->createShift($shiftData);
                $this->syncUsersAndCheckConflicts($shift, $userIds);
                $count++;
            }
        }

        $message = $count === 1 ? 'Shift créé avec succès.' : "{$count} shifts créés avec succès.";

        // If created from the week calendar on a shift's show page, redirect back
        if ($request->boolean('_from_calendar')) {
            return back()->with('success', $message);
        }

        return redirect()
            ->route('departments.schedule.index', [
                'department' => $department,
                'week' => $schedule->week_start->format('Y-m-d'),
            ])
            ->with('success', $message);
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

        // Get TODOs linked to this shift
        $shiftTodosCollection = DepartmentTodo::forDepartment($department)
            ->forShift($shift)
            ->with(['shift.user'])
            ->ordered()
            ->get();

        DepartmentTodo::eagerLoadBackupUsers($shiftTodosCollection);

        $shiftTodos = $shiftTodosCollection->map(fn ($todo): array => $todo->toArrayForApi());

        // Get department members for calendar assignment popover
        $members = $department->members()
            ->select('users.id', 'users.uuid', 'users.first_name', 'users.last_name', 'users.email')
            ->get()
            ->map(fn ($user): array => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->first_name && $user->last_name
                    ? "{$user->first_name} {$user->last_name}"
                    : ($user->name ?? $user->email),
                'email' => $user->email,
            ]);

        // Load this shift + series siblings for the week, with their assigned users
        $shiftDate = Carbon::parse($shift->date);
        $weekStart = $shiftDate->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $weekEnd = $shiftDate->copy()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        $relatedShiftIds = collect([$shift->id]);
        if ($shift->series_id) {
            $seriesSiblingIds = Shift::where('series_id', $shift->series_id)
                ->pluck('id');
            $relatedShiftIds = $relatedShiftIds->merge($seriesSiblingIds)->unique();
        }

        $weekShifts = Shift::whereIn('id', $relatedShiftIds)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->with(['users:users.id,users.first_name,users.last_name'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Shift $s): array => [
                'id' => $s->id,
                'uuid' => $s->uuid,
                'date' => $s->date->format('Y-m-d'),
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'type' => $s->type->value,
                'users_by_slot' => $s->users->groupBy(fn (User $u): string => $u->pivot->time_slot)
                    ->map(fn ($group) => $group->map(fn (User $u): array => [
                        'id' => $u->id,
                        'name' => trim("{$u->first_name} {$u->last_name}") ?: 'N/A',
                    ])->values()->all())
                    ->all(),
            ]);

        // Load OTHER department shifts for the same week (not in relatedShiftIds)
        $otherWeekShifts = Shift::where('department_id', $department->id)
            ->whereNotIn('id', $relatedShiftIds)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->with(['users:users.id,users.first_name,users.last_name'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Shift $s): array => [
                'id' => $s->id,
                'uuid' => $s->uuid,
                'date' => $s->date->format('Y-m-d'),
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'type' => $s->type->value,
                'title' => $s->title,
                'users_by_slot' => $s->users->groupBy(fn (User $u): string => $u->pivot->time_slot)
                    ->map(fn ($group) => $group->map(fn (User $u): array => [
                        'id' => $u->id,
                        'name' => trim("{$u->first_name} {$u->last_name}") ?: 'N/A',
                    ])->values()->all())
                    ->all(),
            ]);

        return Inertia::render('Departments/Schedule/Shifts/Show', [
            'department' => $department,
            'schedule' => $schedule,
            'shift' => $shift,
            'conflicts' => $conflicts,
            'shiftTodos' => $shiftTodos,
            'members' => $members,
            'weekShifts' => $weekShifts,
            'otherWeekShifts' => $otherWeekShifts,
            'todoPriorities' => collect(TodoPriority::cases())->map(fn ($p): array => [
                'value' => $p->value,
                'label' => $p->label(),
                'color' => $p->color(),
            ]),
        ]);
    }

    /**
     * Show form to edit a shift
     */
    public function edit(Department $department, WeeklySchedule $schedule, Shift $shift): Response
    {
        $this->authorize('update', $department);

        $shift->load(['user', 'users', 'position', 'tasks']);

        // Get all active users from users table
        $users = User::whereNotNull('email_verified_at')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($user): array => [
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
            ->whereHas('user')
            ->get()
            ->map(fn ($employee): array => [
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
            ->map(fn ($star): array => [
                'id' => $star->user_id ?? $star->id,
                'uuid' => $star->uuid,
                'first_name' => $star->user->first_name ?? $star->title ?? '',
                'last_name' => $star->user->last_name ?? '',
                'email' => $star->user->email ?? '',
                'title' => $star->title,
                'type' => 'star',
            ]);

        $positions = $department->activePositions()->get()->map(fn ($p): array => [
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
            'shiftTypes' => collect(ShiftType::cases())->map(fn ($t): array => [
                'value' => $t->value,
                'label' => $t->label(),
                'color' => $t->color(),
            ]),
            'shiftStatuses' => collect(ShiftStatus::cases())->map(fn ($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
        ]);
    }

    /**
     * Update a shift (optionally all/following shifts in a series)
     */
    public function update(Request $request, Department $department, WeeklySchedule $schedule, Shift $shift): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($schedule->is_locked) {
            return back()->with('error', 'Ce planning est verrouillé.');
        }

        $validated = $request->validate([
            'update_scope' => 'sometimes|in:single,all,following',
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

        $updateScope = $validated['update_scope'] ?? 'single';
        $userIds = $validated['user_ids'] ?? [];
        $updateData = collect($validated)->except(['update_scope', 'user_ids', 'date'])->toArray();

        if ($updateScope !== 'single' && $shift->series_id) {
            $query = Shift::where('series_id', $shift->series_id);

            if ($updateScope === 'following') {
                $query->where('date', '>=', $shift->date);
            }

            $siblings = $query->get();

            $syncData = [];
            foreach ($userIds as $uid) {
                $syncData[$uid] = ['time_slot' => '00:00'];
            }

            foreach ($siblings as $sibling) {
                $sibling->update($updateData);
                $sibling->users()->sync($syncData);
            }
        } else {
            // Update date only for single shift updates
            if (isset($validated['date'])) {
                $updateData['date'] = $validated['date'];
            }
            $shift->update($updateData);

            $syncData = [];
            foreach ($userIds as $uid) {
                $syncData[$uid] = ['time_slot' => '00:00'];
            }
            $shift->users()->sync($syncData);
        }

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

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        $message = 'Employé assigné avec succès.';
        if (! empty($result['warnings'])) {
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

        if (! $shift->can_check_in) {
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

        if (! $shift->can_check_out) {
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
     * Add a user to a shift's pivot table
     */
    public function addUser(Request $request, Department $department, WeeklySchedule $schedule, Shift $shift): RedirectResponse
    {
        $this->authorize('update', $department);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'time_slot' => 'required|string|size:5',
        ]);

        // Prevent duplicate: same user + same time_slot
        $exists = $shift->users()
            ->wherePivot('user_id', $validated['user_id'])
            ->wherePivot('time_slot', $validated['time_slot'])
            ->exists();

        if (! $exists) {
            $shift->users()->attach($validated['user_id'], [
                'time_slot' => $validated['time_slot'],
            ]);
        }

        return back()->with('success', 'Utilisateur ajouté au créneau.');
    }

    /**
     * Remove a user from a specific time slot in a shift
     */
    public function removeUser(Request $request, Department $department, WeeklySchedule $schedule, Shift $shift): RedirectResponse
    {
        $this->authorize('update', $department);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'time_slot' => 'required|string|size:5',
        ]);

        \DB::table('shift_user')
            ->where('shift_id', $shift->id)
            ->where('user_id', $validated['user_id'])
            ->where('time_slot', $validated['time_slot'])
            ->delete();

        return back()->with('success', 'Utilisateur retiré du créneau.');
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

        if (! $shift->status->canTransitionTo(ShiftStatus::CANCELLED)) {
            return back()->with('error', 'Ce shift ne peut pas être annulé depuis son statut actuel.');
        }

        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        $cancellationReason = $validated['cancellation_reason'] ?? null;

        $shift->update([
            'status' => ShiftStatus::CANCELLED,
            'notes' => $cancellationReason
                ? ($shift->notes ? $shift->notes."\n\n" : '')."Raison d'annulation: ".$cancellationReason
                : $shift->notes,
        ]);

        return redirect()->route('departments.schedule.shifts.show', [
            'department' => $department->uuid,
            'schedule' => $schedule->uuid,
            'shift' => $shift->uuid,
        ])->with('success', 'Shift annulé avec succès.');
    }

    /**
     * Delete a shift (optionally all/following shifts in a series)
     */
    public function destroy(Request $request, Department $department, WeeklySchedule $schedule, Shift $shift): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($schedule->is_locked) {
            return back()->with('error', 'Ce planning est verrouillé.');
        }

        $deleteScope = $request->input('delete_scope', 'single');

        if ($deleteScope !== 'single' && $shift->series_id) {
            $query = Shift::where('series_id', $shift->series_id);

            if ($deleteScope === 'following') {
                $query->where('date', '>=', $shift->date);
            }

            $shiftsToDelete = $query->get();

            foreach ($shiftsToDelete as $s) {
                $s->tasks()->delete();
                $s->delete();
            }

            return back()->with('success', count($shiftsToDelete).' shift(s) supprimé(s) avec succès.');
        }

        $shift->tasks()->delete();
        $shift->delete();

        return back()->with('success', 'Shift supprimé avec succès.');
    }

    /** @var array<string, WeeklySchedule> */
    private array $weeklyScheduleCache = [];

    /**
     * Find or create a WeeklySchedule for a given date and department.
     */
    private function findOrCreateWeeklySchedule(Department $department, string $date): WeeklySchedule
    {
        $carbonDate = Carbon::parse($date);
        $weekStart = $carbonDate->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $cacheKey = $department->id.':'.$weekStart;

        if (isset($this->weeklyScheduleCache[$cacheKey])) {
            return $this->weeklyScheduleCache[$cacheKey];
        }

        $weekEnd = Carbon::parse($weekStart)->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        $schedule = WeeklySchedule::where('department_id', $department->id)
            ->whereDate('week_start', $weekStart)
            ->first();

        if (! $schedule) {
            $schedule = WeeklySchedule::create([
                'uuid' => Str::uuid()->toString(),
                'department_id' => $department->id,
                'week_start' => $weekStart,
                'week_end' => $weekEnd,
                'status' => ScheduleStatus::DRAFT->value,
            ]);
        }

        $this->weeklyScheduleCache[$cacheKey] = $schedule;

        return $schedule;
    }

    /**
     * Generate recurring dates between start and end based on frequency.
     *
     * @return string[]
     */
    private function generateRecurringDates(string $startDate, string $endDate, string $recurrenceType): array
    {
        $dates = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current->lte($end)) {
            $dates[] = $current->format('Y-m-d');

            $current = match ($recurrenceType) {
                'daily' => $current->copy()->addDay(),
                'weekly' => $current->copy()->addWeek(),
                'monthly' => $current->copy()->addMonth(),
                default => $end->copy()->addDay(), // break loop
            };
        }

        return $dates;
    }

    /**
     * Sync users to a shift and check for conflicts.
     *
     * @param  int[]  $userIds
     */
    private function syncUsersAndCheckConflicts(Shift $shift, array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }

        $syncData = [];
        foreach ($userIds as $userId) {
            $syncData[$userId] = ['time_slot' => '00:00'];
        }
        $shift->users()->sync($syncData);

        $firstUser = User::find($userIds[0]);
        if ($firstUser) {
            $conflicts = $this->conflictService->detectConflicts($shift, $firstUser);
            if ($conflicts['has_warnings']) {
                session()->flash('warning', 'Shift(s) créé(s) avec des avertissements de conflits.');
            }
        }
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
