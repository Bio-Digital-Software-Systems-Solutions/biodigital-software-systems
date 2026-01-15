<?php

namespace App\Models\Scheduling;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ScheduleTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'department_id',
        'created_by',
        'name',
        'description',
        'shifts_pattern',
        'staff_requirements',
        'is_active',
    ];

    protected $casts = [
        'shifts_pattern' => 'array',
        'staff_requirements' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Relations
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeForDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getShiftsCountAttribute(): int
    {
        return count($this->shifts_pattern ?? []);
    }

    // Methods
    /**
     * Apply template to create shifts for a given week
     */
    public function applyToWeek(WeeklySchedule $schedule): array
    {
        $createdShifts = [];
        $pattern = $this->shifts_pattern ?? [];

        foreach ($pattern as $shiftDef) {
            // Day of week: 0 = Monday, 6 = Sunday
            $dayOffset = $shiftDef['day_of_week'] ?? 0;
            $shiftDate = $schedule->week_start->copy()->addDays($dayOffset);

            $shift = Shift::create([
                'weekly_schedule_id' => $schedule->id,
                'department_id' => $schedule->department_id,
                'date' => $shiftDate,
                'type' => $shiftDef['type'] ?? 'custom',
                'status' => 'draft',
                'start_time' => $shiftDef['start_time'] ?? '09:00',
                'end_time' => $shiftDef['end_time'] ?? '17:00',
                'break_duration' => $shiftDef['break_duration'] ?? 30,
                'location' => $shiftDef['location'] ?? null,
                'position' => $shiftDef['position'] ?? null,
                'notes' => $shiftDef['notes'] ?? null,
                'required_skills' => $shiftDef['required_skills'] ?? null,
            ]);

            $createdShifts[] = $shift;
        }

        return $createdShifts;
    }

    /**
     * Create template from an existing week schedule
     */
    public static function createFromSchedule(WeeklySchedule $schedule, string $name, ?string $description = null): self
    {
        $shifts = $schedule->shifts;
        $pattern = [];

        foreach ($shifts as $shift) {
            $pattern[] = [
                'day_of_week' => $shift->date->dayOfWeekIso - 1, // 0 = Monday
                'type' => $shift->type->value,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'break_duration' => $shift->break_duration,
                'location' => $shift->location,
                'position' => $shift->position,
                'required_skills' => $shift->required_skills,
            ];
        }

        return self::create([
            'department_id' => $schedule->department_id,
            'created_by' => auth()->id(),
            'name' => $name,
            'description' => $description,
            'shifts_pattern' => $pattern,
            'is_active' => true,
        ]);
    }

    /**
     * Get minimum staff requirements for each day
     */
    public function getRequirementsForDay(int $dayOfWeek): array
    {
        $requirements = $this->staff_requirements ?? [];
        return $requirements[$dayOfWeek] ?? [];
    }

    /**
     * Validate that all requirements are met
     */
    public function validateSchedule(WeeklySchedule $schedule): array
    {
        $issues = [];
        $requirements = $this->staff_requirements ?? [];

        foreach ($requirements as $day => $dayReqs) {
            $date = $schedule->week_start->copy()->addDays($day);
            $dayShifts = $schedule->getShiftsForDay($date);

            foreach ($dayReqs as $shiftType => $minCount) {
                $typeShifts = $dayShifts->where('type.value', $shiftType)->count();
                if ($typeShifts < $minCount) {
                    $issues[] = [
                        'day' => $day,
                        'date' => $date->format('Y-m-d'),
                        'shift_type' => $shiftType,
                        'required' => $minCount,
                        'actual' => $typeShifts,
                    ];
                }
            }
        }

        return $issues;
    }
}
