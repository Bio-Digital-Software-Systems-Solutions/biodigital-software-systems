<?php

namespace App\Services\Scheduling;

use App\Enums\Scheduling\ShiftStatus;
use App\Models\Department;
use App\Models\Scheduling\DepartmentSchedulingSettings;
use App\Models\Scheduling\EmployeeWorkPreferences;
use App\Models\Scheduling\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkloadService
{
    /**
     * Get weekly workload for an employee
     */
    public function getWeeklyWorkload(User $employee, Carbon $weekStart, ?int $departmentId = null): array
    {
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $query = Shift::where('user_id', $employee->id)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->whereNotIn('status', [ShiftStatus::CANCELLED]);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $shifts = $query->get();

        $totalHours = $shifts->sum(fn($s) => $s->duration_hours);
        $overtimeHours = $shifts->where('is_overtime', true)->sum(fn($s) => $s->duration_hours);

        $settings = $departmentId
            ? $this->getSettings($departmentId)
            : null;

        $maxHours = $settings?->max_hours_per_week ?? 40;
        $remainingHours = max(0, $maxHours - $totalHours);

        $byDay = [];
        $current = $weekStart->copy();
        while ($current->lte($weekEnd)) {
            $dayShifts = $shifts->filter(fn($s) => $s->date->isSameDay($current));
            $byDay[$current->format('Y-m-d')] = [
                'date' => $current->copy(),
                'day_name' => $current->translatedFormat('l'),
                'shifts_count' => $dayShifts->count(),
                'hours' => $dayShifts->sum(fn($s) => $s->duration_hours),
            ];
            $current->addDay();
        }

        return [
            'total_hours' => round($totalHours, 2),
            'regular_hours' => round($totalHours - $overtimeHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'max_hours' => $maxHours,
            'remaining_hours' => round($remainingHours, 2),
            'utilization_rate' => $maxHours > 0 ? round(($totalHours / $maxHours) * 100, 1) : 0,
            'shifts_count' => $shifts->count(),
            'by_day' => $byDay,
        ];
    }

    /**
     * Get monthly workload for an employee
     */
    public function getMonthlyWorkload(User $employee, Carbon $month, ?int $departmentId = null): array
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $query = Shift::where('user_id', $employee->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->whereNotIn('status', [ShiftStatus::CANCELLED]);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $shifts = $query->get();

        $totalHours = $shifts->sum(fn($s) => $s->duration_hours);
        $overtimeHours = $shifts->where('is_overtime', true)->sum(fn($s) => $s->duration_hours);

        // Weekly breakdown
        $byWeek = [];
        $weekStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);

        while ($weekStart->lte($monthEnd)) {
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
            $weekShifts = $shifts->filter(
                fn($s): bool => $s->date->gte($weekStart) && $s->date->lte($weekEnd)
            );

            $byWeek[] = [
                'week_start' => $weekStart->copy(),
                'week_end' => $weekEnd->copy(),
                'shifts_count' => $weekShifts->count(),
                'hours' => $weekShifts->sum(fn($s) => $s->duration_hours),
            ];

            $weekStart->addWeek();
        }

        return [
            'month' => $month->format('Y-m'),
            'month_name' => $month->translatedFormat('F Y'),
            'total_hours' => round($totalHours, 2),
            'regular_hours' => round($totalHours - $overtimeHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'shifts_count' => $shifts->count(),
            'working_days' => $shifts->pluck('date')->unique()->count(),
            'by_week' => $byWeek,
        ];
    }

    /**
     * Get workload distribution for a department
     */
    public function getDepartmentWorkloadDistribution(
        Department $department,
        Carbon $weekStart
    ): array {
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $members = $department->members()->with(['shifts' => function ($query) use ($weekStart, $weekEnd): void {
            $query->whereBetween('date', [$weekStart, $weekEnd])
                ->whereNotIn('status', [ShiftStatus::CANCELLED]);
        }])->get();

        $settings = $this->getSettings($department->id);
        $maxHours = $settings->max_hours_per_week ?? 40;

        $distribution = $members->map(function ($member) use ($maxHours): array {
            $totalHours = $member->shifts->sum(fn($s) => $s->duration_hours);

            return [
                'employee' => $member,
                'shifts_count' => $member->shifts->count(),
                'total_hours' => round($totalHours, 2),
                'remaining_hours' => round(max(0, $maxHours - $totalHours), 2),
                'utilization_rate' => $maxHours > 0 ? round(($totalHours / $maxHours) * 100, 1) : 0,
                'is_overloaded' => $totalHours > $maxHours,
                'is_underloaded' => $totalHours < ($maxHours * 0.5),
            ];
        });

        $avgHours = $distribution->avg('total_hours');
        $totalShifts = $distribution->sum('shifts_count');

        return [
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'total_employees' => $members->count(),
            'total_shifts' => $totalShifts,
            'average_hours' => round($avgHours, 2),
            'overloaded_count' => $distribution->where('is_overloaded', true)->count(),
            'underloaded_count' => $distribution->where('is_underloaded', true)->count(),
            'employees' => $distribution->sortByDesc('total_hours')->values(),
        ];
    }

    /**
     * Calculate fairness score for workload distribution
     */
    public function calculateFairnessScore(Department $department, Carbon $weekStart): array
    {
        $distribution = $this->getDepartmentWorkloadDistribution($department, $weekStart);

        if ($distribution['total_employees'] < 2) {
            return [
                'score' => 100,
                'rating' => 'excellent',
                'message' => 'Pas assez d\'employés pour calculer l\'équité',
            ];
        }

        $hours = collect($distribution['employees'])->pluck('total_hours');
        $avg = $hours->avg();
        $variance = $hours->map(fn($h): float|int => ($h - $avg) ** 2)->avg();
        $stdDev = sqrt($variance);

        // Calculate coefficient of variation (lower is better)
        $cv = $avg > 0 ? ($stdDev / $avg) * 100 : 0;

        // Convert to fairness score (100 = perfect equality)
        $score = max(0, 100 - $cv);

        $rating = match (true) {
            $score >= 90 => 'excellent',
            $score >= 75 => 'good',
            $score >= 60 => 'fair',
            $score >= 40 => 'poor',
            default => 'very_poor',
        };

        return [
            'score' => round($score, 1),
            'rating' => $rating,
            'standard_deviation' => round($stdDev, 2),
            'coefficient_of_variation' => round($cv, 2),
            'average_hours' => round($avg, 2),
            'min_hours' => $hours->min(),
            'max_hours' => $hours->max(),
            'message' => $this->getFairnessMessage($rating),
        ];
    }

    /**
     * Get employee work preferences
     */
    public function getWorkPreferences(User $employee, int $departmentId): ?EmployeeWorkPreferences
    {
        return EmployeeWorkPreferences::where('user_id', $employee->id)
            ->where('department_id', $departmentId)
            ->first();
    }

    /**
     * Update employee work preferences
     */
    public function updateWorkPreferences(User $employee, int $departmentId, array $data): EmployeeWorkPreferences
    {
        return EmployeeWorkPreferences::updateOrCreate(
            [
                'user_id' => $employee->id,
                'department_id' => $departmentId,
            ],
            $data
        );
    }

    /**
     * Check if employee can take more hours
     */
    public function canTakeMoreHours(User $employee, int $departmentId, float $additionalHours): array
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $workload = $this->getWeeklyWorkload($employee, $weekStart, $departmentId);

        $newTotal = $workload['total_hours'] + $additionalHours;
        $maxHours = $workload['max_hours'];

        $canTake = $newTotal <= $maxHours;
        $wouldExceedBy = max(0, $newTotal - $maxHours);

        return [
            'can_take' => $canTake,
            'current_hours' => $workload['total_hours'],
            'additional_hours' => $additionalHours,
            'new_total' => $newTotal,
            'max_hours' => $maxHours,
            'would_exceed_by' => round($wouldExceedBy, 2),
            'remaining_capacity' => round($workload['remaining_hours'], 2),
        ];
    }

    /**
     * Get overtime summary for a period
     */
    public function getOvertimeSummary(
        Department $department,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $shifts = Shift::where('department_id', $department->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotIn('status', [ShiftStatus::CANCELLED])
            ->whereNotNull('user_id')
            ->with('user')
            ->get();

        $overtimeShifts = $shifts->where('is_overtime', true);
        $totalOvertimeHours = $overtimeShifts->sum(fn($s) => $s->duration_hours);

        $byEmployee = $overtimeShifts->groupBy('user_id')
            ->map(fn($employeeShifts): array => [
                'employee' => $employeeShifts->first()->user,
                'overtime_shifts' => $employeeShifts->count(),
                'overtime_hours' => $employeeShifts->sum(fn($s) => $s->duration_hours),
            ])
            ->sortByDesc('overtime_hours')
            ->values();

        return [
            'period_start' => $startDate,
            'period_end' => $endDate,
            'total_overtime_shifts' => $overtimeShifts->count(),
            'total_overtime_hours' => round($totalOvertimeHours, 2),
            'employees_with_overtime' => $byEmployee->count(),
            'by_employee' => $byEmployee,
        ];
    }

    /**
     * Suggest optimal shift distribution
     */
    public function suggestOptimalDistribution(
        Department $department,
        Carbon $weekStart,
        int $totalHoursNeeded
    ): Collection {
        $distribution = $this->getDepartmentWorkloadDistribution($department, $weekStart);
        $employees = collect($distribution['employees']);

        // Sort by remaining capacity (descending)
        $sorted = $employees->sortByDesc('remaining_hours');

        $suggestions = collect();
        $remainingHours = $totalHoursNeeded;

        foreach ($sorted as $employee) {
            if ($remainingHours <= 0) {
                break;
            }

            $canAssign = min($employee['remaining_hours'], $remainingHours);

            if ($canAssign > 0) {
                $suggestions->push([
                    'employee' => $employee['employee'],
                    'suggested_hours' => round($canAssign, 2),
                    'current_hours' => $employee['total_hours'],
                    'would_have_hours' => round($employee['total_hours'] + $canAssign, 2),
                ]);

                $remainingHours -= $canAssign;
            }
        }

        return $suggestions;
    }

    /**
     * Get settings for department
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
     * Get fairness message based on rating
     */
    protected function getFairnessMessage(string $rating): string
    {
        return match ($rating) {
            'excellent' => 'La répartition des heures est très équilibrée.',
            'good' => 'La répartition des heures est globalement équilibrée.',
            'fair' => 'La répartition des heures pourrait être améliorée.',
            'poor' => 'La répartition des heures est déséquilibrée.',
            'very_poor' => 'La répartition des heures est très déséquilibrée et nécessite une attention.',
            default => '',
        };
    }
}
