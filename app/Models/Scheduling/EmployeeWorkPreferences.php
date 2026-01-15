<?php

namespace App\Models\Scheduling;

use App\Enums\Scheduling\DayOfWeek;
use App\Enums\Scheduling\ShiftType;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmployeeWorkPreferences extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'department_id',
        'preferred_hours_per_week',
        'min_hours_per_week',
        'max_hours_per_week',
        'preferred_days',
        'unavailable_days',
        'preferred_shift_types',
        'earliest_start',
        'latest_end',
        'can_work_weekends',
        'can_work_nights',
        'can_work_holidays',
    ];

    protected $casts = [
        'preferred_hours_per_week' => 'decimal:2',
        'min_hours_per_week' => 'decimal:2',
        'max_hours_per_week' => 'decimal:2',
        'preferred_days' => 'array',
        'unavailable_days' => 'array',
        'preferred_shift_types' => 'array',
        'can_work_weekends' => 'boolean',
        'can_work_nights' => 'boolean',
        'can_work_holidays' => 'boolean',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Methods
    public function prefersDay(DayOfWeek $day): bool
    {
        $preferredDays = $this->preferred_days ?? [];
        return in_array($day->value, $preferredDays);
    }

    public function isUnavailableOnDay(DayOfWeek $day): bool
    {
        $unavailableDays = $this->unavailable_days ?? [];
        return in_array($day->value, $unavailableDays);
    }

    public function prefersShiftType(ShiftType $type): bool
    {
        $preferredTypes = $this->preferred_shift_types ?? [];
        return in_array($type->value, $preferredTypes);
    }

    public function canWorkOnDay(DayOfWeek $day): bool
    {
        if ($this->isUnavailableOnDay($day)) {
            return false;
        }

        if ($day->isWeekend() && !$this->can_work_weekends) {
            return false;
        }

        return true;
    }

    public function canWorkShiftType(ShiftType $type): bool
    {
        if ($type === ShiftType::NIGHT && !$this->can_work_nights) {
            return false;
        }

        return true;
    }

    public function isWithinTimeRange(string $startTime, string $endTime): bool
    {
        if (!$this->earliest_start && !$this->latest_end) {
            return true;
        }

        if ($this->earliest_start && $startTime < $this->earliest_start) {
            return false;
        }

        if ($this->latest_end && $endTime > $this->latest_end) {
            return false;
        }

        return true;
    }

    public function getPreferenceScore(Shift $shift): int
    {
        $score = 50; // Base score

        // Day preference bonus
        $dayOfWeek = DayOfWeek::fromCarbon($shift->date->dayOfWeek);
        if ($this->prefersDay($dayOfWeek)) {
            $score += 20;
        }
        if ($this->isUnavailableOnDay($dayOfWeek)) {
            $score -= 50;
        }

        // Shift type preference bonus
        if ($this->prefersShiftType($shift->type)) {
            $score += 15;
        }

        // Time range bonus
        if ($this->isWithinTimeRange($shift->start_time, $shift->end_time)) {
            $score += 10;
        } else {
            $score -= 20;
        }

        // Weekend penalty if can't work weekends
        if ($dayOfWeek->isWeekend() && !$this->can_work_weekends) {
            $score -= 40;
        }

        // Night penalty if can't work nights
        if ($shift->type === ShiftType::NIGHT && !$this->can_work_nights) {
            $score -= 40;
        }

        return max(0, min(100, $score));
    }
}
