<?php

namespace App\Http\Controllers\Scheduling;

use App\Enums\Scheduling\AvailabilityStatus;
use App\Enums\Scheduling\DayOfWeek;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Scheduling\EmployeeAvailability;
use App\Models\User;
use App\Services\Scheduling\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {}

    /**
     * Display availability overview for a department
     */
    public function index(Request $request, Department $department): Response
    {
        $this->authorize('view', $department);

        $weekStart = $request->filled('week')
            ? Carbon::parse($request->input('week'))->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        // Get members from department OR users who have availability records for this department
        $memberIds = $department->members()->pluck('users.id');
        $availabilityUserIds = EmployeeAvailability::where('department_id', $department->id)
            ->pluck('user_id')
            ->unique();

        $allUserIds = $memberIds->merge($availabilityUserIds)->unique();

        $members = \App\Models\User::whereIn('id', $allUserIds)
            ->with(['employeeAvailabilities' => function ($query) use ($department): void {
                $query->where('department_id', $department->id);
            }, 'absences'])
            ->get();

        $availabilityMatrix = $members->map(function (\App\Models\User $member) use ($weekStart, $weekEnd, $department): array {
            $dates = [];
            $current = $weekStart->copy();

            while ($current->lte($weekEnd)) {
                $availability = $this->availabilityService->getAvailabilityForDateAndDepartment(
                    $member,
                    $current,
                    $department->id
                );
                $dates[$current->format('Y-m-d')] = $availability;
                $current->addDay();
            }

            return [
                'employee' => $member,
                'dates' => $dates,
            ];
        });

        return Inertia::render('Departments/Schedule/Availability/Index', [
            'department' => $department,
            'availabilityMatrix' => $availabilityMatrix,
            'weekStart' => $weekStart->format('Y-m-d'),
            'weekEnd' => $weekEnd->format('Y-m-d'),
            'prevWeek' => $weekStart->copy()->subWeek()->format('Y-m-d'),
            'nextWeek' => $weekStart->copy()->addWeek()->format('Y-m-d'),
            'availabilityStatuses' => collect(AvailabilityStatus::cases())->map(fn ($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
        ]);
    }

    /**
     * Show my availability (for current user)
     */
    public function myAvailability(Request $request, Department $department): Response
    {
        $user = $request->user();

        $weekStart = $request->filled('week')
            ? Carbon::parse($request->input('week'))->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $weeklyPattern = $this->availabilityService->getWeeklyPattern($user, $department->id);

        // Convert weekly pattern to format expected by frontend (keyed by day name)
        $dayKeyMap = [
            0 => 'sunday',
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
        ];

        $currentAvailability = [];
        foreach ($weeklyPattern as $dayNumber => $data) {
            $dayKey = $dayKeyMap[$dayNumber] ?? null;
            if ($dayKey) {
                $isAvailable = $data['status'] !== null && $data['status'] !== AvailabilityStatus::UNAVAILABLE;
                $currentAvailability[$dayKey] = [
                    'available' => $isAvailable,
                    'slots' => $data['start_time'] && $data['end_time']
                        ? [['start' => substr((string) $data['start_time'], 0, 5), 'end' => substr((string) $data['end_time'], 0, 5)]]
                        : [['start' => '08:00', 'end' => '12:00'], ['start' => '14:00', 'end' => '18:00']],
                    'notes' => $data['notes'] ?? '',
                ];
            }
        }

        return Inertia::render('Departments/Schedule/Availability/MyAvailability', [
            'department' => $department,
            'currentAvailability' => $currentAvailability,
            'weeklyPattern' => $weeklyPattern,
            'weekStart' => $weekStart->format('Y-m-d'),
            'weekEnd' => $weekEnd->format('Y-m-d'),
            'prevWeek' => $weekStart->copy()->subWeek()->format('Y-m-d'),
            'nextWeek' => $weekStart->copy()->addWeek()->format('Y-m-d'),
            'availabilityStatuses' => collect(AvailabilityStatus::cases())->map(fn ($s): array => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
            'daysOfWeek' => collect(DayOfWeek::cases())->map(fn ($d): array => [
                'value' => $d->value,
                'label' => $d->label(),
                'short' => $d->shortLabel(),
            ]),
        ]);
    }

    /**
     * Store my availability for the week
     */
    public function storeMyAvailability(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'availability' => 'required|array',
            'availability.*.available' => 'required|boolean',
            'availability.*.slots' => 'nullable|array',
            'availability.*.slots.*.start' => 'nullable|date_format:H:i',
            'availability.*.slots.*.end' => 'nullable|date_format:H:i',
            'availability.*.notes' => 'nullable|string|max:500',
            'week_start' => 'required|date',
        ]);

        $weekStart = Carbon::parse($validated['week_start']);
        $daysMapping = [
            'monday' => 0,
            'tuesday' => 1,
            'wednesday' => 2,
            'thursday' => 3,
            'friday' => 4,
            'saturday' => 5,
            'sunday' => 6,
        ];

        foreach ($validated['availability'] as $dayKey => $dayData) {
            if (! isset($daysMapping[$dayKey])) {
                continue;
            }

            $date = $weekStart->copy()->addDays($daysMapping[$dayKey]);
            $status = $dayData['available'] ? AvailabilityStatus::AVAILABLE : AvailabilityStatus::UNAVAILABLE;

            // Get first slot times if available
            $startTime = null;
            $endTime = null;
            if ($dayData['available'] && ! empty($dayData['slots'])) {
                $startTime = $dayData['slots'][0]['start'] ?? null;
                $endTime = $dayData['slots'][0]['end'] ?? null;
            }

            $this->availabilityService->setAvailability(
                $request->user(),
                $department->id,
                $date,
                $status,
                $startTime,
                $endTime,
                $dayData['notes'] ?? null
            );
        }

        return back()->with('success', 'Disponibilités enregistrées avec succès.');
    }

    /**
     * Set availability for a specific date
     */
    public function store(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'status' => 'required|string',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->availabilityService->setAvailability(
            $request->user(),
            $department->id,
            Carbon::parse($validated['date']),
            AvailabilityStatus::from($validated['status']),
            $validated['start_time'] ?? null,
            $validated['end_time'] ?? null,
            $validated['notes'] ?? null
        );

        return back()->with('success', 'Disponibilité enregistrée.');
    }

    /**
     * Set weekly recurring availability
     */
    public function storeWeekly(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|min:0|max:6',
            'status' => 'required|string',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after_or_equal:effective_from',
        ]);

        $this->availabilityService->setWeeklyAvailability(
            $request->user(),
            $department->id,
            DayOfWeek::from($validated['day_of_week']),
            AvailabilityStatus::from($validated['status']),
            $validated['start_time'] ?? null,
            $validated['end_time'] ?? null,
            $validated['notes'] ?? null,
            isset($validated['effective_from']) ? Carbon::parse($validated['effective_from']) : null,
            isset($validated['effective_until']) ? Carbon::parse($validated['effective_until']) : null
        );

        return back()->with('success', 'Disponibilité hebdomadaire enregistrée.');
    }

    /**
     * Bulk set availability for multiple dates
     */
    public function bulkStore(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'dates' => 'required|array|min:1',
            'dates.*' => 'date',
            'status' => 'required|string',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->availabilityService->bulkSetAvailability(
            $request->user(),
            $department->id,
            $validated['dates'],
            AvailabilityStatus::from($validated['status']),
            $validated['start_time'] ?? null,
            $validated['end_time'] ?? null,
            $validated['notes'] ?? null
        );

        $count = count($validated['dates']);

        return back()->with('success', "Disponibilité enregistrée pour {$count} date(s).");
    }

    /**
     * Clear availability for a specific date
     */
    public function destroy(Request $request, Department $department, string $date): RedirectResponse
    {
        $this->availabilityService->clearAvailability(
            $request->user(),
            $department->id,
            Carbon::parse($date)
        );

        return back()->with('success', 'Disponibilité supprimée.');
    }

    /**
     * Delete a weekly availability pattern
     */
    public function destroyWeekly(Request $request, Department $department, int $dayOfWeek): RedirectResponse
    {
        EmployeeAvailability::where('user_id', $request->user()->id)
            ->where('department_id', $department->id)
            ->where('day_of_week', DayOfWeek::from($dayOfWeek))
            ->delete();

        return back()->with('success', 'Disponibilité hebdomadaire supprimée.');
    }

    /**
     * Get weekly availability for a specific member (API)
     */
    public function getMemberWeekAvailability(Request $request, Department $department, User $user): JsonResponse
    {
        $this->authorize('view', $department);

        $weekStart = $request->filled('week')
            ? Carbon::parse($request->input('week'))->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $dates = [];
        $current = $weekStart->copy();

        while ($current->lte($weekEnd)) {
            $raw = $this->availabilityService->getAvailabilityForDateAndDepartment(
                $user,
                $current,
                $department->id
            );

            $dates[$current->format('Y-m-d')] = [
                'status' => $raw['status'] instanceof AvailabilityStatus ? $raw['status']->value : $raw['status'],
                'is_available' => $raw['is_available'],
                'is_absent' => $raw['absence'] !== null,
                'absence_type' => $raw['absence']?->type?->label() ?? null,
                'time_slots' => $raw['time_slots'],
            ];

            $current->addDay();
        }

        return response()->json([
            'employee' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
            ],
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d'),
            'prev_week' => $weekStart->copy()->subWeek()->format('Y-m-d'),
            'next_week' => $weekStart->copy()->addWeek()->format('Y-m-d'),
            'dates' => $dates,
        ]);
    }

    /**
     * Get availability for a specific date (API)
     */
    public function getForDate(Request $request, Department $department, string $date)
    {
        $this->authorize('view', $department);

        $user = $request->has('user_id')
            ? \App\Models\User::findOrFail($request->input('user_id'))
            : $request->user();

        $availability = $this->availabilityService->getAvailabilityForDate(
            $user,
            Carbon::parse($date)
        );

        return response()->json($availability);
    }

    /**
     * Get available employees for a date/time (API)
     */
    public function getAvailableEmployees(Request $request, Department $department)
    {
        $this->authorize('view', $department);

        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ]);

        $employees = $this->availabilityService->getAvailableEmployees(
            $department->id,
            Carbon::parse($validated['date']),
            $validated['start_time'] ?? null,
            $validated['end_time'] ?? null
        );

        return response()->json([
            'employees' => $employees,
        ]);
    }
}
