<?php

namespace App\Services\Scheduling;

use App\Enums\Scheduling\AbsenceStatus;
use App\Enums\Scheduling\AvailabilityStatus;
use App\Enums\Scheduling\DayOfWeek;
use App\Enums\Scheduling\RecurrenceType;
use App\Models\Scheduling\Absence;
use App\Models\Scheduling\EmployeeAvailability;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Get employee availability for a specific date
     */
    public function getAvailabilityForDate(User $employee, Carbon $date): array
    {
        // Check for approved absences first
        $absence = $this->getAbsenceForDate($employee, $date);
        if ($absence instanceof \App\Models\Scheduling\Absence) {
            return [
                'is_available' => false,
                'status' => AvailabilityStatus::UNAVAILABLE,
                'reason' => "En absence: {$absence->type->label()}",
                'absence' => $absence,
                'availability' => null,
                'time_slots' => [],
            ];
        }

        // Check explicit availability entries
        $availability = $this->findAvailabilityEntry($employee, $date);

        if ($availability instanceof \App\Models\Scheduling\EmployeeAvailability) {
            return [
                'is_available' => $availability->status->isAvailable(),
                'status' => $availability->status,
                'reason' => $availability->notes,
                'absence' => null,
                'availability' => $availability,
                'time_slots' => $this->parseTimeSlots($availability),
            ];
        }

        // Default: available (no explicit entry means available)
        return [
            'is_available' => true,
            'status' => AvailabilityStatus::AVAILABLE,
            'reason' => null,
            'absence' => null,
            'availability' => null,
            'time_slots' => [],
        ];
    }

    /**
     * Get employee availability for a specific date and department
     */
    public function getAvailabilityForDateAndDepartment(User $employee, Carbon $date, int $departmentId): array
    {
        // Check for approved absences first
        $absence = $this->getAbsenceForDate($employee, $date);
        if ($absence instanceof \App\Models\Scheduling\Absence) {
            return [
                'is_available' => false,
                'status' => AvailabilityStatus::UNAVAILABLE,
                'reason' => "En absence: {$absence->type->label()}",
                'absence' => $absence,
                'availability' => null,
                'time_slots' => [],
            ];
        }

        // Check explicit availability entries for this department
        $availability = $this->findAvailabilityEntryForDepartment($employee, $date, $departmentId);

        if ($availability instanceof \App\Models\Scheduling\EmployeeAvailability) {
            return [
                'is_available' => $availability->status->isAvailable(),
                'status' => $availability->status,
                'reason' => $availability->notes,
                'absence' => null,
                'availability' => $availability,
                'time_slots' => $this->parseTimeSlots($availability),
            ];
        }

        // Default: no entry means not set
        return [
            'is_available' => null,
            'status' => null,
            'reason' => null,
            'absence' => null,
            'availability' => null,
            'time_slots' => [],
        ];
    }

    /**
     * Get availability for a date range
     */
    public function getAvailabilityForRange(
        User $employee,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $availabilities = collect();
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $availabilities->push([
                'date' => $current->copy(),
                'availability' => $this->getAvailabilityForDate($employee, $current),
            ]);
            $current->addDay();
        }

        return $availabilities;
    }

    /**
     * Get weekly availability pattern for an employee
     */
    public function getWeeklyPattern(User $employee, int $departmentId): array
    {
        $pattern = [];

        foreach (DayOfWeek::cases() as $day) {
            $availability = EmployeeAvailability::where('user_id', $employee->id)
                ->where('department_id', $departmentId)
                ->where('day_of_week', $day->value)
                ->where('recurrence_type', RecurrenceType::WEEKLY->value)
                ->first();

            $pattern[$day->value] = [
                'day' => $day,
                'label' => $day->label(),
                'status' => $availability?->status ?? AvailabilityStatus::AVAILABLE,
                'start_time' => $availability?->start_time,
                'end_time' => $availability?->end_time,
                'notes' => $availability?->notes,
            ];
        }

        return $pattern;
    }

    /**
     * Set availability for a specific date (stores as weekly pattern based on day of week)
     */
    public function setAvailability(
        User $employee,
        int $departmentId,
        Carbon $date,
        AvailabilityStatus $status,
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $notes = null
    ): EmployeeAvailability {
        // Convert date to day of week for storage (Carbon dayOfWeek: 0=Sunday, 6=Saturday)
        $dayOfWeek = DayOfWeek::from($date->dayOfWeek);

        return EmployeeAvailability::updateOrCreate(
            [
                'user_id' => $employee->id,
                'department_id' => $departmentId,
                'day_of_week' => $dayOfWeek->value,
            ],
            [
                'status' => $status,
                'start_time' => $startTime ?? '09:00',
                'end_time' => $endTime ?? '17:00',
                'notes' => $notes,
                'recurrence_type' => RecurrenceType::WEEKLY->value,
            ]
        );
    }

    /**
     * Set recurring weekly availability
     */
    public function setWeeklyAvailability(
        User $employee,
        int $departmentId,
        DayOfWeek $dayOfWeek,
        AvailabilityStatus $status,
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $notes = null,
        ?Carbon $effectiveFrom = null,
        ?Carbon $effectiveUntil = null
    ): EmployeeAvailability {
        return EmployeeAvailability::updateOrCreate(
            [
                'user_id' => $employee->id,
                'department_id' => $departmentId,
                'day_of_week' => $dayOfWeek->value,
            ],
            [
                'status' => $status,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'notes' => $notes,
                'recurrence_type' => RecurrenceType::WEEKLY->value,
                'effective_from' => $effectiveFrom,
                'effective_until' => $effectiveUntil,
            ]
        );
    }

    /**
     * Clear availability for a specific date
     */
    public function clearAvailability(User $employee, int $departmentId, Carbon $date): bool
    {
        $dayOfWeek = $date->dayOfWeek;

        return EmployeeAvailability::where('user_id', $employee->id)
            ->where('department_id', $departmentId)
            ->where('day_of_week', $dayOfWeek)
            ->delete() > 0;
    }

    /**
     * Clear availability for a specific day of week
     */
    public function clearWeeklyAvailability(User $employee, int $departmentId, DayOfWeek $dayOfWeek): bool
    {
        return EmployeeAvailability::where('user_id', $employee->id)
            ->where('department_id', $departmentId)
            ->where('day_of_week', $dayOfWeek->value)
            ->delete() > 0;
    }

    /**
     * Get available time slots for an employee on a date
     */
    public function getAvailableTimeSlots(
        User $employee,
        Carbon $date,
        int $departmentId
    ): array {
        $availability = $this->getAvailabilityForDate($employee, $date);

        if (!$availability['is_available']) {
            return [];
        }

        // If specific time slots are defined
        if (!empty($availability['time_slots'])) {
            return $availability['time_slots'];
        }

        // Check work preferences for default hours
        $preferences = $employee->workPreferences()
            ->where('department_id', $departmentId)
            ->first();

        if ($preferences) {
            return [[
                'start' => $preferences->preferred_start_time ?? '08:00',
                'end' => $preferences->preferred_end_time ?? '17:00',
            ]];
        }

        // Default working hours
        return [[
            'start' => '08:00',
            'end' => '17:00',
        ]];
    }

    /**
     * Check if employee is available during specific time range
     */
    public function isAvailableDuring(
        User $employee,
        Carbon $date,
        string $startTime,
        string $endTime
    ): bool {
        $availability = $this->getAvailabilityForDate($employee, $date);

        if (!$availability['is_available']) {
            return false;
        }

        // If no specific time slots, assume available all day
        if (empty($availability['time_slots'])) {
            return true;
        }

        // Check if requested time falls within any available slot
        foreach ($availability['time_slots'] as $slot) {
            if ($startTime >= $slot['start'] && $endTime <= $slot['end']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find absence for a date
     */
    protected function getAbsenceForDate(User $employee, Carbon $date): ?Absence
    {
        return Absence::where('user_id', $employee->id)
            ->where('status', AbsenceStatus::APPROVED)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    /**
     * Find availability entry for a date (based on day of week and effective dates)
     */
    protected function findAvailabilityEntry(User $employee, Carbon $date): ?EmployeeAvailability
    {
        // Use Carbon's dayOfWeek: 0=Sunday, 1=Monday, ..., 6=Saturday
        $dayOfWeek = $date->dayOfWeek;

        return EmployeeAvailability::where('user_id', $employee->id)
            ->where('day_of_week', $dayOfWeek)
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $date);
            })
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $date);
            })
            ->first();
    }

    /**
     * Find availability entry for a date and department
     */
    protected function findAvailabilityEntryForDepartment(User $employee, Carbon $date, int $departmentId): ?EmployeeAvailability
    {
        // Use Carbon's dayOfWeek: 0=Sunday, 1=Monday, ..., 6=Saturday
        $dayOfWeek = $date->dayOfWeek;

        return EmployeeAvailability::where('user_id', $employee->id)
            ->where('department_id', $departmentId)
            ->where('day_of_week', $dayOfWeek)
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $date);
            })
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $date);
            })
            ->first();
    }

    /**
     * Parse time slots from availability entry
     */
    protected function parseTimeSlots(EmployeeAvailability $availability): array
    {
        if (!$availability->start_time || !$availability->end_time) {
            return [];
        }

        return [[
            'start' => $availability->start_time,
            'end' => $availability->end_time,
        ]];
    }

    /**
     * Get employees available on a specific date and time
     */
    public function getAvailableEmployees(
        int $departmentId,
        Carbon $date,
        ?string $startTime = null,
        ?string $endTime = null
    ): Collection {
        // Get all department members
        $employees = User::whereHas('departments', function ($query) use ($departmentId): void {
            $query->where('department_id', $departmentId);
        })->get();

        return $employees->filter(function (\App\Models\User $employee) use ($date, $startTime, $endTime) {
            if ($startTime && $endTime) {
                return $this->isAvailableDuring($employee, $date, $startTime, $endTime);
            }

            $availability = $this->getAvailabilityForDate($employee, $date);
            return $availability['is_available'];
        })->values();
    }

    /**
     * Bulk set availability for multiple dates
     */
    public function bulkSetAvailability(
        User $employee,
        int $departmentId,
        array $dates,
        AvailabilityStatus $status,
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $notes = null
    ): Collection {
        $results = collect();

        foreach ($dates as $date) {
            $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);
            $results->push(
                $this->setAvailability(
                    $employee,
                    $departmentId,
                    $carbonDate,
                    $status,
                    $startTime,
                    $endTime,
                    $notes
                )
            );
        }

        return $results;
    }

    /**
     * Bulk set weekly availability for multiple days
     */
    public function bulkSetWeeklyAvailability(
        User $employee,
        int $departmentId,
        array $days,
        AvailabilityStatus $status,
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $notes = null
    ): Collection {
        $results = collect();

        foreach ($days as $day) {
            $dayOfWeek = $day instanceof DayOfWeek ? $day : DayOfWeek::from($day);
            $results->push(
                $this->setWeeklyAvailability(
                    $employee,
                    $departmentId,
                    $dayOfWeek,
                    $status,
                    $startTime,
                    $endTime,
                    $notes
                )
            );
        }

        return $results;
    }
}
