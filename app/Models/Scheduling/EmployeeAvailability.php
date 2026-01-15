<?php

namespace App\Models\Scheduling;

use App\Enums\Scheduling\AvailabilityStatus;
use App\Enums\Scheduling\RecurrenceType;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmployeeAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'department_id',
        'day_of_week',
        'start_time',
        'end_time',
        'status',
        'recurrence_type',
        'effective_from',
        'effective_until',
        'notes',
    ];

    protected $casts = [
        'status' => AvailabilityStatus::class,
        'recurrence_type' => RecurrenceType::class,
        'effective_from' => 'date',
        'effective_until' => 'date',
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


    // Scopes
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        $dayOfWeek = strtolower($date->format('l'));
        return $query->where('day_of_week', $dayOfWeek)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_from')
                  ->orWhere('effective_from', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', $date);
            });
    }

    public function scopeForDayOfWeek(Builder $query, string $dayOfWeek): Builder
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            AvailabilityStatus::AVAILABLE,
            AvailabilityStatus::PREFERRED,
            AvailabilityStatus::PARTIALLY_AVAILABLE,
        ]);
    }

    public function scopeUnavailable(Builder $query): Builder
    {
        return $query->where('status', AvailabilityStatus::UNAVAILABLE);
    }

    // Accessors
    public function getTimeRangeAttribute(): ?string
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }
        return $this->start_time . ' - ' . $this->end_time;
    }

    public function getIsRecurringAttribute(): bool
    {
        return $this->recurrence_type === RecurrenceType::WEEKLY;
    }

    // Methods
    public function isAvailableAt(string $startTime, string $endTime): bool
    {
        if (!$this->status->allowsAssignment()) {
            return false;
        }

        // If no time range specified, consider fully available
        if (!$this->start_time || !$this->end_time) {
            return true;
        }

        $availStart = Carbon::parse($this->start_time);
        $availEnd = Carbon::parse($this->end_time);
        $shiftStart = Carbon::parse($startTime);
        $shiftEnd = Carbon::parse($endTime);

        return $shiftStart >= $availStart && $shiftEnd <= $availEnd;
    }

    public function appliesToDate(Carbon $date): bool
    {
        $dayOfWeek = strtolower($date->format('l'));

        if ($this->day_of_week !== $dayOfWeek) {
            return false;
        }

        if ($this->effective_from && $date->lt($this->effective_from)) {
            return false;
        }

        if ($this->effective_until && $date->gt($this->effective_until)) {
            return false;
        }

        return true;
    }

    public static function getForUserAndWeek(int $userId, int $departmentId, Carbon $weekStart): array
    {
        $weekEnd = $weekStart->copy()->addDays(6);

        // Get weekly recurring availabilities
        $availabilities = self::where('user_id', $userId)
            ->where('department_id', $departmentId)
            ->get();

        $result = [];
        $current = $weekStart->copy();

        while ($current <= $weekEnd) {
            $dateKey = $current->format('Y-m-d');
            $dayOfWeek = strtolower($current->format('l'));

            // Find availability that applies to this date
            $applicable = $availabilities->first(function ($avail) use ($current) {
                return $avail->appliesToDate($current);
            });

            $result[$dateKey] = $applicable;
            $current->addDay();
        }

        return $result;
    }
}
