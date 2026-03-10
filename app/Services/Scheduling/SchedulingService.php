<?php

namespace App\Services\Scheduling;

use App\Enums\Scheduling\ShiftStatus;
use App\Enums\Scheduling\ScheduleStatus;
use App\Models\Department;
use App\Models\Scheduling\DepartmentSchedulingSettings;
use App\Models\Scheduling\Shift;
use App\Models\Scheduling\WeeklySchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SchedulingService
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected ConflictDetectionService $conflictService,
        protected WorkloadService $workloadService
    ) {}

    /**
     * Create a new weekly schedule for a department
     */
    public function createWeeklySchedule(
        Department $department,
        Carbon $weekStartDate,
        ?string $name = null
    ): WeeklySchedule {
        $weekStart = $weekStartDate->startOfWeek(Carbon::MONDAY);

        return WeeklySchedule::create([
            'department_id' => $department->id,
            'week_start' => $weekStart,
            'week_end' => $weekStart->copy()->endOfWeek(Carbon::SUNDAY),
            'status' => ScheduleStatus::DRAFT,
            'notes' => $name,
        ]);
    }

    /**
     * Get or create weekly schedule for a given week
     */
    public function getOrCreateWeeklySchedule(
        Department $department,
        Carbon $weekStartDate
    ): WeeklySchedule {
        $weekStart = $weekStartDate->startOfWeek(Carbon::MONDAY);

        return WeeklySchedule::firstOrCreate(
            [
                'department_id' => $department->id,
                'week_start' => $weekStart,
            ],
            [
                'week_end' => $weekStart->copy()->endOfWeek(Carbon::SUNDAY),
                'status' => ScheduleStatus::DRAFT,
            ]
        );
    }

    /**
     * Create a new shift
     */
    public function createShift(array $data): Shift
    {
        return DB::transaction(fn() => Shift::create([
            'weekly_schedule_id' => $data['weekly_schedule_id'],
            'department_id' => $data['department_id'],
            'position_id' => $data['position_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'break_duration' => $data['break_duration'] ?? 0,
            'type' => $data['type'],
            'status' => $data['status'] ?? ShiftStatus::DRAFT,
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'color' => $data['color'] ?? null,
            'min_employees' => $data['min_employees'] ?? 1,
            'max_employees' => $data['max_employees'] ?? 1,
            'required_skills' => $data['required_skills'] ?? [],
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'is_overtime' => $data['is_overtime'] ?? false,
            'requires_approval' => $data['requires_approval'] ?? false,
        ]));
    }

    /**
     * Assign an employee to a shift
     */
    public function assignShift(Shift $shift, User $employee, ?User $assignedBy = null): array
    {
        // Check conflicts
        $conflicts = $this->conflictService->detectConflicts($shift, $employee);
        if ($conflicts['has_blocking_conflicts']) {
            return [
                'success' => false,
                'conflicts' => $conflicts['conflicts'],
                'message' => 'Des conflits bloquants empêchent cette assignation.',
            ];
        }

        // Assign the shift
        $shift->update([
            'user_id' => $employee->id,
            'assigned_by' => $assignedBy?->id,
            'assigned_at' => now(),
            'status' => ShiftStatus::CONFIRMED,
        ]);

        return [
            'success' => true,
            'conflicts' => $conflicts['conflicts'],
            'warnings' => $conflicts['warnings'],
            'shift' => $shift->fresh(),
        ];
    }

    /**
     * Unassign an employee from a shift
     */
    public function unassignShift(Shift $shift): Shift
    {
        $shift->update([
            'user_id' => null,
            'assigned_by' => null,
            'assigned_at' => null,
            'status' => ShiftStatus::DRAFT,
        ]);

        return $shift->fresh();
    }

    /**
     * Publish a weekly schedule
     */
    public function publishSchedule(
        WeeklySchedule $schedule,
        User $publishedBy,
        bool $notifyEmployees = true
    ): WeeklySchedule {
        return DB::transaction(function () use ($schedule, $publishedBy, $notifyEmployees) {
            $schedule->publish($publishedBy);

            // Update all draft shifts to published
            $schedule->shifts()
                ->where('status', ShiftStatus::DRAFT)
                ->update(['status' => ShiftStatus::PUBLISHED]);

            if ($notifyEmployees) {
                $this->notifyEmployeesOfPublishedSchedule($schedule);
            }

            return $schedule->fresh();
        });
    }

    /**
     * Lock a weekly schedule
     */
    public function lockSchedule(WeeklySchedule $schedule, User $lockedBy): WeeklySchedule
    {
        return DB::transaction(function () use ($schedule) {
            $schedule->lock();

            return $schedule->fresh();
        });
    }

    /**
     * Get available employees for a shift
     */
    public function getAvailableEmployees(Shift $shift): Collection
    {
        $department = $shift->department;
        $employees = $department->members()
            ->with(['employeeAvailabilities', 'absences', 'shifts', 'skills'])
            ->get();

        return $employees->map(function (\App\Models\User $employee) use ($shift): array {
            $availability = $this->availabilityService->getAvailabilityForDate(
                $employee,
                $shift->date
            );

            $conflicts = $this->conflictService->detectConflicts($shift, $employee);
            $workload = $this->workloadService->getWeeklyWorkload(
                $employee,
                $shift->date->startOfWeek()
            );

            return [
                'employee' => $employee,
                'availability' => $availability,
                'is_available' => $availability['is_available'] && !$conflicts['has_blocking_conflicts'],
                'conflicts' => $conflicts['conflicts'],
                'warnings' => $conflicts['warnings'],
                'current_hours' => $workload['total_hours'],
                'remaining_hours' => $workload['remaining_hours'],
                'score' => $this->calculateAssignmentScore($employee, $shift, $availability, $workload),
            ];
        })->sortByDesc('score')->values();
    }

    /**
     * Suggest auto-assignment for unassigned shifts
     */
    public function suggestAutoAssignment(WeeklySchedule $schedule): Collection
    {
        $unassignedShifts = $schedule->shifts()
            ->whereNull('user_id')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $suggestions = collect();

        foreach ($unassignedShifts as $shift) {
            $availableEmployees = $this->getAvailableEmployees($shift);
            $bestMatch = $availableEmployees->first(fn($e): mixed => $e['is_available']);

            $suggestions->push([
                'shift' => $shift,
                'suggested_employee' => $bestMatch ? $bestMatch['employee'] : null,
                'alternatives' => $availableEmployees->take(5),
                'can_auto_assign' => $bestMatch !== null,
            ]);
        }

        return $suggestions;
    }

    /**
     * Auto-assign all unassigned shifts in a schedule
     */
    public function autoAssignSchedule(
        WeeklySchedule $schedule,
        User $assignedBy,
        bool $respectPreferences = true
    ): array {
        $suggestions = $this->suggestAutoAssignment($schedule);
        $assigned = 0;
        $failed = 0;
        $results = [];

        foreach ($suggestions as $suggestion) {
            if (!$suggestion['can_auto_assign']) {
                $failed++;
                $results[] = [
                    'shift' => $suggestion['shift'],
                    'success' => false,
                    'reason' => 'Aucun employé disponible',
                ];
                continue;
            }

            $result = $this->assignShift(
                $suggestion['shift'],
                $suggestion['suggested_employee'],
                $assignedBy
            );

            if ($result['success']) {
                $assigned++;
            } else {
                $failed++;
            }

            $results[] = array_merge(['shift' => $suggestion['shift']], $result);
        }

        return [
            'total' => $suggestions->count(),
            'assigned' => $assigned,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Copy schedule from one week to another
     */
    public function copyScheduleToWeek(
        WeeklySchedule $sourceSchedule,
        Carbon $targetWeekStart,
        bool $copyAssignments = false
    ): WeeklySchedule {
        return DB::transaction(function () use ($sourceSchedule, $targetWeekStart, $copyAssignments) {
            $targetSchedule = $this->createWeeklySchedule(
                $sourceSchedule->department,
                $targetWeekStart,
                "Copie - Semaine du {$targetWeekStart->format('d/m/Y')}"
            );

            $daysDiff = $sourceSchedule->week_start->diffInDays($targetWeekStart);

            foreach ($sourceSchedule->shifts as $shift) {
                $newShiftData = $shift->only([
                    'department_id',
                    'position_id',
                    'start_time',
                    'end_time',
                    'break_duration',
                    'type',
                    'title',
                    'description',
                    'location',
                    'color',
                    'min_employees',
                    'max_employees',
                    'required_skills',
                    'hourly_rate',
                    'is_overtime',
                    'requires_approval',
                ]);

                $newShiftData['weekly_schedule_id'] = $targetSchedule->id;
                $newShiftData['date'] = $shift->date->copy()->addDays($daysDiff);
                $newShiftData['status'] = ShiftStatus::DRAFT;

                if ($copyAssignments && $shift->user_id) {
                    $newShiftData['user_id'] = $shift->user_id;
                }

                Shift::create($newShiftData);
            }

            return $targetSchedule->fresh(['shifts']);
        });
    }

    /**
     * Get schedule statistics
     */
    public function getScheduleStats(WeeklySchedule $schedule): array
    {
        $shifts = $schedule->shifts()->with(['user', 'users'])->get();

        $totalShifts = $shifts->count();

        // A shift is assigned if it has user_id OR has users in the many-to-many relationship
        $assignedShifts = $shifts->filter(fn($shift): bool => $shift->user_id || $shift->users->isNotEmpty())->count();
        $unassignedShifts = $totalShifts - $assignedShifts;

        $totalHours = $shifts->sum(fn($shift) => $shift->duration_hours);

        // Assigned hours = hours from shifts that have at least one user assigned
        $assignedHours = $shifts->filter(fn($shift): bool => $shift->user_id || $shift->users->isNotEmpty())->sum(fn($shift) => $shift->duration_hours);

        // Calculate employee distribution from both relationships
        $employeeHoursMap = [];

        foreach ($shifts as $shift) {
            // Check single user relationship
            if ($shift->user_id && $shift->user) {
                $userId = $shift->user_id;
                if (!isset($employeeHoursMap[$userId])) {
                    $employeeHoursMap[$userId] = [
                        'employee' => $shift->user,
                        'shifts_count' => 0,
                        'total_hours' => 0,
                    ];
                }
                $employeeHoursMap[$userId]['shifts_count']++;
                $employeeHoursMap[$userId]['total_hours'] += $shift->duration_hours;
            }

            // Check many-to-many users relationship
            foreach ($shift->users as $user) {
                $userId = $user->id;
                if (!isset($employeeHoursMap[$userId])) {
                    $employeeHoursMap[$userId] = [
                        'employee' => $user,
                        'shifts_count' => 0,
                        'total_hours' => 0,
                    ];
                }
                $employeeHoursMap[$userId]['shifts_count']++;
                $employeeHoursMap[$userId]['total_hours'] += $shift->duration_hours;
            }
        }

        $byStatus = $shifts->groupBy(fn($s) => $s->status->value)
            ->map(fn($group): int => $group->count());

        $byType = $shifts->groupBy(fn($s) => $s->type->value)
            ->map(fn($group): int => $group->count());

        return [
            'total_shifts' => $totalShifts,
            'assigned_shifts' => $assignedShifts,
            'unassigned_shifts' => $unassignedShifts,
            'assignment_rate' => $totalShifts > 0 ? round(($assignedShifts / $totalShifts) * 100, 1) : 0,
            'total_hours' => round($totalHours, 1),
            'assigned_hours' => round($assignedHours, 1),
            'unassigned_hours' => round($totalHours - $assignedHours, 1),
            'employee_distribution' => array_values($employeeHoursMap),
            'by_status' => $byStatus,
            'by_type' => $byType,
        ];
    }

    /**
     * Get global statistics for a department (all shifts)
     */
    public function getGlobalStats(Department $department): array
    {
        $shifts = Shift::where('department_id', $department->id)
            ->with(['user', 'users'])
            ->get();

        $totalShifts = $shifts->count();

        // A shift is assigned if it has user_id OR has users in the many-to-many relationship
        $assignedShifts = $shifts->filter(fn($shift): bool => $shift->user_id || $shift->users->isNotEmpty())->count();
        $unassignedShifts = $totalShifts - $assignedShifts;

        $totalHours = $shifts->sum(fn($shift) => $shift->duration_hours);

        // Assigned hours = hours from shifts that have at least one user assigned
        $assignedHours = $shifts->filter(fn($shift): bool => $shift->user_id || $shift->users->isNotEmpty())->sum(fn($shift) => $shift->duration_hours);

        // Calculate employee distribution from both relationships
        $employeeHoursMap = [];

        foreach ($shifts as $shift) {
            // Check single user relationship
            if ($shift->user_id && $shift->user) {
                $userId = $shift->user_id;
                if (!isset($employeeHoursMap[$userId])) {
                    $employeeHoursMap[$userId] = [
                        'employee' => $shift->user,
                        'shifts_count' => 0,
                        'total_hours' => 0,
                    ];
                }
                $employeeHoursMap[$userId]['shifts_count']++;
                $employeeHoursMap[$userId]['total_hours'] += $shift->duration_hours;
            }

            // Check many-to-many users relationship
            foreach ($shift->users as $user) {
                $userId = $user->id;
                if (!isset($employeeHoursMap[$userId])) {
                    $employeeHoursMap[$userId] = [
                        'employee' => $user,
                        'shifts_count' => 0,
                        'total_hours' => 0,
                    ];
                }
                $employeeHoursMap[$userId]['shifts_count']++;
                $employeeHoursMap[$userId]['total_hours'] += $shift->duration_hours;
            }
        }

        $byStatus = $shifts->groupBy(fn($s) => $s->status->value)
            ->map(fn($group) => $group->count());

        $byType = $shifts->groupBy(fn($s) => $s->type->value)
            ->map(fn($group) => $group->count());

        // Count schedules
        $totalSchedules = WeeklySchedule::where('department_id', $department->id)->count();

        return [
            'total_shifts' => $totalShifts,
            'assigned_shifts' => $assignedShifts,
            'unassigned_shifts' => $unassignedShifts,
            'assignment_rate' => $totalShifts > 0 ? round(($assignedShifts / $totalShifts) * 100, 1) : 0,
            'total_hours' => round($totalHours, 1),
            'assigned_hours' => round($assignedHours, 1),
            'unassigned_hours' => round($totalHours - $assignedHours, 1),
            'employee_distribution' => array_values($employeeHoursMap),
            'by_status' => $byStatus,
            'by_type' => $byType,
            'total_schedules' => $totalSchedules,
        ];
    }

    /**
     * Get department scheduling settings
     */
    public function getSettings(Department $department): DepartmentSchedulingSettings
    {
        return DepartmentSchedulingSettings::firstOrCreate(
            ['department_id' => $department->id],
            [
                'default_shift_duration' => 8,
                'min_rest_between_shifts' => 11,
                'max_hours_per_week' => 40,
                'max_hours_per_day' => 10,
                'max_consecutive_days' => 6,
                'overtime_threshold' => 40,
                'allow_self_assignment' => true,
                'allow_shift_swap' => true,
                'require_swap_approval' => true,
                'advance_schedule_weeks' => 4,
                'auto_publish_enabled' => false,
                'notifications_enabled' => true,
            ]
        );
    }

    /**
     * Update department scheduling settings
     */
    public function updateSettings(Department $department, array $data): DepartmentSchedulingSettings
    {
        $settings = $this->getSettings($department);
        $settings->update($data);

        return $settings->fresh();
    }

    /**
     * Calculate assignment score for sorting available employees
     */
    protected function calculateAssignmentScore(
        User $employee,
        Shift $shift,
        array $availability,
        array $workload
    ): float {
        $score = 0;

        // Availability bonus
        if ($availability['status']?->value === 'preferred') {
            $score += 30;
        } elseif ($availability['status']?->value === 'available') {
            $score += 20;
        } elseif ($availability['status']?->value === 'partially_available') {
            $score += 10;
        }

        // Workload balance (prefer employees with fewer hours)
        $hoursRatio = $workload['total_hours'] / max($workload['max_hours'], 1);
        $score += (1 - $hoursRatio) * 25;

        // Skills match bonus
        if ($shift->required_skills) {
            $employeeSkills = $employee->skills->pluck('id')->toArray();
            $matchedSkills = array_intersect($shift->required_skills, $employeeSkills);
            $skillsRatio = count($matchedSkills) / count($shift->required_skills);
            $score += $skillsRatio * 25;
        } else {
            $score += 25; // No skills required, full bonus
        }

        return round($score, 2);
    }

    /**
     * Notify employees of published schedule
     */
    protected function notifyEmployeesOfPublishedSchedule(WeeklySchedule $schedule): void
    {
        // Get unique employees assigned to shifts
        $employees = $schedule->shifts()
            ->whereNotNull('user_id')
            ->with('user')
            ->get()
            ->pluck('user')
            ->unique('id');

        foreach ($employees as $employee) {
            // TODO: Implement notification (email, push, in-app)
            // Notification::send($employee, new SchedulePublishedNotification($schedule));
        }
    }
}
