<?php

namespace App\Services\Scheduling;

use App\Enums\Scheduling\AbsenceStatus;
use App\Enums\Scheduling\ShiftStatus;
use App\Models\Scheduling\Absence;
use App\Models\Scheduling\DepartmentSchedulingSettings;
use App\Models\Scheduling\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ConflictDetectionService
{
    public const CONFLICT_OVERLAP = 'overlap';
    public const CONFLICT_REST_PERIOD = 'rest_period';
    public const CONFLICT_MAX_HOURS_DAY = 'max_hours_day';
    public const CONFLICT_MAX_HOURS_WEEK = 'max_hours_week';
    public const CONFLICT_CONSECUTIVE_DAYS = 'consecutive_days';
    public const CONFLICT_ABSENCE = 'absence';
    public const CONFLICT_SKILLS = 'skills';
    public const CONFLICT_AVAILABILITY = 'availability';

    /**
     * Detect all conflicts for assigning an employee to a shift
     */
    public function detectConflicts(Shift $shift, User $employee): array
    {
        $conflicts = [];
        $warnings = [];

        $settings = $this->getSettings($shift->department_id);

        // Check for overlapping shifts
        $overlapConflict = $this->checkOverlappingShifts($shift, $employee);
        if ($overlapConflict) {
            $conflicts[] = $overlapConflict;
        }

        // Check rest period between shifts
        $restConflict = $this->checkRestPeriod($shift, $employee, $settings);
        if ($restConflict) {
            $conflicts[] = $restConflict;
        }

        // Check absence
        $absenceConflict = $this->checkAbsence($shift, $employee);
        if ($absenceConflict) {
            $conflicts[] = $absenceConflict;
        }

        // Check daily hours limit
        $dailyHoursConflict = $this->checkDailyHoursLimit($shift, $employee, $settings);
        if ($dailyHoursConflict) {
            $warnings[] = $dailyHoursConflict;
        }

        // Check weekly hours limit
        $weeklyHoursConflict = $this->checkWeeklyHoursLimit($shift, $employee, $settings);
        if ($weeklyHoursConflict) {
            $warnings[] = $weeklyHoursConflict;
        }

        // Check consecutive days
        $consecutiveConflict = $this->checkConsecutiveDays($shift, $employee, $settings);
        if ($consecutiveConflict) {
            $warnings[] = $consecutiveConflict;
        }

        // Check required skills
        $skillsConflict = $this->checkRequiredSkills($shift, $employee);
        if ($skillsConflict) {
            $warnings[] = $skillsConflict;
        }

        return [
            'has_blocking_conflicts' => count($conflicts) > 0,
            'has_warnings' => count($warnings) > 0,
            'conflicts' => $conflicts,
            'warnings' => $warnings,
        ];
    }

    /**
     * Check for overlapping shifts
     */
    public function checkOverlappingShifts(Shift $shift, User $employee): ?array
    {
        $overlapping = Shift::where('user_id', $employee->id)
            ->where('id', '!=', $shift->id ?? 0)
            ->where('date', $shift->date)
            ->whereNotIn('status', [ShiftStatus::CANCELLED])
            ->where(function ($query) use ($shift) {
                $query->where(function ($q) use ($shift) {
                    // New shift starts during existing shift
                    $q->where('start_time', '<=', $shift->start_time)
                        ->where('end_time', '>', $shift->start_time);
                })->orWhere(function ($q) use ($shift) {
                    // New shift ends during existing shift
                    $q->where('start_time', '<', $shift->end_time)
                        ->where('end_time', '>=', $shift->end_time);
                })->orWhere(function ($q) use ($shift) {
                    // New shift contains existing shift
                    $q->where('start_time', '>=', $shift->start_time)
                        ->where('end_time', '<=', $shift->end_time);
                });
            })
            ->first();

        if ($overlapping) {
            return [
                'type' => self::CONFLICT_OVERLAP,
                'severity' => 'blocking',
                'message' => "Conflit avec un shift existant ({$overlapping->start_time} - {$overlapping->end_time})",
                'conflicting_shift' => $overlapping,
            ];
        }

        return null;
    }

    /**
     * Check minimum rest period between shifts
     */
    public function checkRestPeriod(Shift $shift, User $employee, DepartmentSchedulingSettings $settings): ?array
    {
        $minRestHours = $settings->min_rest_between_shifts ?? 11;

        // Get previous day's last shift
        $previousShift = Shift::where('user_id', $employee->id)
            ->where('date', $shift->date->copy()->subDay())
            ->whereNotIn('status', [ShiftStatus::CANCELLED])
            ->orderBy('end_time', 'desc')
            ->first();

        if ($previousShift) {
            $previousEnd = Carbon::parse($shift->date->copy()->subDay()->toDateString() . ' ' . $previousShift->end_time);
            $currentStart = Carbon::parse($shift->date->toDateString() . ' ' . $shift->start_time);
            $restHours = $previousEnd->diffInHours($currentStart);

            if ($restHours < $minRestHours) {
                return [
                    'type' => self::CONFLICT_REST_PERIOD,
                    'severity' => 'blocking',
                    'message' => "Période de repos insuffisante ({$restHours}h au lieu de {$minRestHours}h minimum)",
                    'required_rest' => $minRestHours,
                    'actual_rest' => $restHours,
                ];
            }
        }

        // Get next day's first shift
        $nextShift = Shift::where('user_id', $employee->id)
            ->where('date', $shift->date->copy()->addDay())
            ->whereNotIn('status', [ShiftStatus::CANCELLED])
            ->orderBy('start_time', 'asc')
            ->first();

        if ($nextShift) {
            $currentEnd = Carbon::parse($shift->date->toDateString() . ' ' . $shift->end_time);
            $nextStart = Carbon::parse($shift->date->copy()->addDay()->toDateString() . ' ' . $nextShift->start_time);
            $restHours = $currentEnd->diffInHours($nextStart);

            if ($restHours < $minRestHours) {
                return [
                    'type' => self::CONFLICT_REST_PERIOD,
                    'severity' => 'blocking',
                    'message' => "Période de repos insuffisante avant le shift suivant ({$restHours}h au lieu de {$minRestHours}h minimum)",
                    'required_rest' => $minRestHours,
                    'actual_rest' => $restHours,
                ];
            }
        }

        return null;
    }

    /**
     * Check if employee has approved absence on shift date
     */
    public function checkAbsence(Shift $shift, User $employee): ?array
    {
        $absence = Absence::where('user_id', $employee->id)
            ->where('status', AbsenceStatus::APPROVED)
            ->where('start_date', '<=', $shift->date)
            ->where('end_date', '>=', $shift->date)
            ->first();

        if ($absence) {
            return [
                'type' => self::CONFLICT_ABSENCE,
                'severity' => 'blocking',
                'message' => "L'employé est en absence ({$absence->type->label()})",
                'absence' => $absence,
            ];
        }

        return null;
    }

    /**
     * Check daily hours limit
     */
    public function checkDailyHoursLimit(Shift $shift, User $employee, DepartmentSchedulingSettings $settings): ?array
    {
        $maxHoursPerDay = $settings->max_hours_per_day ?? 10;

        $dailyHours = Shift::where('user_id', $employee->id)
            ->where('id', '!=', $shift->id ?? 0)
            ->where('date', $shift->date)
            ->whereNotIn('status', [ShiftStatus::CANCELLED])
            ->get()
            ->sum(fn($s) => $s->duration_hours);

        $totalWithNew = $dailyHours + $shift->duration_hours;

        if ($totalWithNew > $maxHoursPerDay) {
            return [
                'type' => self::CONFLICT_MAX_HOURS_DAY,
                'severity' => 'warning',
                'message' => "Dépassement des heures journalières ({$totalWithNew}h au lieu de {$maxHoursPerDay}h max)",
                'max_hours' => $maxHoursPerDay,
                'current_hours' => $dailyHours,
                'new_hours' => $shift->duration_hours,
                'total_hours' => $totalWithNew,
            ];
        }

        return null;
    }

    /**
     * Check weekly hours limit
     */
    public function checkWeeklyHoursLimit(Shift $shift, User $employee, DepartmentSchedulingSettings $settings): ?array
    {
        $maxHoursPerWeek = $settings->max_hours_per_week ?? 40;
        $weekStart = $shift->date->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $shift->date->copy()->endOfWeek(Carbon::SUNDAY);

        $weeklyHours = Shift::where('user_id', $employee->id)
            ->where('id', '!=', $shift->id ?? 0)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->whereNotIn('status', [ShiftStatus::CANCELLED])
            ->get()
            ->sum(fn($s) => $s->duration_hours);

        $totalWithNew = $weeklyHours + $shift->duration_hours;

        if ($totalWithNew > $maxHoursPerWeek) {
            return [
                'type' => self::CONFLICT_MAX_HOURS_WEEK,
                'severity' => 'warning',
                'message' => "Dépassement des heures hebdomadaires ({$totalWithNew}h au lieu de {$maxHoursPerWeek}h max)",
                'max_hours' => $maxHoursPerWeek,
                'current_hours' => $weeklyHours,
                'new_hours' => $shift->duration_hours,
                'total_hours' => $totalWithNew,
            ];
        }

        return null;
    }

    /**
     * Check consecutive working days
     */
    public function checkConsecutiveDays(Shift $shift, User $employee, DepartmentSchedulingSettings $settings): ?array
    {
        $maxConsecutive = $settings->max_consecutive_days ?? 6;

        // Count consecutive days before shift date
        $daysBefore = 0;
        $checkDate = $shift->date->copy()->subDay();

        while ($daysBefore < $maxConsecutive) {
            $hasShift = Shift::where('user_id', $employee->id)
                ->where('date', $checkDate)
                ->whereNotIn('status', [ShiftStatus::CANCELLED])
                ->exists();

            if (!$hasShift) {
                break;
            }

            $daysBefore++;
            $checkDate->subDay();
        }

        // Count consecutive days after shift date
        $daysAfter = 0;
        $checkDate = $shift->date->copy()->addDay();

        while ($daysAfter < $maxConsecutive) {
            $hasShift = Shift::where('user_id', $employee->id)
                ->where('date', $checkDate)
                ->whereNotIn('status', [ShiftStatus::CANCELLED])
                ->exists();

            if (!$hasShift) {
                break;
            }

            $daysAfter++;
            $checkDate->addDay();
        }

        $totalConsecutive = $daysBefore + 1 + $daysAfter; // +1 for the shift being assigned

        if ($totalConsecutive > $maxConsecutive) {
            return [
                'type' => self::CONFLICT_CONSECUTIVE_DAYS,
                'severity' => 'warning',
                'message' => "Dépassement des jours consécutifs ({$totalConsecutive} jours au lieu de {$maxConsecutive} max)",
                'max_consecutive' => $maxConsecutive,
                'actual_consecutive' => $totalConsecutive,
            ];
        }

        return null;
    }

    /**
     * Check if employee has required skills
     */
    public function checkRequiredSkills(Shift $shift, User $employee): ?array
    {
        if (empty($shift->required_skills)) {
            return null;
        }

        $employeeSkillIds = $employee->skills->pluck('id')->toArray();
        $missingSkills = array_diff($shift->required_skills, $employeeSkillIds);

        if (!empty($missingSkills)) {
            return [
                'type' => self::CONFLICT_SKILLS,
                'severity' => 'warning',
                'message' => 'Compétences manquantes pour ce shift',
                'required_skills' => $shift->required_skills,
                'employee_skills' => $employeeSkillIds,
                'missing_skills' => array_values($missingSkills),
            ];
        }

        return null;
    }

    /**
     * Validate multiple shifts for an employee
     */
    public function validateShifts(Collection $shifts, User $employee): array
    {
        $results = [];

        foreach ($shifts as $shift) {
            $results[$shift->id] = $this->detectConflicts($shift, $employee);
        }

        $totalConflicts = collect($results)->sum(fn($r) => count($r['conflicts']));
        $totalWarnings = collect($results)->sum(fn($r) => count($r['warnings']));

        return [
            'is_valid' => $totalConflicts === 0,
            'total_conflicts' => $totalConflicts,
            'total_warnings' => $totalWarnings,
            'by_shift' => $results,
        ];
    }

    /**
     * Find all employees with conflicts for a shift
     */
    public function findEmployeesWithConflicts(Shift $shift, Collection $employees): Collection
    {
        return $employees->map(function ($employee) use ($shift) {
            return [
                'employee' => $employee,
                'conflicts' => $this->detectConflicts($shift, $employee),
            ];
        })->filter(function ($result) {
            return $result['conflicts']['has_blocking_conflicts'] || $result['conflicts']['has_warnings'];
        })->values();
    }

    /**
     * Get scheduling settings for department
     */
    protected function getSettings(int $departmentId): DepartmentSchedulingSettings
    {
        return DepartmentSchedulingSettings::firstOrCreate(
            ['department_id' => $departmentId],
            [
                'default_shift_duration' => 8,
                'min_rest_between_shifts' => 11,
                'max_hours_per_week' => 40,
                'max_hours_per_day' => 10,
                'max_consecutive_days' => 6,
            ]
        );
    }

    /**
     * Get summary of all conflicts in a schedule
     */
    public function getScheduleConflictSummary(int $weeklyScheduleId): array
    {
        $shifts = Shift::where('weekly_schedule_id', $weeklyScheduleId)
            ->whereNotNull('user_id')
            ->with('user')
            ->get();

        $conflictsByEmployee = [];
        $conflictsByType = [];

        foreach ($shifts as $shift) {
            $result = $this->detectConflicts($shift, $shift->user);

            if ($result['has_blocking_conflicts'] || $result['has_warnings']) {
                $employeeId = $shift->user_id;

                if (!isset($conflictsByEmployee[$employeeId])) {
                    $conflictsByEmployee[$employeeId] = [
                        'employee' => $shift->user,
                        'conflicts' => [],
                        'warnings' => [],
                    ];
                }

                $conflictsByEmployee[$employeeId]['conflicts'] = array_merge(
                    $conflictsByEmployee[$employeeId]['conflicts'],
                    $result['conflicts']
                );

                $conflictsByEmployee[$employeeId]['warnings'] = array_merge(
                    $conflictsByEmployee[$employeeId]['warnings'],
                    $result['warnings']
                );

                foreach ($result['conflicts'] as $conflict) {
                    $type = $conflict['type'];
                    $conflictsByType[$type] = ($conflictsByType[$type] ?? 0) + 1;
                }

                foreach ($result['warnings'] as $warning) {
                    $type = $warning['type'];
                    $conflictsByType[$type] = ($conflictsByType[$type] ?? 0) + 1;
                }
            }
        }

        return [
            'total_shifts' => $shifts->count(),
            'shifts_with_issues' => count($conflictsByEmployee),
            'by_employee' => array_values($conflictsByEmployee),
            'by_type' => $conflictsByType,
        ];
    }
}
