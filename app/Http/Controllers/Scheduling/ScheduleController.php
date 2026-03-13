<?php

namespace App\Http\Controllers\Scheduling;

use App\Enums\Scheduling\ShiftTaskStatus;
use App\Enums\Scheduling\TodoPriority;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Scheduling\DepartmentTodo;
use App\Models\Scheduling\WeeklySchedule;
use App\Services\Scheduling\SchedulingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __construct(
        protected SchedulingService $schedulingService
    ) {
        $this->middleware('can:view departments');
        $this->middleware('can:manage departments')->except(['index', 'show', 'stats']);
    }

    /**
     * Display the scheduling dashboard for a department
     */
    public function index(Request $request, Department $department): Response
    {
        $this->authorize('viewSchedule', $department);

        $weekStart = $request->filled('week')
            ? Carbon::parse($request->input('week'))->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $schedule = $this->schedulingService->getOrCreateWeeklySchedule($department, $weekStart);
        $schedule->load(['shifts.user', 'shifts.users', 'shifts.position', 'shifts.tasks']);

        $stats = $this->schedulingService->getScheduleStats($schedule);
        $globalStats = $this->schedulingService->getGlobalStats($department);
        $settings = $this->schedulingService->getSettings($department);

        // Get available weeks for navigation
        $weeks = WeeklySchedule::where('department_id', $department->id)
            ->orderBy('week_start', 'desc')
            ->take(12)
            ->get(['uuid', 'week_start', 'week_end', 'status', 'notes']);

        // Get department todos (all todos, filtering done on frontend)
        $todosCollection = DepartmentTodo::forDepartment($department)
            ->with(['shift.user'])
            ->ordered()
            ->get();

        DepartmentTodo::eagerLoadBackupUsers($todosCollection);

        $todos = $todosCollection->map(fn ($todo): array => $todo->toArrayForApi());

        // Get todo stats
        $todoStats = [
            'total' => DepartmentTodo::forDepartment($department)->count(),
            'pending' => DepartmentTodo::forDepartment($department)->pending()->count(),
            'completed' => DepartmentTodo::forDepartment($department)->completed()->count(),
            'overdue' => DepartmentTodo::forDepartment($department)->overdue()->count(),
            'due_today' => DepartmentTodo::forDepartment($department)->dueToday()->count(),
        ];

        // Get department members for assignment dropdown
        $members = $department->users()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($user): array => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->full_name ?? $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url ?? null,
            ]);

        return Inertia::render('Departments/Schedule/Index', [
            'department' => $department,
            'schedule' => $schedule,
            'stats' => $stats,
            'globalStats' => $globalStats,
            'settings' => $settings,
            'weeks' => $weeks,
            'currentWeek' => $weekStart->format('Y-m-d'),
            'prevWeek' => $weekStart->copy()->subWeek()->format('Y-m-d'),
            'nextWeek' => $weekStart->copy()->addWeek()->format('Y-m-d'),
            'todos' => $todos,
            'todoStats' => $todoStats,
            'members' => $members,
            'todoStatuses' => collect(ShiftTaskStatus::cases())->map(fn ($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
            'todoPriorities' => collect(TodoPriority::cases())->map(fn ($p): array => [
                'value' => $p->value,
                'label' => $p->label(),
                'color' => $p->color(),
            ]),
        ]);
    }

    /**
     * Store a new weekly schedule
     */
    public function store(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $validated = $request->validate([
            'week_start' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $schedule = $this->schedulingService->createWeeklySchedule(
            $department,
            Carbon::parse($validated['week_start']),
            $validated['notes'] ?? null
        );

        return redirect()
            ->route('departments.schedule.index', [
                'department' => $department,
                'week' => $schedule->week_start->format('Y-m-d'),
            ])
            ->with('success', 'Planning créé avec succès.');
    }

    /**
     * Show a specific weekly schedule
     */
    public function show(Department $department, WeeklySchedule $schedule): Response
    {
        $this->authorize('viewSchedule', $department);

        $schedule->load(['shifts.user', 'shifts.position', 'shifts.tasks', 'publishedBy', 'lockedBy']);

        $stats = $this->schedulingService->getScheduleStats($schedule);

        return Inertia::render('Departments/Schedule/Show', [
            'department' => $department,
            'schedule' => $schedule,
            'stats' => $stats,
        ]);
    }

    /**
     * Publish a weekly schedule
     */
    public function publish(Request $request, Department $department, WeeklySchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $department);

        if (! $schedule->status->canTransitionTo(\App\Enums\Scheduling\ScheduleStatus::PUBLISHED)) {
            return back()->with('error', 'Ce planning ne peut pas être publié.');
        }

        $notifyEmployees = $request->boolean('notify_employees', true);

        $this->schedulingService->publishSchedule($schedule, $request->user(), $notifyEmployees);

        return back()->with('success', 'Planning publié avec succès.');
    }

    /**
     * Lock a weekly schedule
     */
    public function lock(Request $request, Department $department, WeeklySchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $department);

        if (! $schedule->status->canTransitionTo(\App\Enums\Scheduling\ScheduleStatus::LOCKED)) {
            return back()->with('error', 'Ce planning ne peut pas être verrouillé.');
        }

        $this->schedulingService->lockSchedule($schedule, $request->user());

        return back()->with('success', 'Planning verrouillé avec succès.');
    }

    /**
     * Copy schedule to another week
     */
    public function copy(Request $request, Department $department, WeeklySchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $department);

        $validated = $request->validate([
            'target_week' => 'required|date',
            'copy_assignments' => 'boolean',
        ]);

        $newSchedule = $this->schedulingService->copyScheduleToWeek(
            $schedule,
            Carbon::parse($validated['target_week']),
            $validated['copy_assignments'] ?? false
        );

        return redirect()
            ->route('departments.schedule.index', [
                'department' => $department,
                'week' => $newSchedule->week_start->format('Y-m-d'),
            ])
            ->with('success', 'Planning copié avec succès.');
    }

    /**
     * Auto-assign shifts
     */
    public function autoAssign(Request $request, Department $department, WeeklySchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($schedule->is_locked) {
            return back()->with('error', 'Ce planning est verrouillé.');
        }

        $result = $this->schedulingService->autoAssignSchedule($schedule, $request->user());

        $message = "{$result['assigned']} shift(s) assigné(s) sur {$result['total']}.";
        if ($result['failed'] > 0) {
            $message .= " {$result['failed']} n'ont pas pu être assignés.";
        }

        return back()->with('success', $message);
    }

    /**
     * Get schedule statistics (API)
     */
    public function stats(Department $department, WeeklySchedule $schedule)
    {
        $this->authorize('viewSchedule', $department);

        return response()->json([
            'stats' => $this->schedulingService->getScheduleStats($schedule),
        ]);
    }

    /**
     * Delete a weekly schedule
     */
    public function destroy(Department $department, WeeklySchedule $schedule): RedirectResponse
    {
        $this->authorize('update', $department);

        if ($schedule->is_locked) {
            return back()->with('error', 'Impossible de supprimer un planning verrouillé.');
        }

        $schedule->shifts()->delete();
        $schedule->delete();

        return redirect()
            ->route('departments.schedule.index', ['department' => $department])
            ->with('success', 'Planning supprimé avec succès.');
    }
}
